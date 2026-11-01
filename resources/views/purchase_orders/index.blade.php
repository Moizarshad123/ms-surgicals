@extends('layouts.app')
@section('title', 'Purchase Orders')
@section('page-title', 'Purchase Orders')

@push('styles')
<style>
    .kpi-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .kpi-icon {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
    .po-table-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    #poTable th {
        font-weight: 600;
        font-size: 0.85rem;
        color: #556270;
        background: #fbfbfc;
        border-bottom: 2px solid #edf1f6;
    }
    #poTable td {
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
    .search-group {
        position: relative;
    }
    .search-group .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #8c98a4;
        font-size: 0.85rem;
        pointer-events: none;
    }
    .search-group input {
        padding-left: 32px;
        border-radius: 8px;
        border: 1px solid #e0e6ed;
    }
    .search-group input:focus {
        border-color: #ff6e40;
        box-shadow: 0 0 0 3px rgba(255, 110, 64, 0.15);
    }
    .badge-ordered   { background: rgba(37, 194, 227, 0.12); color: #0284c7; border: 1px solid rgba(37, 194, 227, 0.25); }
    .badge-draft     { background: #e2e3e5; color: #41464b; border: 1px solid #d3d6d8; }
    .badge-received  { background: rgba(5, 177, 113, 0.12); color: #059669; border: 1px solid rgba(5, 177, 113, 0.25); }
    .badge-cancelled { background: rgba(234, 68, 68, 0.12); color: #dc2626; border: 1px solid rgba(234, 68, 68, 0.25); }
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
    .supplier-avatar {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #f0f3f6;
        color: #556270;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
    }
</style>
@endpush

@section('content')
<!-- KPI Stat Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Total Purchase Orders</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalOrders ?? $orders->total()) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(255,110,64,0.1); color: #ff6e40;">
                    <i class="bi bi-clipboard2-check"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Total Orders Value</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($totalAmount ?? 0, 2) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(37,194,227,0.1); color: #25c2e3;">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Ordered / Pending</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($orderedCount ?? 0) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(255,193,7,0.1); color: #f59e0b;">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Received Orders</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($receivedCount ?? 0) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(5,177,113,0.1); color: #05b171;">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Purchase Orders Table Card -->
<div class="card po-table-card">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-clipboard2-check text-primary"></i> All Purchase Orders
            </h5>
            <div class="text-muted small">Showing <span id="visiblePoCount">{{ $orders->count() }}</span> purchase orders on this page</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Live Search -->
            <div class="search-group" style="width: 250px;">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="poSearchInput" class="form-control form-control-sm" placeholder="Search PO # or supplier...">
            </div>
            <a href="{{ route('purchase-orders.create') }}" class="btn btn-sm btn-theme-primary px-3 d-flex align-items-center gap-1">
                <i class="bi bi-plus-lg"></i> New Purchase Order
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        @if ($orders->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-clipboard2-x" style="font-size:3rem; color: #cbd5e1;"></i>
                <h6 class="mt-3 fw-bold text-dark">No Purchase Orders Found</h6>
                <p class="text-muted small mb-3">Create your first purchase order to send to suppliers.</p>
                <a href="{{ route('purchase-orders.create') }}" class="btn btn-sm btn-theme-primary px-3">
                    <i class="bi bi-plus-lg me-1"></i> New Purchase Order
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="poTable">
                    <thead>
                        <tr>
                            <th style="width: 160px;">PO #</th>
                            <th>Supplier</th>
                            <th>Order Date</th>
                            <th class="text-center">Items</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-center">Status</th>
                            <th class="text-end" style="width: 170px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="poTableBody">
                        @foreach ($orders as $order)
                            <tr class="po-row" data-search="{{ strtolower($order->po_number . ' ' . $order->supplier_name) }}">
                                <td>
                                    <span class="badge bg-light text-dark font-monospace border px-2 py-1" style="font-size: 0.85rem;">
                                        <i class="bi bi-hash text-muted"></i>{{ $order->po_number }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="supplier-avatar">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $order->supplier_name }}</div>
                                            <div class="text-muted small" style="font-size: 0.75rem;">Supplier</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-dark fw-medium">{{ $order->order_date->format('d M, Y') }}</div>
                                    <div class="text-muted small" style="font-size: 0.75rem;">{{ $order->order_date->diffForHumans() }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border px-2 py-1" style="background: rgba(255,110,64,0.1); color: #ff6e40 !important;">
                                        <i class="bi bi-box-seam me-1"></i> {{ $order->items->count() }} {{ \Illuminate\Support\Str::plural('item', $order->items->count()) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold text-dark fs-6">Rs. {{ number_format((float)$order->total_amount, 2) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="status-badge badge-{{ $order->status }}">
                                        @if($order->status === 'received')
                                            <i class="bi bi-check-circle"></i>
                                        @elseif($order->status === 'ordered')
                                            <i class="bi bi-send"></i>
                                        @elseif($order->status === 'cancelled')
                                            <i class="bi bi-x-circle"></i>
                                        @else
                                            <i class="bi bi-file-earmark"></i>
                                        @endif
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-outline-primary" title="View Order">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('purchase-orders.print', $order) }}" target="_blank" class="btn btn-outline-secondary" title="Print PDF">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <a href="{{ route('purchase-orders.edit', $order) }}" class="btn btn-outline-warning" title="Edit Order">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('purchase-orders.destroy', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this purchase order?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Delete Order">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="p-3 border-top">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('poSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const term = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('.po-row');
                let count = 0;

                rows.forEach(function (row) {
                    const searchData = row.getAttribute('data-search') || '';
                    if (!term || searchData.includes(term)) {
                        row.style.display = '';
                        count++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                const countEl = document.getElementById('visiblePoCount');
                if (countEl) countEl.textContent = count;
            });
        }
    });
</script>
@endpush
