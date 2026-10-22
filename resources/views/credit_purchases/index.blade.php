@extends('layouts.app')
@section('title', 'Credit Purchases & Supplier Installments')
@section('page-title', 'Credit Purchases & Installments')

@push('styles')
<style>
    .credit-kpi-card {
        border-radius: 12px;
        border: 1px solid #e9edf2;
        background: #ffffff;
        padding: 18px 20px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
    }
    .credit-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .credit-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }
    .badge-status-partial {
        background-color: #fff8e6;
        color: #b78103;
        border: 1px solid #ffeeba;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }
    .badge-status-due {
        background-color: #fdf2f2;
        color: #dc3545;
        border: 1px solid #f8d7da;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }
    .badge-status-paid {
        background-color: #edfcf5;
        color: #0f8a55;
        border: 1px solid #c7f1dd;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }
    .filter-tab-btn {
        padding: 7px 16px;
        border-radius: 8px;
        font-size: 0.88rem;
        font-weight: 500;
        color: #556270;
        background: #f1f4f8;
        border: 1px solid transparent;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    .filter-tab-btn:hover {
        background: #e2e8f0;
        color: #293134;
    }
    .filter-tab-btn.active {
        background: #ff6e40;
        color: #ffffff;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(255, 110, 64, 0.25);
    }
    .due-countdown-badge {
        font-size: 0.75rem;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 600;
        display: inline-block;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <!-- KPI Metrics Row -->
    <div class="row g-3 mb-4">
        <!-- 1. Pending Supplier Orders -->
        <div class="col-xl-3 col-sm-6">
            <div class="credit-kpi-card d-flex align-items-center justify-content-between shadow-sm border-start border-4 border-warning">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Pending Supplier Orders</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalCreditPurchasesCount) }}</div>
                    <div class="small text-muted mt-1">Purchases with remaining balance</div>
                </div>
                <div class="credit-kpi-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>

        <!-- 2. Total Credit Billed -->
        <div class="col-xl-3 col-sm-6">
            <div class="credit-kpi-card d-flex align-items-center justify-content-between shadow-sm border-start border-4 border-primary">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Credit Billed</div>
                    <div class="fs-4 fw-bold text-dark mt-1">Rs. {{ number_format($totalCreditBilled) }}</div>
                    <div class="small text-muted mt-1">Gross total of credit purchases</div>
                </div>
                <div class="credit-kpi-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
        </div>

        <!-- 3. Total Paid to Suppliers -->
        <div class="col-xl-3 col-sm-6">
            <div class="credit-kpi-card d-flex align-items-center justify-content-between shadow-sm border-start border-4 border-success">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Paid to Suppliers</div>
                    <div class="fs-4 fw-bold text-success mt-1">Rs. {{ number_format($totalCreditPaid) }}</div>
                    <div class="small text-muted mt-1">Installments cleared</div>
                </div>
                <div class="credit-kpi-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>

        <!-- 4. Outstanding Payables -->
        <div class="col-xl-3 col-sm-6">
            <div class="credit-kpi-card d-flex align-items-center justify-content-between shadow-sm border-start border-4 border-danger">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Outstanding Payables</div>
                    <div class="fs-4 fw-bold text-danger mt-1">Rs. {{ number_format($totalOutstandingDue) }}</div>
                    <div class="small text-muted mt-1">
                        @if($totalOverdueCount > 0)
                            <span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> {{ $totalOverdueCount }} overdue</span>
                        @else
                            <span class="text-success fw-semibold"><i class="bi bi-check-all"></i> All within due date</span>
                        @endif
                    </div>
                </div>
                <div class="credit-kpi-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-exclamation-diamond"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 14px;">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <!-- Status Filter Tabs -->
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('credit-purchases.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}" 
                       class="filter-tab-btn {{ $status === 'pending' ? 'active' : '' }}">
                        <i class="bi bi-hourglass-split"></i> Pending Due
                        @if($totalCreditPurchasesCount > 0)
                            <span class="badge {{ $status === 'pending' ? 'bg-white text-dark' : 'bg-warning text-dark' }} ms-1">
                                {{ $totalCreditPurchasesCount }}
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('credit-purchases.index', array_merge(request()->except('page'), ['status' => 'overdue'])) }}" 
                       class="filter-tab-btn {{ $status === 'overdue' ? 'active' : '' }}">
                        <i class="bi bi-calendar-x"></i> Overdue
                        @if($totalOverdueCount > 0)
                            <span class="badge {{ $status === 'overdue' ? 'bg-white text-danger' : 'bg-danger text-white' }} ms-1">
                                {{ $totalOverdueCount }}
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('credit-purchases.index', array_merge(request()->except('page'), ['status' => 'all'])) }}" 
                       class="filter-tab-btn {{ $status === 'all' ? 'active' : '' }}">
                        <i class="bi bi-list-check"></i> All Purchases
                    </a>

                    <a href="{{ route('credit-purchases.index', array_merge(request()->except('page'), ['status' => 'cleared'])) }}" 
                       class="filter-tab-btn {{ $status === 'cleared' ? 'active' : '' }}">
                        <i class="bi bi-check2-circle"></i> Cleared (Paid)
                    </a>
                </div>

                <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-plus-lg"></i> Record Purchase
                </a>
            </div>

            <!-- Search & Date Filter Form -->
            <form method="GET" action="{{ route('credit-purchases.index') }}" class="row g-2 align-items-center mt-3 pt-3 border-top">
                <input type="hidden" name="status" value="{{ $status }}">

                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" 
                               placeholder="Search Reference # or Supplier..." value="{{ $search ?? '' }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted">From</span>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate ?? '' }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted">To</span>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate ?? '' }}">
                    </div>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark flex-grow-1">Filter</button>
                    @if($search || $startDate || $endDate)
                        <a href="{{ route('credit-purchases.index', ['status' => $status]) }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Purchases Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Reference #</th>
                            <th>Supplier Name</th>
                            <th>Purchase Date</th>
                            <th>Due Date</th>
                            <th class="text-end">Total Billed</th>
                            <th class="text-end">Paid Amount</th>
                            <th class="text-end">Balance Due</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            @php
                                $isOverdue = $purchase->due_date && $purchase->due_date->isPast() && $purchase->due_amount > 0;
                                $daysDiff = $purchase->due_date ? (int) now()->startOfDay()->diffInDays($purchase->due_date->startOfDay(), false) : null;
                            @endphp
                            <tr>
                                <!-- Reference # -->
                                <td class="ps-4">
                                    <span class="fw-bold font-monospace text-dark d-flex align-items-center gap-1">
                                        <i class="bi bi-file-earmark-text text-primary"></i>
                                        {{ $purchase->reference_no }}
                                    </span>
                                    <small class="text-muted">{{ $purchase->items->count() }} {{ Str::plural('item', $purchase->items->count()) }}</small>
                                </td>

                                <!-- Supplier Name -->
                                <td>
                                    <div class="fw-semibold text-dark">{{ $purchase->supplier_name ?: 'General Supplier' }}</div>
                                    <small class="text-muted">Vendor</small>
                                </td>

                                <!-- Purchase Date -->
                                <td>
                                    <div class="text-dark">{{ $purchase->purchase_date->format('d M, Y') }}</div>
                                    <small class="text-muted">{{ $purchase->purchase_date->diffForHumans() }}</small>
                                </td>

                                <!-- Due Date -->
                                <td>
                                    @if($purchase->due_date)
                                        <div class="fw-semibold {{ $isOverdue ? 'text-danger' : 'text-dark' }}">
                                            {{ $purchase->due_date->format('d M, Y') }}
                                        </div>
                                        @if($purchase->due_amount > 0)
                                            @if($isOverdue)
                                                <span class="due-countdown-badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                                    <i class="bi bi-exclamation-circle me-1"></i>{{ abs($daysDiff) }}d Overdue
                                                </span>
                                            @elseif($daysDiff === 0)
                                                <span class="due-countdown-badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">
                                                    Due Today
                                                </span>
                                            @else
                                                <span class="due-countdown-badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                                    {{ $daysDiff }}d Left
                                                </span>
                                            @endif
                                        @else
                                            <span class="due-countdown-badge bg-success bg-opacity-10 text-success">Settled</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>

                                <!-- Total Billed -->
                                <td class="text-end fw-bold text-dark">
                                    Rs. {{ number_format($purchase->total) }}
                                </td>

                                <!-- Paid Amount -->
                                <td class="text-end fw-semibold text-success">
                                    Rs. {{ number_format($purchase->paid_amount) }}
                                    @if($purchase->payments->count() > 1)
                                        <div class="small text-muted" style="font-size:0.75rem;">
                                            ({{ $purchase->payments->count() }} installments)
                                        </div>
                                    @endif
                                </td>

                                <!-- Balance Due -->
                                <td class="text-end fw-bold {{ $purchase->due_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                    Rs. {{ number_format($purchase->due_amount) }}
                                </td>

                                <!-- Status Badge -->
                                <td class="text-center">
                                    @if($purchase->payment_status === 'paid')
                                        <span class="badge-status-paid d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-check-circle-fill"></i> Paid
                                        </span>
                                    @elseif($purchase->payment_status === 'partial')
                                        <span class="badge-status-partial d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-hourglass-split"></i> Partial Due
                                        </span>
                                    @else
                                        <span class="badge-status-due d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-exclamation-circle-fill"></i> Unpaid (Due)
                                        </span>
                                    @endif
                                </td>

                                <!-- Action Buttons -->
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        @if($purchase->due_amount > 0)
                                            <button type="button" class="btn btn-outline-success fw-semibold px-2 py-1"
                                                    onclick="openPaymentModal({{ $purchase->id }}, '{{ $purchase->reference_no }}', '{{ addslashes($purchase->supplier_name ?: 'General Supplier') }}', {{ $purchase->due_amount }})"
                                                    title="Record Supplier Payment">
                                                <i class="bi bi-plus-circle me-1"></i> Pay
                                            </button>
                                        @endif

                                        <button type="button" class="btn btn-outline-secondary px-2 py-1" 
                                                onclick="openHistoryModal({{ $purchase->id }})" title="View Payment History / Ledger">
                                            <i class="bi bi-clock-history"></i> Ledger
                                        </button>

                                        <a href="{{ route('credit-purchases.receipt', $purchase->id) }}" target="_blank" 
                                           class="btn btn-outline-primary px-2 py-1" title="Print Supplier Voucher">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-check fs-1 d-block mb-2 text-secondary"></i>
                                    <h5>No purchase records found matching your filter.</h5>
                                    <p class="small text-muted mb-0">Try changing the status tabs or clearing search criteria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($purchases->hasPages())
            <div class="card-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Showing {{ $purchases->firstItem() }} to {{ $purchases->lastItem() }} of {{ $purchases->total() }} purchases
                </div>
                <div>
                    {{ $purchases->links() }}
                </div>
            </div>
        @endif
    </div>

