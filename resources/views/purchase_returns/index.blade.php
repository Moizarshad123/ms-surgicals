@extends('layouts.app')
@section('title', 'Purchase Returns')
@section('page-title', 'Purchase Returns')

@push('styles')
<style>
    .return-kpi-card {
        border-radius: 12px;
        border: 1px solid #e9edf2;
        background: #ffffff;
        padding: 18px 20px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
    }
    .return-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .return-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <!-- KPI Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-sm-6">
            <div class="return-kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Purchase Returns</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalReturnsCount) }}</div>
                    <div class="small text-muted mt-1">Processed returns to vendors</div>
                </div>
                <div class="return-kpi-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-arrow-return-left"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-sm-6">
            <div class="return-kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Returned Value</div>
                    <div class="fs-4 fw-bold text-danger mt-1">Rs. {{ number_format($totalReturnsValue) }}</div>
                    <div class="small text-muted mt-1">Merchandise returned to suppliers</div>
                </div>
                <div class="return-kpi-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-box-arrow-left"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-sm-12">
            <div class="return-kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Units Returned</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalUnitsReturned) }} pcs</div>
                    <div class="small text-muted mt-1">Stock deducted from inventory</div>
                </div>
                <div class="return-kpi-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold text-dark mb-0">Purchase Returns History</h5>
                    <span class="badge bg-light text-secondary border px-2">{{ $returns->total() }} records</span>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <form action="{{ route('purchase-returns.index') }}" method="GET" class="d-flex flex-wrap align-items-center gap-2">
                        <div class="input-group input-group-sm" style="width: 260px;">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" value="{{ $search }}" class="form-control border-start-0" placeholder="Return #, PO ref, supplier...">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary px-3">Search</button>
                        @if($search || request('start_date') || request('end_date'))
                            <a href="{{ route('purchase-returns.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                        @endif
                    </form>

                    <a href="{{ route('purchase-returns.create') }}" class="btn btn-sm btn-warning fw-semibold px-3 d-flex align-items-center gap-1">
                        <i class="bi bi-plus-circle"></i> Create Purchase Return
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3 py-3" style="width: 140px;">Return #</th>
                            <th>Original PO Ref</th>
                            <th>Supplier Name</th>
                            <th>Return Date</th>
                            <th class="text-center">Items Returned</th>
                            <th class="text-end">Total Amount</th>
                            <th>Reason</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $ret)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                        {{ $ret->return_no }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-primary font-monospace">
                                        {{ $ret->purchase->reference_no ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $ret->supplier_name ?: 'General Supplier' }}</div>
                                </td>
                                <td>
                                    <div class="small text-dark">{{ $ret->return_date->format('d M, Y') }}</div>
                                    <div class="small text-muted">{{ $ret->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-secondary border px-2 py-1">
                                        {{ $ret->items->count() }} items ({{ $ret->items->sum('quantity') }} pcs)
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    Rs. {{ number_format($ret->total_amount) }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        {{ $ret->reason ?: 'Supplier Return' }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary py-1 px-2" onclick="showPurchaseReturnDetails({{ $ret->id }})" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <a target="_blank" href="{{ route('purchase-returns.receipt', $ret->id) }}" class="btn btn-outline-primary py-1 px-2" title="Print Return Slip">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger py-1 px-2" onclick="confirmDeletePurchaseReturn({{ $ret->id }}, '{{ $ret->return_no }}')" title="Delete Purchase Return & Restore Stock">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-arrow-return-left fs-1 d-block mb-2 text-secondary"></i>
                                    No purchase returns recorded yet.
                                    <div class="mt-2">
                                        <a href="{{ route('purchase-returns.create') }}" class="btn btn-sm btn-outline-warning">Process First Purchase Return</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($returns->hasPages())
            <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Showing {{ $returns->firstItem() }} to {{ $returns->lastItem() }} of {{ $returns->total() }} returns
                </div>
                <div>
                    {{ $returns->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal: Purchase Return Details -->
<div class="modal fade" id="purchaseReturnDetailsModal" tabindex="-1" aria-labelledby="purchaseReturnDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0" id="purchaseReturnDetailsModalLabel">
                        <i class="bi bi-arrow-return-left text-warning me-1"></i> Purchase Return Details
                    </h6>
                    <small class="text-muted" id="modalReturnNoBadge"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modalLoadingSpinner" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>

                <div id="modalContentBody" class="d-none">
                    <!-- Summary info row -->
                    <div class="row g-3 mb-3 p-3 bg-light rounded-3">
                        <div class="col-sm-3 col-6">
                            <small class="text-muted d-block">Original PO Ref</small>
                            <span class="fw-bold font-monospace" id="mOrigRef"></span>
                        </div>
                        <div class="col-sm-3 col-6">
                            <small class="text-muted d-block">Supplier</small>
                            <span class="fw-semibold" id="mSupplierName"></span>
                        </div>
                        <div class="col-sm-3 col-6">
                            <small class="text-muted d-block">Return Date</small>
                            <span class="fw-semibold" id="mReturnDate"></span>
                        </div>
                        <div class="col-sm-3 col-6">
                            <small class="text-muted d-block">Processed By</small>
                            <span class="fw-semibold" id="mProcessedBy"></span>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Returned Product</th>
                                    <th class="text-center" style="width: 100px;">Qty</th>
                                    <th class="text-end" style="width: 120px;">Unit Price</th>
                                    <th class="text-end" style="width: 130px;">Total</th>
                                </tr>
                            </thead>
                            <tbody id="mItemsTableBody"></tbody>
                        </table>
                    </div>

                    <!-- Total Return Value -->
                    <div class="p-3 rounded-3 text-center" style="background:#fffaf8; border:1px dashed #ffb899;">
                        <small class="text-muted d-block">Total Returned Merchandise Value</small>
                        <span class="fs-4 fw-bold text-danger" id="mTotalAmount">Rs. 0</span>
                    </div>

                    <div class="mt-3">
                        <small class="text-muted d-block">Reason / Note:</small>
                        <div class="text-dark small" id="mReasonNotes"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <a id="modalPrintSlipBtn" target="_blank" href="#" class="btn btn-sm btn-primary">
                    <i class="bi bi-printer me-1"></i> Print Return Slip
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function showPurchaseReturnDetails(id) {
        const modal = new bootstrap.Modal(document.getElementById('purchaseReturnDetailsModal'));
        modal.show();

        const spinner = document.getElementById('modalLoadingSpinner');
        const content = document.getElementById('modalContentBody');
        spinner.classList.remove('d-none');
        content.classList.add('d-none');

        fetch(`/purchase-returns/${id}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error('Failed to load return details');
                const r = data.return;

                document.getElementById('modalReturnNoBadge').innerText = r.return_no;
                document.getElementById('mOrigRef').innerText = r.reference_no;
                document.getElementById('mSupplierName').innerText = r.supplier_name;
                document.getElementById('mReturnDate').innerText = r.return_date;
                document.getElementById('mProcessedBy').innerText = r.processed_by;

                document.getElementById('mTotalAmount').innerText = 'Rs. ' + r.total_amount.toLocaleString(undefined, {minimumFractionDigits: 2});
                document.getElementById('mReasonNotes').innerText = (r.reason || '—') + (r.notes && r.notes !== '—' ? ' (' + r.notes + ')' : '');

                document.getElementById('modalPrintSlipBtn').href = `/purchase-returns/${r.id}/receipt`;

                const tbody = document.getElementById('mItemsTableBody');
                tbody.innerHTML = '';
                r.items.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <div class="fw-semibold text-dark">${item.product_name}</div>
                            ${item.product_code ? '<small class="text-muted font-monospace">#' + item.product_code + '</small>' : ''}
                        </td>
                        <td class="text-center fw-bold">${item.quantity}</td>
                        <td class="text-end">Rs. ${item.price.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end fw-semibold">Rs. ${item.total.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    `;
                    tbody.appendChild(tr);
                });

                spinner.classList.add('d-none');
                content.classList.remove('d-none');
            })
            .catch(err => {
                spinner.innerHTML = `<div class="text-danger">${err.message}</div>`;
            });
    }

    function confirmDeletePurchaseReturn(returnId, returnNo) {
        if (!confirm(`Are you sure you want to delete Purchase Return ${returnNo}?\n\nThis will permanently delete this return record and restore the returned units back into product inventory.`)) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/purchase-returns/${returnId}`;

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

