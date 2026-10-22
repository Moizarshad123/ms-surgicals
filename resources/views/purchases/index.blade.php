@extends('layouts.app')
@section('title', 'Purchases')
@section('page-title', 'Purchases')

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
    .purchases-table-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    #purchasesTable th {
        font-weight: 600;
        font-size: 0.85rem;
        color: #556270;
        background: #fbfbfc;
        border-bottom: 2px solid #edf1f6;
    }
    #purchasesTable td {
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
        font-size: 0.95rem;
        pointer-events: none;
    }
    .search-group input {
        padding-left: 36px;
        border-radius: 8px;
        border: 1px solid #dfe5ec;
        font-size: 0.88rem;
    }
    .search-group input:focus {
        border-color: #ff6e40;
        box-shadow: 0 0 0 3px rgba(255, 110, 64, 0.15);
    }
    .supplier-avatar {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: #f0f3f6;
        color: #556270;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
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
                    <div class="text-muted small fw-semibold">Total Purchases Cost</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($totalSpent) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(255,110,64,0.1); color: #ff6e40;">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">This Month's Purchases</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($thisMonthSpent) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(37,194,227,0.1); color: #25c2e3;">
                    <i class="bi bi-calendar-check"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Purchase Invoices</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalPurchases) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(5,177,113,0.1); color: #05b171;">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Total Units Restocked</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalUnitsPurchased) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(108,92,231,0.1); color: #6c5ce7;">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Purchases Table Card -->