</div>

<!-- ============================================================== -->
<!-- MODAL 1: RECORD SUPPLIER INSTALLMENT PAYMENT                    -->
<!-- ============================================================== -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 14px 14px 0 0;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="paymentModalLabel">
                    <i class="bi bi-cash-stack text-success"></i> Pay Supplier Installment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="paymentForm" onsubmit="submitInstallmentPayment(event)">
                <div class="modal-body p-4">
                    <input type="hidden" id="modalPurchaseId">

                    <!-- Info Box -->
                    <div class="p-3 rounded-3 mb-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Reference No:</span>
                            <span class="fw-bold font-monospace text-dark" id="modalReferenceNo">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Supplier:</span>
                            <span class="fw-semibold text-dark" id="modalSupplierName">-</span>
                        </div>
                        <div class="d-flex justify-content-between pt-2 border-top">
                            <span class="fw-bold text-danger">Outstanding Balance:</span>
                            <span class="fw-bold text-danger fs-5" id="modalCurrentDue">Rs. 0.00</span>
                        </div>
                    </div>

                    <!-- Payment Amount Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Installment Amount (Rs.) <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="inputPaymentAmount" class="form-control form-control-lg fw-bold text-end" 
                               step="0.01" min="1" required placeholder="0.00">
                    </div>

                    <!-- Payment Date & Method -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" id="inputPaymentDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Payment Method <span class="text-danger">*</span></label>
                            <select id="selectPaymentMethod" class="form-select" required>
                                <option value="cash" selected>Cash</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="easypaisa">EasyPaisa</option>
                                <option value="jazzcash">JazzCash</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Payment Note / Voucher #</label>
                        <input type="text" id="inputPaymentNote" class="form-control" placeholder="e.g. Installment #1 paid via online bank">
                    </div>

                    <div id="paymentModalError" class="text-danger small"></div>
                </div>

                <div class="modal-footer border-top py-3 px-4" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-semibold" id="btnSavePayment">
                        <span id="paymentSpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                        <i class="bi bi-check2-circle me-1" id="paymentIcon"></i> Save &amp; Print Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 2: INSTALLMENT HISTORY & SUPPLIER LEDGER                 -->
