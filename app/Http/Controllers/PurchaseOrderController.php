<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierAdvance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    /**
     * Display list of all purchase orders.
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with('items')->latest();

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $orders = $query->paginate(20)->withQueryString();

        $totalOrders   = PurchaseOrder::count();
        $totalAmount   = (float) PurchaseOrder::sum('total_amount');
        $orderedCount  = PurchaseOrder::where('status', 'ordered')->count();
        $receivedCount = PurchaseOrder::where('status', 'received')->count();

        return view('purchase_orders.index', compact(
            'orders',
            'totalOrders',
            'totalAmount',
            'orderedCount',
            'receivedCount'
        ));
    }

    /**
     * Show form to create a new purchase order.
     */
    public function create()
    {
        $products  = Product::orderBy('name')->get();
        $suppliers = $this->suppliersList();

        return view('purchase_orders.create', compact('products', 'suppliers'));
    }

    /**
     * Store a new purchase order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_name'            => ['required', 'string', 'max:255'],
            'order_date'               => ['required', 'date'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.product_id'       => ['required', 'exists:products,id'],
            'items.*.product_type'     => ['required', 'in:CTN,BOX,PC'],
            'items.*.quantity'         => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'       => ['required', 'numeric', 'min:0'],
        ]);

        $order = DB::transaction(function () use ($validated) {
            $poNumber    = PurchaseOrder::generatePoNumber();
            $totalAmount = 0;

            $order = PurchaseOrder::create([
                'po_number'     => $poNumber,
                'supplier_name' => $validated['supplier_name'],
                'order_date'    => $validated['order_date'],
                'status'        => 'ordered',
                'total_amount'  => 0,
                'user_id'       => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $qty      = (float) $item['quantity'];
                $price    = (float) $item['unit_price'];
                $subtotal = round($qty * $price, 2);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id'        => $item['product_id'],
                    'product_type'      => $item['product_type'],
                    'quantity'          => $qty,
                    'unit_price'        => $price,
                    'subtotal'          => $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            $order->update(['total_amount' => round($totalAmount, 2)]);

            return $order;
        });

        return redirect()
            ->route('purchase-orders.index')
            ->with('success', "Purchase Order {$order->po_number} created successfully.");
    }

    /**
     * Show a single purchase order (detail view).
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('items.product');

        return view('purchase_orders.show', compact('purchaseOrder'));
    }

    /**
     * Show edit form for an existing purchase order.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('items.product');
        $products  = Product::orderBy('name')->get();
        $suppliers = $this->suppliersList();

        return view('purchase_orders.edit', compact('purchaseOrder', 'products', 'suppliers'));
    }

    /**
     * Update an existing purchase order.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'supplier_name'            => ['required', 'string', 'max:255'],
            'order_date'               => ['required', 'date'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.product_id'       => ['required', 'exists:products,id'],
            'items.*.product_type'     => ['required', 'in:CTN,BOX,PC'],
            'items.*.quantity'         => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'       => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $purchaseOrder) {
            $purchaseOrder->update([
                'supplier_name' => $validated['supplier_name'],
                'order_date'    => $validated['order_date'],
            ]);

            // Delete old items and re-insert
            $purchaseOrder->items()->delete();

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $qty      = (float) $item['quantity'];
                $price    = (float) $item['unit_price'];
                $subtotal = round($qty * $price, 2);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id'        => $item['product_id'],
                    'product_type'      => $item['product_type'],
                    'quantity'          => $qty,
                    'unit_price'        => $price,
                    'subtotal'          => $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            $purchaseOrder->update(['total_amount' => round($totalAmount, 2)]);
        });

        return redirect()
            ->route('purchase-orders.index')
            ->with('success', "Purchase Order {$purchaseOrder->po_number} updated successfully.");
    }

    /**
     * Delete a purchase order.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();

        return redirect()
            ->route('purchase-orders.index')
            ->with('success', 'Purchase order deleted successfully.');
    }

    /**
     * Stream an A4 PDF print of the purchase order.
     */
    public function print(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('items.product');

        $logoBase64 = $this->getReceiptLogoBase64();
        $logoHtml   = $logoBase64
            ? '<img src="data:image/png;base64,' . $logoBase64 . '" style="height:55px; width:auto;">'
            : '<div style="font-size:20px; font-weight:bold; color:#1d3557;">TG SURGICALS</div>';

        $rowsHtml = '';
        foreach ($purchaseOrder->items as $i => $item) {
            $rowsHtml .= '
            <tr>
                <td style="text-align:center;">' . ($i + 1) . '</td>
                <td style="text-align:left;">' . e($item->product->name ?? 'N/A') . '</td>
                <td style="text-align:center; font-weight:600;">' . $item->product_type . '</td>
                <td style="text-align:center;">' . rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') . '</td>
                <td style="text-align:right;">Rs. ' . number_format((float)$item->unit_price, 2) . '</td>
                <td style="text-align:right; font-weight:600;">Rs. ' . number_format((float)$item->subtotal, 2) . '</td>
            </tr>';
        }

        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #222; padding: 28px 36px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 3px solid #1d3557; padding-bottom: 14px; }
    .shop-info h1 { font-size: 22px; font-weight: 800; color: #1d3557; margin-bottom: 2px; }
    .shop-info p { font-size: 11px; color: #555; }
    .po-badge { text-align: right; }
    .po-badge .po-label { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
    .po-badge .po-num { font-size: 20px; font-weight: 800; color: #e63946; }
    .meta-grid { display: flex; gap: 32px; margin-bottom: 20px; background: #f8f9fa; border-radius: 8px; padding: 12px 16px; }
    .meta-item .meta-label { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
    .meta-item .meta-val { font-size: 13px; font-weight: 700; color: #1d3557; margin-top: 2px; }
    .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #457b9d; margin-bottom: 8px; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    table.items thead tr { background: #1d3557; color: #fff; }
    table.items thead th { padding: 8px 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    table.items tbody tr { border-bottom: 1px solid #e9ecef; }
    table.items tbody tr:nth-child(even) { background: #f8f9fa; }
    table.items td { padding: 8px 10px; font-size: 12px; vertical-align: middle; }
    .total-row { display: flex; justify-content: flex-end; margin-top: 8px; }
    .total-box { background: #1d3557; color: #fff; border-radius: 8px; padding: 12px 24px; min-width: 220px; }
    .total-box .t-label { font-size: 11px; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; }
    .total-box .t-amount { font-size: 22px; font-weight: 800; margin-top: 2px; }
    .footer { margin-top: 40px; display: flex; justify-content: space-between; font-size: 11px; color: #888; border-top: 1px solid #dee2e6; padding-top: 12px; }
    .sig-line { border-top: 1px solid #aaa; width: 160px; margin-top: 30px; padding-top: 4px; font-size: 10px; color: #555; }
    .status-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: #d4edda; color: #155724; }
</style>
</head>
<body>
    <div class="header">
        <div class="shop-info">
            ' . $logoHtml . '
            <h1 style="margin-top:6px;">TG SURGICALS</h1>
            <p>Purchase Order Document</p>
        </div>
        <div class="po-badge">
            <div class="po-label">Purchase Order</div>
            <div class="po-num">' . e($purchaseOrder->po_number) . '</div>
            <div style="margin-top:6px;"><span class="status-badge">' . e($purchaseOrder->status) . '</span></div>
        </div>
    </div>

    <div class="meta-grid">
        <div class="meta-item">
            <div class="meta-label">Supplier</div>
            <div class="meta-val">' . e($purchaseOrder->supplier_name) . '</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Order Date</div>
            <div class="meta-val">' . $purchaseOrder->order_date->format('d M Y') . '</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Items</div>
            <div class="meta-val">' . $purchaseOrder->items->count() . '</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Printed On</div>
            <div class="meta-val">' . now()->format('d M Y') . '</div>
        </div>
    </div>

    <div class="section-title">Order Items</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:5%; text-align:center;">#</th>
                <th style="width:38%; text-align:left;">Product</th>
                <th style="width:10%; text-align:center;">Type</th>
                <th style="width:12%; text-align:center;">Qty</th>
                <th style="width:17%; text-align:right;">Unit Price</th>
                <th style="width:18%; text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            ' . $rowsHtml . '
        </tbody>
    </table>

    <div class="total-row">
        <div class="total-box">
            <div class="t-label">Grand Total (PKR)</div>
            <div class="t-amount">Rs. ' . number_format((float)$purchaseOrder->total_amount, 2) . '</div>
        </div>
    </div>

    <div class="footer">
        <div>
            <div class="sig-line">Prepared By</div>
        </div>
        <div>
            <div class="sig-line">Authorised Signature</div>
        </div>
        <div>
            <div class="sig-line">Supplier Acknowledgement</div>
        </div>
    </div>
</body>
</html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        return $pdf->stream('PO-' . $purchaseOrder->po_number . '.pdf');
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    private function suppliersList(): \Illuminate\Support\Collection
    {
        return Purchase::whereNotNull('supplier_name')
            ->where('supplier_name', '!=', '')
            ->distinct()
            ->pluck('supplier_name')
            ->merge(SupplierAdvance::pluck('supplier_name'))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}

