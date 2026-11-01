@extends('layouts.app')
@section('title', 'Purchase Order ' . $purchaseOrder->po_number)
@section('page-title', 'Purchase Order Details')

@push('styles')
<style>
    .form-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .info-tile {
        background: #f8fafd;
        border: 1px solid #edf2f7;
        border-radius: 10px;
        padding: 16px;
    }
    .info-tile .lbl {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #8c98a4;
        margin-bottom: 4px;
    }
    .info-tile .val {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
    }
    #poShowTable th {
        font-weight: 600;
        font-size: 0.82rem;
        color: #556270;
        background: #fbfbfc;
        border-bottom: 2px solid #edf1f6;
    }
    #poShowTable td {
        vertical-align: middle;
        font-size: 0.88rem;
    }
    .btn-theme-primary {
        background: #ff6e40;
        border-color: #ff6e40;
        color: #ffffff;
        font-weight: 500;
        transition: all 0.15s ease;
    }
    .btn-theme-primary:hover {
        background: #f25b2a;
        border-color: #f25b2a;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(255, 110, 64, 0.25);
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .badge-ordered   { background: rgba(37, 194, 227, 0.12); color: #0284c7; border: 1px solid rgba(37, 194, 227, 0.25); }
    .badge-draft     { background: #e2e3e5; color: #41464b; border: 1px solid #d3d6d8; }
    .badge-received  { background: rgba(5, 177, 113, 0.12); color: #059669; border: 1px solid rgba(5, 177, 113, 0.25); }
    .badge-cancelled { background: rgba(234, 68, 68, 0.12); color: #dc2626; border: 1px solid rgba(234, 68, 68, 0.25); }
</style>
@endpush

@section('content')

{{-- Header Banner Card --}}
<div class="card form-card mb-4">
    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 p-2 text-white d-flex align-items-center justify-content-center" style="background: #ff6e40; width: 44px; height: 44px;">
                <i class="bi bi-clipboard2-check fs-4"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold text-dark mb-0">Purchase Order: {{ $purchaseOrder->po_number }}</h5>
                    <span class="status-badge badge-{{ $purchaseOrder->status }}">{{ $purchaseOrder->status }}</span>
                </div>
                <div class="text-muted small">
                    Supplier: <strong>{{ $purchaseOrder->supplier_name }}</strong> &bull; Date: {{ $purchaseOrder->order_date->format('d M, Y') }}
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('purchase-orders.print', $purchaseOrder) }}" target="_blank" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-printer"></i> Print PDF
            </a>
            <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

{{-- Info Tiles --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="info-tile">
            <div class="lbl">PO Number</div>
            <div class="val text-primary font-monospace">{{ $purchaseOrder->po_number }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-tile">
            <div class="lbl">Supplier Name</div>
            <div class="val">{{ $purchaseOrder->supplier_name }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-tile">
            <div class="lbl">Order Date</div>
            <div class="val">{{ $purchaseOrder->order_date->format('d M, Y') }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-tile">
            <div class="lbl">Total Amount</div>
            <div class="val" style="color: #ff6e40;">Rs. {{ number_format((float)$purchaseOrder->total_amount, 2) }}</div>
        </div>
    </div>
</div>

{{-- Order Items Table --}}
<div class="card form-card mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
            <i class="bi bi-boxes" style="color: #ff6e40;"></i> Order Items ({{ $purchaseOrder->items->count() }})
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="poShowTable">
                <thead>
                    <tr>
                        <th class="text-center ps-3" style="width:50px">#</th>
                        <th>Product</th>
                        <th class="text-center" style="width:120px">Packaging Type</th>
                        <th class="text-center" style="width:120px">Quantity</th>
                        <th class="text-end" style="width:150px">Unit Price</th>
                        <th class="text-end pe-3" style="width:160px">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchaseOrder->items as $i => $item)
                    <tr>
                        <td class="text-center text-muted ps-3">{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $item->product->name ?? 'N/A' }}</div>
                            @if($item->product?->code)
                                <div class="text-muted small">Code: {{ $item->product->code }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1">{{ $item->product_type }}</span>
                        </td>
                        <td class="text-center fw-semibold">
                            {{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }}
                        </td>
                        <td class="text-end">Rs. {{ number_format((float)$item->unit_price, 2) }}</td>
                        <td class="text-end pe-3 fw-bold text-dark">Rs. {{ number_format((float)$item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #fbfbfc; border-top: 2px solid #edf1f6;">
                        <td colspan="5" class="text-end fw-bold py-3">Grand Total:</td>
                        <td class="text-end pe-3 fw-bold fs-6 py-3" style="color: #ff6e40;">
                            Rs. {{ number_format((float)$purchaseOrder->total_amount, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@endsection