<div class="card purchases-table-card">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-credit-card-2-front text-primary"></i> Purchases History
            </h5>
            <div class="text-muted small">Showing <span id="visiblePurchaseCount">{{ $purchases->count() }}</span> purchase records on this page</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Live Search -->
            <div class="search-group" style="width: 250px;">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="purchaseSearchInput" class="form-control form-control-sm" placeholder="Search reference, supplier...">
            </div>
            <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-theme-primary px-3 d-flex align-items-center gap-1">
                <i class="bi bi-plus-lg"></i> New Purchase
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="purchasesTable">
                <thead>
                    <tr>
                        <th style="width: 170px;">Reference No</th>
                        <th>Supplier</th>
                        <th>Purchase Date</th>
                        <th>Items Count</th>
                        <th>Grand Total</th>
                        <th>Payment Status</th>
                        <th class="text-end" style="width: 140px;">Details</th>
                    </tr>
                </thead>
                <tbody id="purchasesTableBody">
                    @forelse ($purchases as $purchase)
                        @php
                            $itemsPayload = $purchase->items->map(function($it) {
                                return [
                                    'product' => $it->product ? $it->product->name : 'Deleted Product',
                                    'code' => $it->product ? $it->product->code : '-',
                                    'quantity' => $it->quantity,
                                    'price' => (float) $it->price,
                                    'total' => (float) $it->total,
                                ];
                            });
                        @endphp
                        <tr class="purchase-row" data-search="{{ strtolower($purchase->reference_no . ' ' . ($purchase->supplier_name ?? '') . ' ' . $purchase->purchase_date) }}">
                            <td>
                                <span class="badge bg-light text-dark font-monospace border px-2 py-1" style="font-size: 0.85rem;">
                                    <i class="bi bi-hash text-muted"></i>{{ $purchase->reference_no }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="supplier-avatar">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $purchase->supplier_name ?: 'General Supplier' }}</div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">Verified Vendor</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-dark fw-medium">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M, Y') }}</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ \Carbon\Carbon::parse($purchase->purchase_date)->diffForHumans() }}</div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border px-2 py-1" style="background: rgba(255,110,64,0.1); color: #ff6e40 !important;">
                                    <i class="bi bi-box-seam me-1"></i> {{ $purchase->items_count }} {{ Str::plural('item', $purchase->items_count) }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-dark fs-6">Rs. {{ number_format($purchase->total) }}</span>
                            </td>
                            <td>
                                @if($purchase->payment_status === 'paid')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> Paid
                                    </span>
                                @elseif($purchase->payment_status === 'partial')
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1" title="Paid: Rs. {{ number_format($purchase->paid_amount) }}">
                                        <i class="bi bi-hourglass-split me-1"></i> Partial (Due: Rs. {{ number_format($purchase->due_amount) }})
                                    </span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i> Unpaid (Rs. {{ number_format($purchase->due_amount) }})
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @if($purchase->due_amount > 0)
                                        <a href="{{ route('credit-purchases.index', ['search' => $purchase->reference_no]) }}" class="btn btn-outline-success" title="Pay Installment">
                                            <i class="bi bi-plus-circle"></i> Pay
                                        </a>
                                    @endif
                                    <button type="button" class="btn btn-outline-primary btn-view-purchase"
                                            data-ref="{{ $purchase->reference_no }}"
                                            data-supplier="{{ $purchase->supplier_name ?: 'General Supplier' }}"
                                            data-date="{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M, Y') }}"
                                            data-total="Rs. {{ number_format($purchase->total) }}"
                                            data-items='@json($itemsPayload)'>
                                        <i class="bi bi-eye me-1"></i> View
                                    </button>
                                    <a href="{{ route('purchases.edit', $purchase->id) }}" class="btn btn-outline-info" title="Edit Purchase">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <a href="{{ route('purchase-returns.create', ['purchase_id' => $purchase->id]) }}" class="btn btn-outline-warning" title="Return to Supplier">
                                        <i class="bi bi-arrow-return-left"></i> Return
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete Purchase & Deduct Stock" onclick="confirmDeletePurchase({{ $purchase->id }}, '{{ $purchase->reference_no }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                No purchases recorded yet. Click "New Purchase" to add your first stock order.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- No Search Results Row -->
        <div id="noSearchMatch" class="py-5 text-center text-muted d-none">
            <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
            <h6 class="fw-semibold text-dark">No matching purchases found</h6>
            <p class="small mb-0">Try searching with a different reference number or supplier name.</p>
        </div>
    </div>

    @if ($purchases->hasPages())
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-end">
            {{ $purchases->links() }}
        </div>
    @endif
</div>

<!-- PURCHASE DETAILS MODAL -->
<div class="modal fade" id="purchaseDetailModal" tabindex="-1" aria-labelledby="purchaseDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 12px 12px 0 0;">
                <div>
                    <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="purchaseDetailModalLabel">
                        <i class="bi bi-receipt text-primary"></i> Purchase Invoice Details
                    </h6>
                    <div class="text-muted small mt-1" id="modalRefNo">REF</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Meta Info Badges -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="text-muted small">Supplier</div>
                            <div class="fw-bold text-dark mt-1" id="modalSupplier">-</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="text-muted small">Purchase Date</div>
                            <div class="fw-bold text-dark mt-1" id="modalDate">-</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="text-muted small">Invoice Total</div>
                            <div class="fw-bold text-success fs-6 mt-1" id="modalTotal">-</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-2">Purchased Items</h6>
                <div class="table-responsive border rounded-3">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="modalItemsBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4" style="background:#fafbfc; border-radius: 0 0 12px 12px;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        // -------------------------------------------------------------
        // Live Search Filter for Purchases
        // -------------------------------------------------------------
        $('#purchaseSearchInput').on('input', function () {
            const query = $(this).val().toLowerCase().trim();
            let visibleCount = 0;

            $('.purchase-row').each(function () {
                const searchData = $(this).data('search') || '';
                if (!query || searchData.includes(query)) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });

            $('#visiblePurchaseCount').text(visibleCount);
            if (visibleCount === 0 && $('.purchase-row').length > 0) {
                $('#noSearchMatch').removeClass('d-none');
            } else {
                $('#noSearchMatch').addClass('d-none');
            }
        });

        // -------------------------------------------------------------
        // Open Purchase Details Modal
        // -------------------------------------------------------------
        $('.btn-view-purchase').on('click', function () {
            const ref = $(this).data('ref');
            const supplier = $(this).data('supplier');
            const date = $(this).data('date');
            const total = $(this).data('total');
            const items = $(this).data('items') || [];

            $('#modalRefNo').text('Invoice Reference: ' + ref);
            $('#modalSupplier').text(supplier);
            $('#modalDate').text(date);
            $('#modalTotal').text(total);

            let rows = '';
            items.forEach((item, index) => {
                rows += `
                    <tr>
                        <td class="text-muted small">${index + 1}</td>
                        <td class="fw-semibold text-dark">${item.product}</td>
                        <td class="text-end">Rs. ${parseFloat(item.price)}</td>
                        <td class="text-center"><span class="badge bg-secondary-subtle text-dark border px-2">${item.quantity}</span></td>
                        <td class="text-end fw-bold text-dark">Rs. ${parseFloat(item.total)}</td>
                    </tr>
                `;
            });

            $('#modalItemsBody').html(rows);

            const modal = new bootstrap.Modal(document.getElementById('purchaseDetailModal'));
            modal.show();
        });
    });

    function confirmDeletePurchase(purchaseId, refNo) {
        if (!confirm(`Are you sure you want to delete Purchase ${refNo}?\n\nThis will permanently remove this purchase record and deduct the restocked quantities from product inventory.`)) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/purchases/${purchaseId}`;

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);

        document.body.appendChild(form);
        form.submit();
    }
</script>
@endpush
