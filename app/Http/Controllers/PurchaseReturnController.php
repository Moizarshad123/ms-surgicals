<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseReturnController extends Controller
{
    /**
     * Display a listing of purchase returns.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = PurchaseReturn::with(['purchase', 'user', 'items.product']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('return_no', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%")
                  ->orWhereHas('purchase', function ($pq) use ($search) {
                      $pq->where('reference_no', 'like', "%{$search}%");
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
        $totalReturnsCount = PurchaseReturn::count();
        $totalReturnsValue = (float) PurchaseReturn::sum('total_amount');
        $totalUnitsReturned = (int) PurchaseReturnItem::sum('quantity');

        return view('purchase_returns.index', compact(
            'returns',
            'search',
            'startDate',
            'endDate',
            'totalReturnsCount',
            'totalReturnsValue',
            'totalUnitsReturned'
        ));
    }

    /**
     * Show the form for creating a new purchase return.
     */
    public function create(Request $request)
    {
        $selectedPurchase = null;
        if ($request->has('purchase_id')) {
            $selectedPurchase = Purchase::with(['items.product', 'items.returnItems'])
                ->find($request->get('purchase_id'));
        }

        return view('purchase_returns.create', compact('selectedPurchase'));
    }

    /**
     * Search purchases for return selection (AJAX).
     */
    public function searchPurchase(Request $request)
    {
        $id = $request->get('id');
        $q = trim($request->get('q', ''));

        if ($id) {
            $purchase = Purchase::with(['items.product', 'items.returnItems'])->find($id);
            if (!$purchase) {
                return response()->json(['success' => false, 'message' => 'Purchase not found.'], 404);
            }
            return response()->json(['success' => true, 'purchase' => $this->formatPurchaseData($purchase)]);
        }

        if ($q) {
            $purchases = Purchase::where('reference_no', 'like', "%{$q}%")
                ->orWhere('supplier_name', 'like', "%{$q}%")
                ->latest('purchase_date')
                ->limit(10)
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'reference_no' => $p->reference_no,
                    'supplier_name' => $p->supplier_name ?: 'General Supplier',
                    'date' => Carbon::parse($p->purchase_date)->format('d M, Y'),
                    'total' => (float) $p->total,
                ]);

            return response()->json(['success' => true, 'purchases' => $purchases]);
        }

        return response()->json(['success' => true, 'purchases' => []]);
    }

    /**
     * Store a newly created purchase return in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => ['required', 'exists:purchases,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['required', 'exists:purchase_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'return_date' => ['nullable', 'date'],
        ]);

        $itemsToReturn = array_filter($validated['items'], fn($item) => (int)$item['quantity'] > 0);

        if (empty($itemsToReturn)) {
            return response()->json([
                'success' => false,
                'message' => 'Please specify at least 1 item with a quantity greater than 0 to return.',
            ], 422);
        }

        $purchaseReturn = DB::transaction(function () use ($validated, $itemsToReturn) {
            $purchase = Purchase::lockForUpdate()->with(['items.product', 'items.returnItems'])->findOrFail($validated['purchase_id']);

            $totalReturnAmount = 0;
            $preparedReturnItems = [];

            foreach ($itemsToReturn as $itemData) {
                $purchaseItem = $purchase->items->firstWhere('id', $itemData['purchase_item_id']);
                if (!$purchaseItem) {
                    abort(422, 'Invalid purchase item selected for this order.');
                }

                $qtyToReturn = (int) $itemData['quantity'];
                $availableQty = $purchaseItem->available_return_quantity;
                $currentStock = $purchaseItem->product->stock;

                if ($qtyToReturn > $availableQty) {
                    abort(422, "Cannot return {$qtyToReturn} units of {$purchaseItem->product->name}. Only {$availableQty} units available for return from this order.");
                }

                if ($qtyToReturn > $currentStock) {
                    abort(422, "Cannot return {$qtyToReturn} units of {$purchaseItem->product->name}. Current in-hand stock is only {$currentStock} units.");
                }

                $lineTotal = round($qtyToReturn * $purchaseItem->price, 2);
                $totalReturnAmount += $lineTotal;

                $preparedReturnItems[] = [
                    'purchase_item' => $purchaseItem,
                    'quantity' => $qtyToReturn,
                    'price' => $purchaseItem->price,
                    'total' => $lineTotal,
                ];
            }

            $returnNo = 'PRET-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
            $returnDate = $validated['return_date'] ?? now()->toDateString();

            // Create Purchase Return Record
            $purchaseReturn = PurchaseReturn::create([
                'return_no' => $returnNo,
                'purchase_id' => $purchase->id,
                'supplier_name' => $purchase->supplier_name ?: 'General Supplier',
                'user_id' => auth()->id(),
                'return_date' => $returnDate,
                'total_amount' => $totalReturnAmount,
                'reason' => $validated['reason'] ?? 'Supplier Return',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create return items and decrement product stock
            foreach ($preparedReturnItems as $prep) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_item_id' => $prep['purchase_item']->id,
                    'product_id' => $prep['purchase_item']->product_id,
                    'quantity' => $prep['quantity'],
                    'price' => $prep['price'],
                    'total' => $prep['total'],
                ]);

                // Decrement stock since goods are returned to supplier
                Product::where('id', $prep['purchase_item']->product_id)
                    ->decrement('stock', $prep['quantity']);
            }

            // Adjust Purchase Total
            $newTotal = max(0, (float) $purchase->total - $totalReturnAmount);
            $purchase->update(['total' => $newTotal]);

            return $purchaseReturn;
        });

        return response()->json([
            'success' => true,
            'message' => 'Purchase return recorded successfully! Stock deducted.',
            'return_id' => $purchaseReturn->id,
            'receipt_url' => route('purchase-returns.receipt', $purchaseReturn->id),
        ]);
    }

    /**
     * Show purchase return details for modal / AJAX.
     */
    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['purchase', 'user', 'items.product']);

        return response()->json([
            'success' => true,
            'return' => [
                'id' => $purchaseReturn->id,
                'return_no' => $purchaseReturn->return_no,
                'reference_no' => $purchaseReturn->purchase->reference_no,
                'supplier_name' => $purchaseReturn->supplier_name ?: 'General Supplier',
                'return_date' => $purchaseReturn->return_date->format('d M, Y'),
                'total_amount' => (float) $purchaseReturn->total_amount,
                'reason' => $purchaseReturn->reason ?? '—',
                'notes' => $purchaseReturn->notes ?? '—',
                'processed_by' => $purchaseReturn->user->name ?? 'Staff',
                'items' => $purchaseReturn->items->map(fn($item) => [
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
     * Stream an 80mm thermal receipt PDF for the purchase return / debit note.
     */
    public function receipt(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['purchase', 'user', 'items.product']);

        $logoPath = public_path('admin/images/logo-2.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $content = '';
        foreach ($purchaseReturn->items as $item) {
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
                    font-size: 13px;
                    font-weight: bold;
                    margin: 6px 0 2px 0;
                    text-transform: uppercase;
                }
                .badge-return {
                    display: inline-block;
                    padding: 2px 6px;
                    border: 1px solid #000;
                    font-size: 10px;
                    font-weight: bold;
                    margin-bottom: 6px;
                }
                .invoice-no {
                    font-size: 12px;
                    font-weight: bold;
                    margin: 0 0 6px 0;
                }
                .solid-line {
                    border: none;
                    border-bottom: 1px solid #000;
                    margin: 5px 0;
                }
                .dotted-line {
                    border: none;
                    border-bottom: 2px dotted #000;
                    margin: 5px 0;
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
                    body {
                        width: 80mm;
                    }
                }
            </style>
        </head>
        <body>'
            . ($logoBase64 ? '<div class="text-center" style="margin-bottom:6px;"><img src="' . $logoBase64 . '" style="width:120px; height:auto; display:inline-block;"></div>' : '') . '
            <div class="text-center shop-name">MS SURGICALS</div>
            <div class="text-center shop-sub">Surgical &amp; Medical Supplies</div>
            <hr class="dotted-line">
            <div class="text-center">
                <span class="badge-return">PURCHASE RETURN SLIP / DEBIT NOTE</span>
            </div>
            <div class="text-center invoice-no">Return #: ' . e($purchaseReturn->return_no) . '</div>
            <hr class="solid-line">

            <table class="meta-table">
                <tr>
                    <td class="label">Orig. Order:</td>
                    <td>' . e($purchaseReturn->purchase->reference_no) . '</td>
                </tr>
                <tr>
                    <td class="label">Supplier:</td>
                    <td>' . e($purchaseReturn->supplier_name) . '</td>
                </tr>
                <tr>
                    <td class="label">Date:</td>
                    <td>' . $purchaseReturn->return_date->format('d/m/Y') . ' (' . $purchaseReturn->created_at->format('h:i A') . ')</td>
                </tr>
                <tr>
                    <td class="label">Issued By:</td>
                    <td>' . e($purchaseReturn->user->name ?? 'Staff') . '</td>
                </tr>
                ' . ($purchaseReturn->reason ? '<tr><td class="label">Reason:</td><td>' . e($purchaseReturn->reason) . '</td></tr>' : '') . '
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
                    <td>TOTAL MERCHANDISE RETURNED:</td>
                    <td class="amt">Rs. ' . number_format($purchaseReturn->total_amount) . '</td>
                </tr>
                <tr>
                    <td>Revised Purchase Total:</td>
                    <td class="amt">Rs. ' . number_format($purchaseReturn->purchase->total) . '</td>
                </tr>
            </table>

            <hr class="dotted-line">
            <div class="thank-you">
                <div style="font-weight:bold;">Stock Deducted &amp; Returned to Vendor.</div>
                <div>Software Developed by Antigravity</div>
            </div>
        </body>
        </html>';

        $pdf = Pdf::loadHTML($htmlContent);
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->stream('purchase-return-' . $purchaseReturn->return_no . '.pdf');
    }

    /**
     * Format purchase for JSON consumption.
     */
    private function formatPurchaseData(Purchase $purchase): array
    {
        return [
            'id' => $purchase->id,
            'reference_no' => $purchase->reference_no,
            'supplier_name' => $purchase->supplier_name ?: 'General Supplier',
            'date' => Carbon::parse($purchase->purchase_date)->format('d M, Y'),
            'total' => (float) $purchase->total,
            'items' => $purchase->items->map(function ($item) {
                return [
                    'purchase_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Item',
                    'product_code' => $item->product->code ?? '',
                    'price' => (float) $item->price,
                    'purchased_quantity' => (int) $item->quantity,
                    'returned_quantity' => (int) $item->returned_quantity,
                    'available_quantity' => (int) $item->available_return_quantity,
                    'current_stock' => (int) ($item->product->stock ?? 0),
                ];
            }),
        ];
    }
}