<!-- ============================================================== -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 14px 14px 0 0;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="historyModalLabel">
                    <i class="bi bi-clock-history text-primary"></i> Installments &amp; Supplier Payment Ledger
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Purchase Summary Box -->
                <div class="row g-3 mb-4 p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                    <div class="col-sm-3">
                        <div class="text-muted small">Reference #</div>
                        <div class="fw-bold font-monospace" id="histReferenceNo">-</div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small">Supplier</div>
                        <div class="fw-bold" id="histSupplierName">-</div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small">Order Total</div>
                        <div class="fw-bold text-dark fs-6" id="histTotal">Rs. 0</div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small">Remaining Balance</div>
                        <div class="fw-bold text-danger fs-6" id="histDue">Rs. 0</div>
                    </div>
                </div>

                <!-- Payments Table -->
                <h6 class="fw-bold text-dark mb-2">Payment Installments Paid to Supplier:</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Date</th>
                                <th>Amount Paid</th>
                                <th>Method</th>
                                <th>Note / Voucher #</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody id="historyPaymentsBody">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>

                <!-- Items Table -->
                <h6 class="fw-bold text-dark mb-2 mt-4">Order Items Purchased:</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product Name</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="historyItemsBody">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-top py-3 px-4" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                <a href="#" target="_blank" class="btn btn-outline-primary" id="btnHistPrintStatement">
                    <i class="bi bi-printer me-1"></i> Print Voucher PDF
                </a>
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentPurchaseMaxDue = 0;

    function openPaymentModal(purchaseId, refNo, supplierName, dueAmount) {
        document.getElementById('modalPurchaseId').value = purchaseId;
        document.getElementById('modalReferenceNo').innerText = refNo;
        document.getElementById('modalSupplierName').innerText = supplierName;
        document.getElementById('modalCurrentDue').innerText = 'Rs. ' + dueAmount.toLocaleString(undefined, {minimumFractionDigits: 2});

        const amountInput = document.getElementById('inputPaymentAmount');
        amountInput.value = dueAmount;
        amountInput.max = dueAmount;
        currentPurchaseMaxDue = dueAmount;

        document.getElementById('inputPaymentNote').value = '';
        document.getElementById('paymentModalError').innerText = '';

        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
    }

    function submitInstallmentPayment(e) {
        e.preventDefault();

        const purchaseId = document.getElementById('modalPurchaseId').value;
        const amount = parseFloat(document.getElementById('inputPaymentAmount').value);
        const paymentDate = document.getElementById('inputPaymentDate').value;
        const paymentMethod = document.getElementById('selectPaymentMethod').value;
        const note = document.getElementById('inputPaymentNote').value;
        const errorBox = document.getElementById('paymentModalError');

        errorBox.innerText = '';

        if (isNaN(amount) || amount <= 0) {
            errorBox.innerText = 'Please enter a valid payment amount.';
            return;
        }

        if (amount > currentPurchaseMaxDue) {
            errorBox.innerText = `Payment cannot exceed the remaining due of Rs. ${currentPurchaseMaxDue.toLocaleString()}`;
            return;
        }

        const btn = document.getElementById('btnSavePayment');
        const spinner = document.getElementById('paymentSpinner');
        const icon = document.getElementById('paymentIcon');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');

        fetch(`/credit-purchases/${purchaseId}/payments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                amount: amount,
                payment_date: paymentDate,
                payment_method: paymentMethod,
                note: note,
            }),
        })
        .then(async (res) => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Error recording payment.');

            // Open updated installment receipt
            if (data.receipt_url) {
                window.open(data.receipt_url, '_blank');
            }

            if (typeof toastr !== 'undefined') {
                toastr.success(data.message || 'Payment recorded successfully!');
            }

            setTimeout(() => {
                window.location.reload();
            }, 800);
        })
        .catch(err => {
            errorBox.innerText = err.message;
            btn.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');
        });
    }

    function openHistoryModal(purchaseId) {
        fetch(`/credit-purchases/${purchaseId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                const purchase = data.purchase;

                document.getElementById('histReferenceNo').innerText = purchase.reference_no;
                document.getElementById('histSupplierName').innerText = purchase.supplier_name;
                document.getElementById('histTotal').innerText = 'Rs. ' + purchase.total.toLocaleString();
                document.getElementById('histDue').innerText = 'Rs. ' + purchase.due_amount.toLocaleString();
                document.getElementById('btnHistPrintStatement').href = `/credit-purchases/${purchase.id}/receipt`;

                // Render Payments
                const pBody = document.getElementById('historyPaymentsBody');
                pBody.innerHTML = '';
                if (purchase.payments.length === 0) {
                    pBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No payments recorded.</td></tr>';
                } else {
                    purchase.payments.forEach((p, idx) => {
                        pBody.innerHTML += `
                            <tr>
                                <td>${idx + 1}</td>
                                <td>${p.payment_date}</td>
                                <td class="fw-bold text-success">Rs. ${p.amount.toLocaleString()}</td>
                                <td>${p.payment_method}</td>
                                <td>${p.note}</td>
                                <td class="small text-muted">${p.paid_by}</td>
                            </tr>
                        `;
                    });
                }

                // Render Items
                const iBody = document.getElementById('historyItemsBody');
                iBody.innerHTML = '';
                purchase.items.forEach(item => {
                    iBody.innerHTML += `
                        <tr>
                            <td>${item.product_name}</td>
                            <td class="text-center">${item.quantity}</td>
                            <td class="text-end">Rs. ${item.price.toLocaleString()}</td>
                            <td class="text-end fw-semibold">Rs. ${item.total.toLocaleString()}</td>
                        </tr>
                    `;
                });

                const modal = new bootstrap.Modal(document.getElementById('historyModal'));
                modal.show();
            })
            .catch(err => {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to load installment details.');
                }
            });
    }
</script>
@endpush

