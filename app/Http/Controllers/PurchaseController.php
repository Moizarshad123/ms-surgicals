<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
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

        return view('purchases.create', compact('products'));
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
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated) {
            $total = 0;
            foreach ($validated['items'] as $item) {
                $total += $item['quantity'] * $item['price'];
            }

            $paidInput = isset($validated['paid_amount']) ? (float) $validated['paid_amount'] : $total;
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

            $lastId  = Purchase::lockForUpdate()->latest('id')->value('id') ?? 0;
            $orderNo = str_pad($lastId + 1, 3, '0', STR_PAD_LEFT); // 001, 002, 003 ...

            $purchase = Purchase::create([
                'reference_no' => 'PUR-' . now()->format('Ymd') . '-' . $orderNo,
                'supplier_name' => $validated['supplier_name'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'total' => $total,
                'paid_amount' => $paidAmount,
                'due_date' => $dueDate,
                'payment_status' => $paymentStatus,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($paidAmount > 0) {
                PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'amount' => $paidAmount,
                    'payment_date' => $validated['purchase_date'],
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'note' => $paidAmount < $total ? 'Initial Advance / Down Payment' : 'Full Payment on Order',
                    'user_id' => auth()->id(),
                ]);
            }

            foreach ($validated['items'] as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
                ]);

                Product::find($item['product_id'])->increment('stock', $item['quantity']);
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
            $total = 0;
            foreach ($validated['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['price'];
                $total += $lineTotal;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $lineTotal,
                ]);

                Product::where('id', $item['product_id'])->increment('stock', $item['quantity']);
            }

            // Recalculate payment status based on current paid_amount vs new total
            $paid = (float) $purchase->paid_amount;
            $paymentStatus = ($paid >= $total) ? 'paid' : ($paid > 0 ? 'partial' : 'due');
            $dueDate = ($paymentStatus === 'paid') ? null : ($validated['due_date'] ?? $purchase->due_date);

            // 4. Update purchase header
            $purchase->update([
                'supplier_name' => $validated['supplier_name'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'total' => $total,
                'payment_status' => $paymentStatus,
                'due_date' => $dueDate,
                'notes' => $validated['notes'] ?? $purchase->notes,
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
