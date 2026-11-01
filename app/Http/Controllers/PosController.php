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
        $q = $request->get('q', $request->get('query', ''));
        $customers = Customer::where('name', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'total_due' => (float) $c->total_due,
                'advance_balance' => (float) $c->advance_balance,
            ]);

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_date' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            // 'discount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
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
            $totalDiscount = 0;
            $total = 0;
            $lineItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    abort(422, "Insufficient stock for {$product->name}. Available: {$product->stock}");
                }

                $qty = (int) $item['quantity'];
                $unitPrice = isset($item['price']) && is_numeric($item['price']) && (float) $item['price'] >= 0
                    ? (float) $item['price']
                    : (float) $product->sale_price;

                $discountPercent = isset($item['discount_percent']) && is_numeric($item['discount_percent'])
                    ? (float) $item['discount_percent']
                    : 0.0;

                // Step 1: Calculate discount per unit = price * discount%
                $discountPerUnit = round($unitPrice * ($discountPercent / 100), 4);
                // Step 2: Calculate discounted unit price = price - discount per unit
                $discountedPrice = max(0, round($unitPrice - $discountPerUnit, 4));
                // Step 3: Calculate final subtotal = discounted unit price * qty
                $lineTotal = round($discountedPrice * $qty, 2);
                $lineRawTotal = round($unitPrice * $qty, 2);
                $lineDiscountAmount = max(0, round($lineRawTotal - $lineTotal, 2));

                $subTotal += $lineRawTotal;
                $totalDiscount += $lineDiscountAmount;
                $total += $lineTotal;

                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'price' => $unitPrice,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $lineDiscountAmount,
                    'total' => $lineTotal,
                ];
            }

            $total = max($total, 0);


            $paidInput = isset($validated['paid_amount']) ? (float) $validated['paid_amount'] : 0.0;

            // Check if customer has advance balance
            $customerAdvance = 0.0;
            if ($customer) {
                $customer->refresh();
                $customerAdvance = (float) $customer->advance_balance;
            }

            // Deduct available advance up to invoice total
            $advanceToApply = min($customerAdvance, $total);
            $netPayable = max(0, round($total - $advanceToApply, 2));

            // If user paid more than net payable, store excess as customer advance
            $excessPaid = max(0, round($paidInput - $netPayable, 2));
            $paidToApply = min($paidInput, $netPayable);
            $totalEffectivePaid = round($advanceToApply + $paidToApply, 2);

            // Deduct applied advance from customer
            if ($advanceToApply > 0 && $customer) {
                $customer->decrement('advance_balance', $advanceToApply);
            }

            // If user paid more than net payable, store excess as customer advance
            if ($excessPaid > 0 && $customer) {
                $customer->increment('advance_balance', $excessPaid);
            }

            // Determine payment status
            if ($totalEffectivePaid >= $total) {
                $paymentStatus = 'paid';
                $dueDate = null;
            } elseif ($totalEffectivePaid > 0) {
                $paymentStatus = 'partial';
                $dueDate = $validated['due_date'] ?? null;
            } else {
                $paymentStatus = 'due';
                $dueDate = $validated['due_date'] ?? null;
            }

            $lastId  = Sale::lockForUpdate()->latest('id')->value('id') ?? 0;
            $orderNo = str_pad($lastId + 1, 3, '0', STR_PAD_LEFT); // 001, 002, 003 ...

            $orderDate = !empty($validated['order_date'])
                ? \Carbon\Carbon::parse($validated['order_date'])->setTimeFrom(now())
                : now();

            $sale = new Sale([
                'invoice_no' => 'INV-' . $orderDate->format('Ymd') . '-' . $orderNo,
                'customer_id' => $customer?->id,
                'user_id' => auth()->id(),
                'sub_total' => $subTotal,
                'discount' => $totalDiscount,
                'total' => $total,
                'paid_amount' => $totalEffectivePaid,
                'advance_adjusted' => $advanceToApply,
                'due_date' => $dueDate,
                'payment_status' => $paymentStatus,
                'notes' => $validated['notes'] ?? null,
            ]);
            $sale->created_at = $orderDate;
            $sale->save();

            // Record advance adjustment payment if applied
            if ($advanceToApply > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $advanceToApply,
                    'payment_date' => $orderDate->toDateString(),
                    'payment_method' => 'advance_adjustment',
                    'note' => 'Auto-adjusted from Customer Advance Balance (Rs. ' . number_format($advanceToApply, 2) . ')',
                    'user_id' => auth()->id(),
                ]);
            }

            // Record manual paid amount payment
            if ($paidInput > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $paidInput,
                    'payment_date' => $orderDate->toDateString(),
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'note' => $excessPaid > 0
                        ? 'Payment at Sale (Includes Rs. ' . number_format($excessPaid, 2) . ' stored as advance)'
                        : ($totalEffectivePaid < $total ? 'Advance / Down Payment' : 'Full Payment at Sale'),
                    'user_id' => auth()->id(),
                ]);
            }

            foreach ($lineItems as $line) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'discount_percent' => $line['discount_percent'],
                    'discount_amount' => $line['discount_amount'],
                    'total' => $line['total'],
                ]);

                $line['product']->decrement('stock', $line['quantity']);
            }

            return $sale;
        });

        $customer = $sale->customer;

        return response()->json([
            'success' => true,
            'sale_id' => $sale->id,
            'invoice_url' => route('pos.invoice', $sale->id),
            'is_credit' => $sale->payment_status !== 'paid',
            'due_amount' => (float) $sale->due_amount,
            'statement_url' => route('credit-sales.receipt', $sale->id),
            'customer_id' => $customer?->id,
            'customer_advance_balance' => $customer ? (float) $customer->fresh()->advance_balance : 0,
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
     * Show the edit form for an existing sale order.
     */
    public function edit(Sale $sale)
    {
        $sale->load(['items.product', 'customer', 'payments']);
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('sales.edit', compact('sale', 'products', 'customers'));
    }

    /**
     * Update an existing sale order and re-adjust inventory.
     */
    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'order_date' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            // 'discount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        DB::transaction(function () use ($validated, $sale) {
            // 1. Resolve customer
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

            // 2. Revert previous stock deduction from existing sale items
            foreach ($sale->items as $oldItem) {
                Product::where('id', $oldItem->product_id)->increment('stock', $oldItem->quantity);
            }

            // 3. Process new items and deduct stock
            $subTotal = 0;
            $totalDiscount = 0;
            $total = 0;
            $lineItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    abort(422, "Insufficient stock for {$product->name}. Current in-hand stock: {$product->stock}");
                }

                $product->decrement('stock', $item['quantity']);

                $qty = (int) $item['quantity'];
                $unitPrice = (float) $item['price'];
                $discountPercent = isset($item['discount_percent']) && is_numeric($item['discount_percent'])
                    ? (float) $item['discount_percent']
                    : 0.0;

                // Step 1: Calculate discount per unit = price * discount%
                $discountPerUnit = round($unitPrice * ($discountPercent / 100), 4);
                // Step 2: Calculate discounted unit price = price - discount per unit
                $discountedPrice = max(0, round($unitPrice - $discountPerUnit, 4));
                // Step 3: Calculate final subtotal = discounted unit price * qty
                $lineTotal = round($discountedPrice * $qty, 2);
                $lineRawTotal = round($unitPrice * $qty, 2);
                $lineDiscountAmount = max(0, round($lineRawTotal - $lineTotal, 2));

                $subTotal += $lineRawTotal;
                $totalDiscount += $lineDiscountAmount;
                $total += $lineTotal;

                $lineItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'price' => $unitPrice,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $lineDiscountAmount,
                    'total' => $lineTotal,
                ];
            }

            // Delete old items and insert updated items
            $sale->items()->delete();
            foreach ($lineItems as $line) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'discount_percent' => $line['discount_percent'],
                    'discount_amount' => $line['discount_amount'],
                    'total' => $line['total'],
                ]);
            }

            // 4. Calculate totals and payment status
            $total = max(0, $total);

            $paidInput = isset($validated['paid_amount']) ? (float) $validated['paid_amount'] : (float) $sale->paid_amount;
            $paidAmount = min($paidInput, $total);

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

            $updateData = [
                'customer_id' => $customer?->id ?? $sale->customer_id,
                'sub_total'   => $subTotal,
                'discount'    => $totalDiscount,
                'total'       => $total,
                'paid_amount' => $paidAmount,
                'due_date'    => $dueDate,
                'payment_status' => $paymentStatus,
                'notes'          => $validated['notes'] ?? null,
            ];

            $sale->fill($updateData);
            if (!empty($validated['order_date'])) {
                $newDate = \Carbon\Carbon::parse($validated['order_date'])->setTimeFrom($sale->created_at ?? now());
                $updateData['created_at'] = $newDate;

                $sale->created_at = \Carbon\Carbon::parse($validated['order_date'])
                                                    ->setTimeFrom($sale->created_at ?? now());
            }
            

            $sale->save();

            // Sync payment record
            $firstPayment = $sale->payments()->first();
            if ($firstPayment) {
                $firstPayment->update(['amount' => $paidAmount]);
            } elseif ($paidAmount > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $paidAmount,
                    'payment_date' => now()->toDateString(),
                    'payment_method' => 'cash',
                    'note' => $paidAmount < $total ? 'Advance / Down Payment' : 'Full Payment',
                    'user_id' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('reports.sales')->with('success', "Order #{$sale->invoice_no} updated successfully.");
    }

    /**
     * Stream the sale as an 80mm thermal-receipt PDF.
     * Clean layout: no negative margins, everything fits inside the page box.
     */
    public function invoice(Sale $sale)
    {
        $sale->load('items.product', 'customer');

        $currentDue = max(0, (float) $sale->total - (float) $sale->paid_amount);

        $advanceAdjusted = (float) $sale->advance_adjusted;
        if ($advanceAdjusted <= 0) {
            $advanceAdjusted = (float) $sale->payments()->where('payment_method', 'advance_adjustment')->sum('amount');
        }
        $cashPaidToday = max(0, (float) $sale->paid_amount - $advanceAdjusted);
        $remainingPayable = max(0, (float) $sale->total - $advanceAdjusted);

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
            if ($item->quantity <= 0) {
                continue;
            }

            $discPercent = (float) ($item->discount_percent ?? 0);
            $discAmount = (float) ($item->discount_amount ?? 0);
            $discDisplay = $discPercent > 0
                ? rtrim(rtrim(number_format($discPercent, 2), '0'), '.') . '%'
                : '-';

            $content .= '<tr>
                            <td style="text-align:center;">' . $item->quantity . '</td>
                            <td style="text-align:left; word-break:break-word;">' . e($item->product->name ?? 'Item') .
                                ($discAmount > 0 ? '<div style="font-size:9.5px; color:#555; line-height:1.2;">Disc: -Rs. ' . number_format($discAmount, 2) . '</div>' : '') .
                            '</td>
                            <td style="text-align:right;">' . number_format((float) $item->price, 2) . '</td>
                            <td style="text-align:center;">' . $discDisplay . '</td>
                            <td style="text-align:right;">' . number_format((float) $item->total, 2) . '</td>
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
                        <th style="width:12%; text-align:center;">Qty</th>
                        <th style="width:40%; text-align:left;">Item</th>
                        <th style="width:18%; text-align:right;">Price</th>
                        <th style="width:12%; text-align:center;">Disc%</th>
                        <th style="width:18%; text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $content . '
                </tbody>
            </table>

            <hr class="dotted-line">

            <table class="totals-table">
                <tr>
                    <td>Sub Total (' . $sale->items->count() . ' ' . ($sale->items->count() === 1 ? 'Item' : 'Items') . '):</td>
                    <td class="amt">' . number_format((float) $sale->sub_total, 2) . '</td>
                </tr>
                ' . ((float) $sale->discount > 0 ? '
                <tr>
                    <td style="color:#dc3545; font-weight:bold;">Discount:</td>
                    <td class="amt" style="color:#dc3545; font-weight:bold;">- ' . number_format((float) $sale->discount, 2) . '</td>
                </tr>' : '') . '
            </table>

            <hr class="dotted-line">

            <table class="totals-table">
                <tr class="grand-row">
                    <td>Inv Total Amt:-</td>
                    <td class="amt">' . number_format((float) $sale->total, 2) . '</td>
                </tr>
                ' . ($advanceAdjusted > 0 ? '
                <tr>
                    <td><strong>Advance Payment:-</strong></td>
                    <td class="amt" style="font-weight:bold; color:#198754;">- ' . number_format($advanceAdjusted, 2) . '</td>
                </tr>
                <tr>
                    <td><strong>Remaining Payable:-</strong></td>
                    <td class="amt" style="font-weight:bold;">' . number_format($remainingPayable, 2) . '</td>
                </tr>
                ' : '') . '
                ' . ($sale->customer_id ? '
                <tr>
                    <td>Previous Bal :-</td>
                    <td class="amt">' . number_format($previousBalance, 2) . '</td>
                </tr>
                ' : '') . '
                ' . ($cashPaidToday > 0 ? '
                <tr>
                    <td>' . ($currentDue > 0 ? 'Paid Today:-' : 'Paid:-') . '</td>
                    <td class="amt">' . number_format($cashPaidToday, 2) . '</td>
                </tr>
                ' : '') . '
            </table>

            <hr class="dotted-line">

            <table class="totals-table">
                <tr class="grand-row">
                    <td>' . ($sale->customer_id ? 'Total Net Due:-' : ($currentDue > 0 ? 'Remaining Due:-' : 'Change:-')) . '</td>
                    <td class="amt">' . ($sale->customer_id ? number_format($totalNetDue, 2) : ($currentDue > 0 ? number_format($currentDue, 2) : number_format(max(0, $cashPaidToday - $remainingPayable), 2))) . '</td>
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