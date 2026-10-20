<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function index()
    {
        $products   = Product::with('category')->where('stock', '>', 0)->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $customers  = Customer::orderBy('name')->get();

        return view('pos.index', compact('products', 'categories', 'customers'));
    }

    public function searchCustomers(Request $request)
    {
        $q = $request->get('q', '');
        $customers = Customer::where('name', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'total_due' => (float) $c->total_due,
            ]);

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $sale = DB::transaction(function () use ($validated, $request) {
            // Find or create customer
            $customer = null;
            if (!empty($validated['customer_id'])) {
                $customer = Customer::find($validated['customer_id']);
            } elseif (!empty($validated['customer_phone'])) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $validated['customer_phone']],
                    ['name' => !empty($validated['customer_name']) ? $validated['customer_name'] : 'Walk-in Customer']
                );
                if (!empty($validated['customer_name']) && $customer->name === 'Walk-in Customer') {
                    $customer->update(['name' => $validated['customer_name']]);
                }
            } elseif (!empty($validated['customer_name']) && $validated['customer_name'] !== 'Walk-in Customer') {
                $customer = Customer::create([
                    'name' => $validated['customer_name'],
                    'phone' => null,
                ]);
            }

            $subTotal = 0;
            $lineItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    abort(422, "Insufficient stock for {$product->name}. Available: {$product->stock}");
                }

                $unitPrice = isset($item['price']) && is_numeric($item['price']) && (float) $item['price'] >= 0
                    ? (float) $item['price']
                    : (float) $product->sale_price;

                $lineTotal = $unitPrice * $item['quantity'];
                $subTotal += $lineTotal;

                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $unitPrice,
                    'total' => $lineTotal,
                ];
            }

            $discount = $validated['discount'] ?? 0;
            $total = max($subTotal - $discount, 0);

            $paidInput = isset($validated['paid_amount']) ? (float) $validated['paid_amount'] : $total;
            $paidAmount = min($paidInput, $total);

            // Determine payment status
            if ($paidAmount >= $total) {
                $paymentStatus = 'paid';
                $dueDate = null;
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
                $dueDate = $validated['due_date'] ?? null;
            } else {
                $paymentStatus = 'due';
                $dueDate = $validated['due_date'] ?? null;
            }

            $lastId  = Sale::lockForUpdate()->latest('id')->value('id') ?? 0;
            $orderNo = str_pad($lastId + 1, 3, '0', STR_PAD_LEFT); // 001, 002, 003 ...


            $sale = Sale::create([
                'invoice_no' => 'INV-' . now()->format('Ymd') . '-' . $orderNo,
                'customer_id' => $customer?->id,
                'user_id' => auth()->id(),
                'sub_total' => $subTotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'due_date' => $dueDate,
                'payment_status' => $paymentStatus,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Record initial / advance payment
            if ($paidAmount > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $paidAmount,
                    'payment_date' => now()->toDateString(),
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'note' => $paidAmount < $total ? 'Advance / Down Payment' : 'Full Payment at Sale',
                    'user_id' => auth()->id(),
                ]);
            }

            foreach ($lineItems as $line) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'total' => $line['total'],
                ]);

                $line['product']->decrement('stock', $line['quantity']);
            }

            return $sale;
        });

        return response()->json([
            'success' => true,
            'sale_id' => $sale->id,
            'invoice_url' => route('pos.invoice', $sale->id),
            'is_credit' => $sale->payment_status !== 'paid',
            'due_amount' => (float) $sale->due_amount,
            'statement_url' => route('credit-sales.receipt', $sale->id),
        ]);
    }

    /**
     * Delete a sale and restore inventory stock.
     */
    public function destroy(Sale $sale)
    {
        DB::transaction(function () use ($sale) {
            $sale->load(['items.returnItems']);

            // 1. Revert product stock
            foreach ($sale->items as $item) {
                $returnedQty = (int) $item->returnItems->sum('quantity');
                $restorableQty = max(0, (int) $item->quantity - $returnedQty);

                if ($restorableQty > 0) {
                    Product::where('id', $item->product_id)->increment('stock', $restorableQty);
                }
            }

            // 2. Delete the sale (cascades to sale_items, sale_payments, sale_returns, sale_return_items)
            $sale->delete();
        });

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Sale {$sale->invoice_no} deleted successfully and inventory restored.",
            ]);
        }

        return back()->with('success', "Sale {$sale->invoice_no} deleted successfully and inventory restored.");
    }

    /**
     * Stream the sale as an 80mm thermal-receipt PDF.
     * Clean layout: no negative margins, everything fits inside the page box.
     */
    public function invoice(Sale $sale)
    {
        $sale->load('items.product', 'customer');

        $currentDue = max(0, (float) $sale->total - (float) $sale->paid_amount);

        // Previous balance of the customer from all earlier sales
        $previousBalance = 0;
        if ($sale->customer_id) {
            $previousBalance = (float) Sale::where('customer_id', $sale->customer_id)
                ->where('id', '<', $sale->id)
                ->whereRaw('total > paid_amount')
                ->sum(DB::raw('total - paid_amount'));
            $previousBalance = max(0, $previousBalance);
        }

        $totalNetDue = $previousBalance + $currentDue;

        $content = '';
        foreach ($sale->items as $item) {
            $content .= '<tr>
                            <td style="text-align:left;">' . e($item->product->name) . '</td>
                            <td style="text-align:center;">' . $item->quantity . '</td>
                            <td>' . number_format($item->price) . '</td>
                            <td>' . number_format($item->total) . '</td>
                        </tr>';
        }

        $htmlContent = '
        <html>
        <head>
            <style>
                * {
                    box-sizing: border-box;
                }
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
                    margin: 0px 0 10px 0;
                }
                .receipt-title {
                    font-size: 15px;
                    font-weight: bold;
                    margin: 10px 0 2px 0;
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
                    font-size: 12px;
                }
                th, td {
                    padding: 3px 2px;
                }
                .meta-table td {
                    padding: 1px 0;
                    font-size: 11px;
                }
                .meta-table .label {
                    font-weight: bold;
                    width: 70px;
                }
                .items-table thead th {
                    border-bottom: 1px solid #000;
                    font-size: 11px;
                }
                .totals-table td {
                    font-size: 12px;
                }
                .totals-table .amt {
                    text-align: right;
                }
                .grand-row td {
                    font-size: 14px;
                    font-weight: bold;
                    padding-top: 4px;
                }
                .thank-you {
                    text-align: center;
                    font-size: 11px;
                    margin-top: 15px;
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
            ' . $this->getReceiptHeaderHtml('SALE RECEIPT', 'Invoice # ' . $sale->invoice_no) . '

            <table class="meta-table">
                <tr><td class="label">Date:</td><td>' . $sale->created_at->format('d-m-Y h:i A') . '</td></tr>
                <tr><td class="label">Customer:</td><td>' . e($sale->customer->name ?? 'Walk-in Customer') . '</td></tr>
                <tr><td class="label">Phone:</td><td>' . e($sale->customer->phone ?? '-') . '</td></tr>
                ' . ($currentDue > 0 ? '<tr><td class="label">Sale Type:</td><td><strong>CREDIT / INSTALLMENT</strong></td></tr>' : '') . '
                ' . ($currentDue > 0 && $sale->due_date ? '<tr><td class="label">Due Date:</td><td><strong>' . $sale->due_date->format('d-m-Y') . '</strong></td></tr>' : '') . '
            </table>

            <hr class="dotted-line">

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="text-align:left;">Item</th>
                        <th style="text-align:center;">Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $content . '
                </tbody>
            </table>

            <hr class="dotted-line">

            <table class="totals-table">
                <tr>
                    <td>Sub Total:</td>
                    <td class="amt">' . number_format($sale->sub_total) . '</td>
                </tr>
                ' . ($sale->discount > 0 ? '
                <tr>
                    <td>Discount:</td>
                    <td class="amt">' . number_format($sale->discount) . '</td>
                </tr>' : '') . '
            </table>

            <hr class="dotted-line">

            <table class="totals-table">
                <tr class="grand-row">
                    <td>Inv Total Amt:-</td>
                    <td class="amt">' . number_format($sale->total) . '</td>
                </tr>
                ' . ($sale->customer_id ? '
                <tr>
                    <td>Previous Bal :-</td>
                    <td class="amt">' . number_format($previousBalance) . '</td>
                </tr>
                ' : '') . '
                ' . ($sale->paid_amount > 0 ? '
                <tr>
                    <td>' . ($currentDue > 0 ? 'Paid Today:-' : 'Paid:-') . '</td>
                    <td class="amt">' . number_format($sale->paid_amount) . '</td>
                </tr>
                ' : '') . '
            </table>

            <hr class="dotted-line">

            <table class="totals-table">
                <tr class="grand-row">
                    <td>' . ($sale->customer_id ? 'Total Net Due:-' : ($currentDue > 0 ? 'Remaining Due:-' : 'Change:-')) . '</td>
                    <td class="amt">' . ($sale->customer_id ? number_format($totalNetDue) : ($currentDue > 0 ? number_format($currentDue) : number_format(abs($sale->paid_amount - $sale->total)))) . '</td>
                </tr>
            </table>

            <p class="thank-you">Thank you for your purchase!</p>
        </body>
        </html>';

        $pdf = Pdf::loadHTML($htmlContent)->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->stream('invoice-' . $sale->invoice_no . '.pdf');
    }

    public function updateProductPrice(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sale_price' => ['required', 'numeric', 'min:0', 'max:99999'],
        ]);

        $product->update(['sale_price' => $validated['sale_price']]);

        return response()->json([
            'success' => true,
            'message' => 'Product catalog price updated successfully.',
            'sale_price' => (float) $product->sale_price,
        ]);
    }
}