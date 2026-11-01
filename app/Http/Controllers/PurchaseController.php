<?php

namespace App\Http\Controllers;

use App\Models\BusinessCapital;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\BankTransaction;
use App\Models\SupplierAdvance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with(['items.product', 'payments'])->withCount('items')->latest('purchase_date')->paginate(15);
        $totalSpent = (float) Purchase::sum('total');
        $totalPurchases = Purchase::count();
        $totalUnitsPurchased = PurchaseItem::sum('quantity');
        $totalDue = (float) Purchase::whereIn('payment_status', ['partial', 'due'])->sum(DB::raw('total - paid_amount'));
        $thisMonthSpent = (float) Purchase::whereMonth('purchase_date', now()->month)
            ->whereYear('purchase_date', now()->year)
            ->sum('total');

        return view('purchases.index', compact(
            'purchases',
            'totalSpent',
            'totalPurchases',
            'totalUnitsPurchased',
            'totalDue',
            'thisMonthSpent'
        ));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();

        // Retrieve existing suppliers and their advance balances
        $suppliers = Purchase::whereNotNull('supplier_name')
            ->where('supplier_name', '!=', '')
            ->distinct()
            ->pluck('supplier_name')
            ->merge(SupplierAdvance::pluck('supplier_name'))
            ->filter()
            ->unique()
            ->values()
            ->map(function ($name) {
                $adv = SupplierAdvance::where('supplier_name', $name)->first();
                return [
                    'name' => $name,
                    'advance_balance' => $adv ? (float) $adv->advance_balance : 0.0,
                ];
            });

        // Calculate available Cash in Hand for purchasing stock
        $totalCashCapital = (float) BusinessCapital::sum('amount');
        $cashSalesInflow = (float) SalePayment::where('payment_method', 'cash')->sum('amount');
        $cashWithdrawalsFromBank = (float) BankTransaction::where('type', 'debit')
            ->where('is_capital_transfer', false)
            ->where(function ($q) {
                $q->where('category', 'like', '%cash withdrawal%')
                  ->orWhere('description', 'like', '%cash withdrawal%');
            })->sum('amount');

        $cashDepositsToBank = (float) BankTransaction::where('type', 'credit')
            ->where('is_capital_transfer', false)
            ->where(function ($q) {
                $q->where('category', 'like', '%cash deposit%')
                  ->orWhere('description', 'like', '%cash deposit%');
            })->sum('amount');

        $capitalTransfersToBank = (float) BankTransaction::where('is_capital_transfer', true)
            ->where('type', 'credit')
            ->sum('amount');

        $cashPurchases = (float) PurchasePayment::where('payment_method', 'cash')->sum('amount');
        $cashExpenses = (float) Expense::where(function ($q) {
            $q->where('payment_method', 'cash')->orWhereNull('payment_method');
        })->sum('amount');
        $cashRefunds = (float) SaleReturn::sum('refund_amount');

        $cashInHand = ($totalCashCapital + $cashSalesInflow + $cashWithdrawalsFromBank)
                    - ($cashPurchases + $cashExpenses + $cashDepositsToBank + $capitalTransfersToBank + $cashRefunds);
        $cashInHand = round($cashInHand, 2);

        return view('purchases.create', compact('products', 'suppliers', 'cashInHand', 'totalCashCapital'));
    }

    public function getSupplierAdvance(Request $request)
    {
        $name = trim($request->get('name', ''));
        $advanceBalance = 0.0;
        if (!empty($name)) {
            $record = SupplierAdvance::where('supplier_name', $name)->first();
            $advanceBalance = $record ? (float) $record->advance_balance : 0.0;
        }

        return response()->json([
            'success' => true,
            'supplier_name' => $name,
            'advance_balance' => $advanceBalance,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated) {
            $subTotal = 0;
            $totalDiscount = 0;
            $total = 0;
            $computedItems = [];

            foreach ($validated['items'] as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['price'];
                $discountPercent = isset($item['discount_percent']) && is_numeric($item['discount_percent'])
                    ? (float) $item['discount_percent']
                    : 0.0;

                // Step 1: Calculate discount per unit = price * discount%
                $discountPerUnit = round($price * ($discountPercent / 100), 4);
                // Step 2: Calculate discounted unit price = price - discount per unit
                $discountedPrice = max(0, round($price - $discountPerUnit, 4));
                // Step 3: Calculate final subtotal = discounted unit price * qty
                $lineTotal = round($discountedPrice * $qty, 2);
                $lineRawTotal = round($price * $qty, 2);
                $lineDiscountAmount = max(0, round($lineRawTotal - $lineTotal, 2));

                $subTotal += $lineRawTotal;
                $totalDiscount += $lineDiscountAmount;
                $total += $lineTotal;

                $computedItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'price' => $price,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $lineDiscountAmount,
                    'total' => $lineTotal,
                ];
            }

            $paidInput = isset($validated['paid_amount']) ? (float) $validated['paid_amount'] : 0.0;
            $supplierName = trim($validated['supplier_name'] ?? '');

            // Check if supplier has available advance
            $advanceRecord = null;
            $advanceAvailable = 0.0;
            if (!empty($supplierName)) {
                $advanceRecord = SupplierAdvance::where('supplier_name', $supplierName)->lockForUpdate()->first();
                $advanceAvailable = $advanceRecord ? (float) $advanceRecord->advance_balance : 0.0;
            }

            // Deduct available advance up to invoice total
            $advanceToApply = min($advanceAvailable, $total);
            $netPayable = max(0, round($total - $advanceToApply, 2));

            // If manual payment exceeds net payable, excess is stored as supplier advance
            $excessPaid = max(0, round($paidInput - $netPayable, 2));
            $paidToApply = min($paidInput, $netPayable);
            $totalEffectivePaid = round($advanceToApply + $paidToApply, 2);

            // Deduct applied advance from supplier_advances
            if ($advanceToApply > 0 && $advanceRecord) {
                $advanceRecord->decrement('advance_balance', $advanceToApply);
            }

            // If excess was paid, store into supplier advance
            if ($excessPaid > 0 && !empty($supplierName)) {
                $suppAdv = SupplierAdvance::firstOrCreate(
                    ['supplier_name' => $supplierName],
                    ['advance_balance' => 0]
                );
                $suppAdv->increment('advance_balance', $excessPaid);
            }

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

            $lastId  = Purchase::lockForUpdate()->latest('id')->value('id') ?? 0;
            $orderNo = str_pad($lastId + 1, 3, '0', STR_PAD_LEFT); // 001, 002, 003 ...

            $purchase = Purchase::create([
                'reference_no' => 'PUR-' . now()->format('Ymd') . '-' . $orderNo,
                'supplier_name' => !empty($supplierName) ? $supplierName : null,
                'sub_total' => $subTotal,
                'discount' => $totalDiscount,
                'total' => $total,
                'paid_amount' => $totalEffectivePaid,
                'advance_adjusted' => $advanceToApply,
                'purchase_date' => $validated['purchase_date'],
                'due_date' => $dueDate,
                'payment_status' => $paymentStatus,
                'notes' => $validated['notes'] ?? null,
            ]);

            // 1. Record advance deduction payment if applied
            if ($advanceToApply > 0) {
                PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'amount' => $advanceToApply,
                    'payment_date' => $validated['purchase_date'],
                    'payment_method' => 'advance_adjustment',
                    'note' => 'Auto-adjusted from Supplier Advance Balance (Rs. ' . number_format($advanceToApply, 2) . ')',
                    'user_id' => auth()->id(),
                ]);
            }

            // 2. Record manual paid payment
            if ($paidInput > 0) {
                PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'amount' => $paidInput,
                    'payment_date' => $validated['purchase_date'],
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'note' => $excessPaid > 0
                        ? 'Payment at Purchase (Includes Rs. ' . number_format($excessPaid, 2) . ' stored as advance)'
                        : ($totalEffectivePaid < $total ? 'Initial Advance / Down Payment' : 'Full Payment on Order'),
                    'user_id' => auth()->id(),
                ]);
            }

            foreach ($computedItems as $computed) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $computed['product_id'],
                    'quantity' => $computed['quantity'],
                    'price' => $computed['price'],
                    'discount_percent' => $computed['discount_percent'],
                    'discount_amount' => $computed['discount_amount'],
                    'total' => $computed['total'],
                ]);

                Product::find($computed['product_id'])->increment('stock', $computed['quantity']);
            }
        });

        return redirect()->route('purchases.index')->with('success', 'Purchase recorded and stock updated.');
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load(['items.product']);
        $products = Product::orderBy('name')->get();

        return view('purchases.edit', compact('purchase', 'products'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated, $purchase) {
            // 1. Revert previous purchase items from stock
            foreach ($purchase->items as $oldItem) {
                Product::where('id', $oldItem->product_id)->decrement('stock', $oldItem->quantity);
            }

            // 2. Delete old items
            $purchase->items()->delete();

            // 3. Insert updated items and increment stock
            $subTotal = 0;
            $totalDiscount = 0;
            $total = 0;

            foreach ($validated['items'] as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['price'];
                $discountPercent = isset($item['discount_percent']) && is_numeric($item['discount_percent'])
                    ? (float) $item['discount_percent']
                    : 0.0;

                // Step 1: Calculate discount per unit = price * discount%
                $discountPerUnit = round($price * ($discountPercent / 100), 4);
                // Step 2: Calculate discounted unit price = price - discount per unit
                $discountedPrice = max(0, round($price - $discountPerUnit, 4));
                // Step 3: Calculate final subtotal = discounted unit price * qty
                $lineTotal = round($discountedPrice * $qty, 2);
                $lineRawTotal = round($price * $qty, 2);
                $lineDiscountAmount = max(0, round($lineRawTotal - $lineTotal, 2));

                $subTotal += $lineRawTotal;
                $totalDiscount += $lineDiscountAmount;
                $total += $lineTotal;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'price' => $price,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $lineDiscountAmount,
                    'total' => $lineTotal,
                ]);

                Product::where('id', $item['product_id'])->increment('stock', $qty);
            }

            // Recalculate payment status based on current paid_amount vs new total
            $rawPaid = (float) $purchase->paid_amount;
            $paid = min($rawPaid, (float) $total);
            $paymentStatus = ($paid >= $total) ? 'paid' : ($paid > 0 ? 'partial' : 'due');
            $dueDate = ($paymentStatus === 'paid') ? null : ($validated['due_date'] ?? $purchase->due_date);

            // If new total is less than what was previously recorded as paid, credit excess to supplier advance
            $overpaidExcess = max(0, round($rawPaid - (float) $total, 2));
            if ($overpaidExcess > 0 && !empty($validated['supplier_name'])) {
                $suppAdv = SupplierAdvance::firstOrCreate(
                    ['supplier_name' => $validated['supplier_name']],
                    ['advance_balance' => 0]
                );
                $suppAdv->increment('advance_balance', $overpaidExcess);
            }

            // 4. Update purchase header
            $purchase->update([
                'supplier_name'  => $validated['supplier_name'] ?? null,
                'purchase_date'  => $validated['purchase_date'],
                'sub_total'      => $subTotal,
                'discount'       => $totalDiscount,
                'total'          => $total,
                'paid_amount'    => $paid,
                'payment_status' => $paymentStatus,
                'due_date'       => $dueDate,
                'notes'          => $validated['notes'] ?? $purchase->notes,
            ]);
        });

        return redirect()->route('purchases.index')->with('success', 'Purchase updated and inventory adjusted successfully.');
    }

    public function destroy(Purchase $purchase)
    {
        if ($purchase->returns()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete this purchase because it has purchase returns attached. Please delete the returns first.');
        }

        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                Product::where('id', $item->product_id)->decrement('stock', $item->quantity);
            }

            $purchase->items()->delete();
            $purchase->delete();
        });

        return redirect()->back()->with('success', 'Purchase deleted and inventory adjusted successfully.');
    }
}
