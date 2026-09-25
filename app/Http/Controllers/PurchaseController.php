<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with(['items.product'])->withCount('items')->latest('purchase_date')->paginate(15);
        $totalSpent = Purchase::sum('total');
        $totalPurchases = Purchase::count();
        $totalUnitsPurchased = PurchaseItem::sum('quantity');
        $thisMonthSpent = Purchase::whereMonth('purchase_date', now()->month)
            ->whereYear('purchase_date', now()->year)
            ->sum('total');

        return view('purchases.index', compact(
            'purchases',
            'totalSpent',
            'totalPurchases',
            'totalUnitsPurchased',
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
        ]);

        DB::transaction(function () use ($validated) {
            $total = 0;
            foreach ($validated['items'] as $item) {
                $total += $item['quantity'] * $item['price'];
            }

            $purchase = Purchase::create([
                'reference_no' => 'PUR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
                'supplier_name' => $validated['supplier_name'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'total' => $total,
            ]);

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
}
