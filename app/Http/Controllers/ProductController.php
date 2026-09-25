<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $products = Product::with('category')->select('products.*');

            return datatables()->of($products)
                ->addColumn('image', function ($data) {
                    if ($data->image && Storage::disk('public')->exists($data->image)) {
                        return '<img src="' . asset('storage/' . $data->image) . '" class="rounded border" width="44" height="44" style="object-fit:cover;" alt="' . e($data->name) . '">';
                    }
                    return '<div class="d-inline-flex align-items-center justify-content-center rounded border bg-light text-muted" style="width:44px; height:44px;"><i class="bi bi-box-seam fs-5 text-secondary"></i></div>';
                })
                ->addColumn('name_with_code', function ($data) {
                    $codeBadge = $data->code ? '<span class="badge bg-light text-dark font-monospace border ms-1">#' . e($data->code) . '</span>' : '';
                    return '<div><span class="fw-semibold text-dark">' . e($data->name) . '</span> ' . $codeBadge . '</div>';
                })
                ->addColumn('category', function ($data) {
                    return $data->category ? '<span class="badge bg-light text-secondary border px-2 py-1">' . e($data->category->name) . '</span>' : '<span class="text-muted">—</span>';
                })
                ->filterColumn('category', function ($query, $keyword) {
                    $query->whereHas('category', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('purchase_price', function ($data) {
                    return '<span class="text-muted">Rs. ' . number_format($data->purchase_price) . '</span>';
                })
                ->filterColumn('purchase_price', function ($query, $keyword) {
                    $query->where('purchase_price', 'like', "%{$keyword}%");
                })
                ->addColumn('sale_price', function ($data) {
                    return '<span class="fw-bold text-dark">Rs. ' . number_format($data->sale_price) . '</span>';
                })
                ->filterColumn('sale_price', function ($query, $keyword) {
                    $query->where('sale_price', 'like', "%{$keyword}%");
                })
                ->addColumn('stock', function ($data) {
                    if ($data->is_low_stock || ($data->alert_quantity && $data->stock <= $data->alert_quantity)) {
                        return '<span class="badge bg-danger">Low: ' . $data->stock . '</span>';
                    } elseif ($data->stock <= 5) {
                        return '<span class="badge bg-warning text-dark">' . $data->stock . ' left</span>';
                    }
                    return '<span class="badge bg-success">' . $data->stock . ' pcs</span>';
                })
                ->filterColumn('stock', function ($query, $keyword) {
                    $query->where('stock', 'like', "%{$keyword}%");
                })
                ->addColumn('action', function ($data) {
                    $editUrl = route('products.edit', $data->id);
                    $deleteUrl = route('products.destroy', $data->id);

                    return '
                        <div class="d-flex align-items-center gap-1">
                            <a href="' . $editUrl . '" class="btn btn-sm btn-outline-primary" title="Edit Product"><i class="bi bi-pencil-square"></i></a>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-product" data-url="' . $deleteUrl . '" data-name="' . e($data->name) . '" title="Delete Product"><i class="bi bi-trash"></i></button>
                        </div>
                    ';
                })
                ->rawColumns(['image', 'name_with_code', 'category', 'purchase_price', 'sale_price', 'stock', 'action'])
                ->make(true);
        }

        $totalProducts = Product::count();
        $lowStockCount = Product::lowStock()->count();
        $totalStockUnits = Product::sum('stock');
        $totalCategories = Category::count();

        return view('products.index', compact('totalProducts', 'lowStockCount', 'totalStockUnits', 'totalCategories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('products.create', compact('categories'));
    }

    private function rules(?Product $product = null): array
    {
        $codeUniqueRule = 'unique:products,code' . ($product ? ',' . $product->id : '');

        return [
            'name'            => ['required', 'string', 'max:255'],
            'code'            => ['required', 'string', 'max:100', $codeUniqueRule],
            'category_id'     => ['required', 'exists:categories,id'],
            'purchase_price'  => ['required', 'numeric', 'min:1', 'max:99999'],
            'sale_price'      => ['required', 'numeric', 'min:1', 'max:99999'],
            'stock'           => ['required', 'integer', 'min:1', 'max:99999'],
            'alert_quantity'  => ['required', 'integer', 'min:1'],
            'image'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product added successfully.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate($this->rules($product));

        if ($request->hasFile('image')) {
            // Remove previous image from storage if present
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = null;
        }

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }
}