<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesReturnController extends Controller
{
    /**
     * Display a listing of sales returns.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = SaleReturn::with(['sale', 'customer', 'user', 'items.product']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('return_no', 'like', "%{$search}%")
                  ->orWhereHas('sale', function ($sq) use ($search) {
                      $sq->where('invoice_no', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($startDate && $endDate) {
            $query->whereBetween('return_date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString(),
            ]);
        }

        $returns = $query->latest('id')->paginate(15)->withQueryString();

        // Metrics
        $totalReturnsCount = SaleReturn::count();
        $totalReturnsValue = (float) SaleReturn::sum('total_amount');
        $totalCashRefunded = (float) SaleReturn::sum('refund_amount');
        $totalDueDeducted  = (float) SaleReturn::sum('deducted_from_due');

        return view('sales_returns.index', compact(
            'returns',
            'search',
            'startDate',
            'endDate',
            'totalReturnsCount',
            'totalReturnsValue',
            'totalCashRefunded',
            'totalDueDeducted'
        ));
    }

    /**
     * Show the form for creating a new sales return.
     */
    public function create(Request $request)
    {
        $selectedSale = null;
        if ($request->has('sale_id')) {
            $selectedSale = Sale::with(['customer', 'items.product', 'items.returnItems'])
                ->find($request->get('sale_id'));
        }

        return view('sales_returns.create', compact('selectedSale'));
    }

    /**
     * Search sales by invoice or customer phone/name for return selection (AJAX).
     */
    public function searchSale(Request $request)
    {
        $id = $request->get('id');
        $q = trim($request->get('q', ''));

        if ($id) {
            $sale = Sale::with(['customer', 'items.product', 'items.returnItems'])->find($id);
            if (!$sale) {
                return response()->json(['success' => false, 'message' => 'Sale not found.'], 404);
            }
            return response()->json(['success' => true, 'sale' => $this->formatSaleData($sale)]);
        }

        if ($q) {
            $sales = Sale::with('customer')
                ->where('invoice_no', 'like', "%{$q}%")
                ->orWhereHas('customer', function ($cq) use ($q) {
                    $cq->where('name', 'like', "%{$q}%")
                       ->orWhere('phone', 'like', "%{$q}%");
                })
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'invoice_no' => $s->invoice_no,
                    'customer_name' => $s->customer->name ?? 'Walk-in Customer',
                    'customer_phone' => $s->customer->phone ?? '—',
                    'date' => $s->created_at->format('d M, Y'),
                    'total' => (float) $s->total,
                    'due' => (float) $s->due_amount,
                ]);

            return response()->json(['success' => true, 'sales' => $sales]);
        }

        return response()->json(['success' => true, 'sales' => []]);
    }

    /**
     * Store a newly created sales return in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => ['required', 'exists:sales,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'return_date' => ['nullable', 'date'],
        ]);

        // Filter out items with 0 return quantity
        $itemsToReturn = array_filter($validated['items'], fn($item) => (int)$item['quantity'] > 0);

        if (empty($itemsToReturn)) {
            return response()->json([
                'success' => false,
                'message' => 'Please specify at least 1 item with a quantity greater than 0 to return.',
            ], 422);
        }

        $saleReturn = DB::transaction(function () use ($validated, $itemsToReturn) {
            $sale = Sale::lockForUpdate()->with(['items.product', 'items.returnItems'])->findOrFail($validated['sale_id']);

            $totalReturnAmount = 0;
            $preparedReturnItems = [];

            foreach ($itemsToReturn as $itemData) {
                $saleItem = $sale->items->firstWhere('id', $itemData['sale_item_id']);
                if (!$saleItem) {
                    abort(422, 'Invalid sale item selected for this invoice.');
                }

                $qtyToReturn = (int) $itemData['quantity'];
                $availableQty = $saleItem->available_return_quantity;

                if ($qtyToReturn > $availableQty) {
                    abort(422, "Cannot return {$qtyToReturn} units of {$saleItem->product->name}. Only {$availableQty} units available for return.");
                }

                $lineTotal = round($qtyToReturn * $saleItem->price, 2);
                $totalReturnAmount += $lineTotal;

                $preparedReturnItems[] = [
                    'sale_item' => $saleItem,
                    'quantity' => $qtyToReturn,
                    'price' => $saleItem->price,
                    'total' => $lineTotal,
                ];
            }

            // Settlement calculations
            $currentDue = (float) $sale->due_amount;
            if ($currentDue > 0) {
                $deductedFromDue = min($currentDue, $totalReturnAmount);
                $refundAmount = max(0, $totalReturnAmount - $deductedFromDue);
            } else {
                $deductedFromDue = 0;
                $refundAmount = $totalReturnAmount;
            }

            $returnNo = 'RET-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
            $returnDate = $validated['return_date'] ?? now()->toDateString();

            // Create Return Record
            $saleReturn = SaleReturn::create([
                'return_no' => $returnNo,
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'user_id' => auth()->id(),
                'return_date' => $returnDate,
                'total_amount' => $totalReturnAmount,
                'deducted_from_due' => $deductedFromDue,
                'refund_amount' => $refundAmount,
                'reason' => $validated['reason'] ?? 'Customer Return',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create return items and restock products
            foreach ($preparedReturnItems as $prep) {
                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $prep['sale_item']->id,
                    'product_id' => $prep['sale_item']->product_id,
                    'quantity' => $prep['quantity'],
                    'price' => $prep['price'],
                    'total' => $prep['total'],
                ]);

                // Increment product stock
                Product::where('id', $prep['sale_item']->product_id)
                    ->increment('stock', $prep['quantity']);
            }

            // Adjust the Sale
            $newTotal = max(0, (float) $sale->total - $totalReturnAmount);
            $newSubTotal = max(0, (float) $sale->sub_total - $totalReturnAmount);
            $newPaid = max(0, (float) $sale->paid_amount - $refundAmount);
            $newStatus = ($newPaid >= $newTotal) ? 'paid' : ($newPaid > 0 ? 'partial' : 'due');

            $sale->update([
                'total' => $newTotal,
                'sub_total' => $newSubTotal,
                'paid_amount' => $newPaid,
                'payment_status' => $newStatus,
            ]);

            // If cash was refunded, log it in sale_payments as a refund entry
            if ($refundAmount > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => -$refundAmount,
                    'payment_date' => $returnDate,
                    'payment_method' => 'cash',
                    'note' => 'Cash Refund on Return #' . $returnNo,
                    'user_id' => auth()->id(),
                ]);
            }

            return $saleReturn;
        });

        return response()->json([
            'success' => true,
            'message' => 'Sales return processed successfully! Stock has been updated.',
            'return_id' => $saleReturn->id,
            'receipt_url' => route('sales-returns.receipt', $saleReturn->id),
        ]);
    }

    /**
     * Show return details for modal / AJAX.
     */
    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load(['sale', 'customer', 'user', 'items.product']);

        return response()->json([
            'success' => true,
            'return' => [
                'id' => $saleReturn->id,
                'return_no' => $saleReturn->return_no,
                'invoice_no' => $saleReturn->sale->invoice_no,
                'customer_name' => $saleReturn->customer->name ?? 'Walk-in Customer',
                'customer_phone' => $saleReturn->customer->phone ?? '—',
                'return_date' => $saleReturn->return_date->format('d M, Y'),
                'total_amount' => (float) $saleReturn->total_amount,
                'deducted_from_due' => (float) $saleReturn->deducted_from_due,
                'refund_amount' => (float) $saleReturn->refund_amount,
                'reason' => $saleReturn->reason ?? '—',
                'notes' => $saleReturn->notes ?? '—',
                'processed_by' => $saleReturn->user->name ?? 'Cashier',
                'items' => $saleReturn->items->map(fn($item) => [
                    'product_name' => $item->product->name ?? 'Item',
                    'product_code' => $item->product->code ?? '',
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'total' => (float) $item->total,
                ]),
            ],
        ]);
    }

    /**
     * Stream an 80mm thermal receipt PDF for the sales return.
     */
    public function receipt(SaleReturn $saleReturn)
    {
        $saleReturn->load(['sale', 'customer', 'user', 'items.product']);


        $content = '';
        foreach ($saleReturn->items as $item) {
            $content .= '<tr>
                            <td style="text-align:left;">' . e($item->product->name) . '</td>
                            <td style="text-align:center;">' . $item->quantity . '</td>
                            <td style="text-align:right;">' . number_format($item->price) . '</td>
                            <td style="text-align:right;">' . number_format($item->total) . '</td>
                        </tr>';
        }

        $htmlContent = '
        <html>
        <head>
            <style>
                * { box-sizing: border-box; }
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 10px 12px;
                    font-size: 12px;
                    color: #000;
                }
                .text-center { text-align: center; }
                .shop-name {
                    font-size: 16px;
                    font-weight: bold;
                    margin: 0;
                }
                .shop-sub {
                    font-size: 11px;
                    margin: 0px 0 6px 0;
                }
                .receipt-title {
                    font-size: 15px;
                    font-weight: bold;
                    margin: 10px 0 2px 0;
                    text-transform: uppercase;
                }
                .invoice-no {
                    font-size: 12px;
                    font-weight: bold;
                    margin: 0 0 8px 0;
                }
                .solid-line {
                    border: none;
                    border-bottom: 1px solid #000;
                    margin: 6px 0;
                }
                .dotted-line {
                    border: none;
                    border-bottom: 2px dotted #000;
                    margin: 6px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 11px;
                }
                th, td {
                    padding: 2px 1px;
                }
                .meta-table td {
                    padding: 1px 0;
                    font-size: 11px;
                }
                .meta-table .label {
                    font-weight: bold;
                    width: 80px;
                }
                .items-table thead th {
                    border-bottom: 1px solid #000;
                    font-size: 11px;
                }
                .totals-table td {
                    font-size: 11px;
                    padding: 2px 0;
                }
                .totals-table .amt {
                    text-align: right;
                }
                .grand-row td {
                    font-size: 13px;
                    font-weight: bold;
                    padding-top: 4px;
                }
                .thank-you {
                    text-align: center;
                    font-size: 11px;
                    margin-top: 12px;
                }
                @media print {
                    @page {
                        size: 80mm auto;
                        margin: 0;
                    }
                    img {
                        display: block;
                        max-width: 80% !important;
                    }
                    body {
                        width: 80mm;
                    }
                }
            </style>
        </head>
        <body>
            ' . $this->getReceiptHeaderHtml('SALES RETURN SLIP / CREDIT NOTE', 'Return # ' . $saleReturn->return_no) . '

            <table class="meta-table">
                <tr>
                    <td class="label">Orig. Invoice:</td>
                    <td>' . e($saleReturn->sale->invoice_no) . '</td>
                </tr>
                <tr>
                    <td class="label">Customer:</td>
                    <td>' . e($saleReturn->customer->name ?? 'Walk-in Customer') . '</td>
                </tr>
                <tr>
                    <td class="label">Phone:</td>
                    <td>' . e($saleReturn->customer->phone ?? '—') . '</td>
                </tr>
                <tr>
                    <td class="label">Date:</td>
                    <td>' . $saleReturn->return_date->format('d/m/Y') . ' (' . $saleReturn->created_at->format('h:i A') . ')</td>
                </tr>
                <tr>
                    <td class="label">Cashier:</td>
                    <td>' . e($saleReturn->user->name ?? 'Cashier') . '</td>
                </tr>
                ' . ($saleReturn->reason ? '<tr><td class="label">Reason:</td><td>' . e($saleReturn->reason) . '</td></tr>' : '') . '
            </table>

            <hr class="solid-line">

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="text-align:left;">Item</th>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:right;">Price</th>
                        <th style="text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $content . '
                </tbody>
            </table>

            <hr class="solid-line">

            <table class="totals-table">
                <tr class="grand-row">
                    <td>TOTAL RETURN VALUE:</td>
                    <td class="amt">Rs. ' . number_format($saleReturn->total_amount) . '</td>
                </tr>
                <tr>
                    <td>Deducted from Udhaar/Due:</td>
                    <td class="amt" style="font-weight:bold;">- Rs. ' . number_format($saleReturn->deducted_from_due) . '</td>
                </tr>
                <tr>
                    <td>Cash Refunded to Customer:</td>
                    <td class="amt" style="font-weight:bold;">Rs. ' . number_format($saleReturn->refund_amount) . '</td>
                </tr>
                <tr>
                    <td>Invoice Remaining Balance:</td>
                    <td class="amt">Rs. ' . number_format($saleReturn->sale->due_amount) . '</td>
                </tr>
            </table>

            <hr class="dotted-line">
            <div class="thank-you">
                <div style="font-weight:bold;">Items returned &amp; restocked.</div>
                <div>Software Developed by Antigravity</div>
            </div>
        </body>
        </html>';

        $pdf = Pdf::loadHTML($htmlContent);
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->stream('return-' . $saleReturn->return_no . '.pdf');
    }

    /**
     * Format a sale for JSON consumption.
     */
    private function formatSaleData(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'customer_name' => $sale->customer->name ?? 'Walk-in Customer',
            'customer_phone' => $sale->customer->phone ?? '—',
            'date' => $sale->created_at->format('d M, Y h:i A'),
            'sub_total' => (float) $sale->sub_total,
            'discount' => (float) $sale->discount,
            'total' => (float) $sale->total,
            'paid_amount' => (float) $sale->paid_amount,
            'due_amount' => (float) $sale->due_amount,
            'payment_status' => $sale->payment_status,
            'items' => $sale->items->map(function ($item) {
                return [
                    'sale_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Item',
                    'product_code' => $item->product->code ?? '',
                    'price' => (float) $item->price,
                    'sold_quantity' => (int) $item->quantity,
                    'returned_quantity' => (int) $item->returned_quantity,
                    'available_quantity' => (int) $item->available_return_quantity,
                ];
            }),
        ];
    }
}

