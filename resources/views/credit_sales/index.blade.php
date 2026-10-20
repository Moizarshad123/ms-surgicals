@extends('layouts.app')
@section('title', 'Credit Sales & Installments')
@section('page-title', 'Credit Sales & Installments')

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
        <div class="col-xl-3 col-sm-6">
            <div class="credit-kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Pending Orders</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalCreditSalesCount) }}</div>
                    <div class="small text-muted mt-1">Orders with balance due</div>
                </div>
                <div class="credit-kpi-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="credit-kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Credit Billed</div>
                    <div class="fs-4 fw-bold text-dark mt-1">Rs. {{ number_format($totalCreditBilled) }}</div>
                    <div class="small text-muted mt-1">Gross total of credit sales</div>
                </div>
                <div class="credit-kpi-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="credit-kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Collected</div>
                    <div class="fs-4 fw-bold text-success mt-1">Rs. {{ number_format($totalCreditCollected) }}</div>
                    <div class="small text-muted mt-1">Advance + installments</div>
                </div>
                <div class="credit-kpi-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="credit-kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Remaining Outstanding</div>
                    <div class="fs-4 fw-bold text-danger mt-1">Rs. {{ number_format($totalOutstandingDue) }}</div>
                    <div class="small text-danger mt-1 fw-medium">
                        @if($totalOverdueCount > 0)
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $totalOverdueCount }} Overdue
                        @else
                            Pending recovery
                        @endif
                    </div>
                </div>
                <div class="credit-kpi-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Status Filter Tabs -->
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('credit-sales.index', array_merge(request()->query(), ['status' => 'pending'])) }}" 
                       class="filter-tab-btn {{ $status === 'pending' ? 'active' : '' }}">
                        <i class="bi bi-hourglass-split"></i> Pending ({{ $totalCreditSalesCount }})
                    </a>
                    <a href="{{ route('credit-sales.index', array_merge(request()->query(), ['status' => 'overdue'])) }}" 
                       class="filter-tab-btn {{ $status === 'overdue' ? 'active' : '' }}">
                        <i class="bi bi-exclamation-circle"></i> Overdue ({{ $totalOverdueCount }})
                    </a>
                    <a href="{{ route('credit-sales.index', array_merge(request()->query(), ['status' => 'cleared'])) }}" 
                       class="filter-tab-btn {{ $status === 'cleared' ? 'active' : '' }}">
                        <i class="bi bi-check-circle"></i> Fully Cleared
                    </a>
                    <a href="{{ route('credit-sales.index', array_merge(request()->query(), ['status' => 'all'])) }}" 
                       class="filter-tab-btn {{ $status === 'all' ? 'active' : '' }}">
                        <i class="bi bi-collection"></i> All Records
                    </a>
                </div>

                <!-- Search & Date Filter Form -->
                <form action="{{ route('credit-sales.index') }}" method="GET" class="d-flex flex-wrap align-items-center gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <div class="input-group input-group-sm" style="width: 260px;">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ $search }}" class="form-control border-start-0" placeholder="Invoice, customer, phone...">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary px-3">Filter</button>
                    @if($search || request('start_date'))
                        <a href="{{ route('credit-sales.index', ['status' => $status]) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3 py-3" style="width: 130px;">Invoice #</th>
                            <th>Customer</th>
                            <th>Sale Date</th>
                            <th>Due Date</th>
                            <th class="text-end">Total Bill</th>
                            <th class="text-end">Paid (Advance)</th>
                            <th class="text-end">Remaining Due</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            @php
                                $due = $sale->due_amount;
                                $isOverdue = $sale->due_date && $sale->due_date->isPast() && $due > 0;
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                        {{ $sale->invoice_no }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                                    <div class="small text-muted">
                                        <i class="bi bi-telephone me-1"></i>{{ $sale->customer->phone ?? '—' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-dark">{{ $sale->created_at->format('d M, Y') }}</div>
                                    <div class="small text-muted">{{ $sale->created_at->format('h:i A') }}</div>
                                </td>
                                <td>
                                    @if($sale->due_date)
                                        <div class="small fw-semibold {{ $isOverdue ? 'text-danger' : 'text-dark' }}">
                                            {{ $sale->due_date->format('d M, Y') }}
                                        </div>
                                        @if($due > 0)
                                            @if($isOverdue)
                                                <span class="due-countdown-badge bg-danger bg-opacity-10 text-danger">
                                                    Overdue by {{ abs(now()->diffInDays($sale->due_date, false)) }} days
                                                </span>
                                            @else
                                                <span class="due-countdown-badge bg-warning bg-opacity-10 text-warning text-dark">
                                                    {{ now()->diffInDays($sale->due_date) }} days left
                                                </span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold text-dark">
                                    Rs. {{ number_format($sale->total) }}
                                </td>
                                <td class="text-end text-success fw-semibold">
                                    Rs. {{ number_format($sale->paid_amount) }}
                                    @if($sale->payments->count() > 1)
                                        <div class="small text-muted">({{ $sale->payments->count() }} payments)</div>
                                    @endif
                                </td>
                                <td class="text-end fw-bold {{ $due > 0 ? 'text-danger' : 'text-muted' }}">
                                    Rs. {{ number_format($due) }}
                                </td>
                                <td class="text-center">
                                    @if($sale->payment_status === 'paid' || $due <= 0)
                                        <span class="badge-status-paid">Paid</span>
                                    @elseif($sale->payment_status === 'partial')
                                        <span class="badge-status-partial">Partial</span>
                                    @else
                                        <span class="badge-status-due">Due</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        @if($due > 0)
                                            <!-- Receive Payment Button -->
                                            <button type="button" class="btn btn-success" 
                                                    onclick="openPaymentModal({{ $sale->id }}, '{{ $sale->invoice_no }}', '{{ addslashes($sale->customer->name ?? 'Walk-in Customer') }}', {{ $due }})"
                                                    title="Receive Installment">
                                                <i class="bi bi-plus-circle me-1"></i> Pay
                                            </button>
                                        @endif

                                        <!-- History / Details Button -->
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="openHistoryModal({{ $sale->id }})"
                                                title="View Installment History">
                                            <i class="bi bi-clock-history"></i>
                                        </button>

                                        <!-- Edit Order Button -->
                                        <a href="{{ route('sales.edit', $sale->id) }}" 
                                           class="btn btn-outline-info" title="Edit Order">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <!-- Print Statement Button -->
                                        <a href="{{ route('credit-sales.receipt', $sale->id) }}" target="_blank" 
                                           class="btn btn-outline-secondary" title="Print Installment Statement">
                                            <i class="bi bi-printer"></i>
                                        </a>

                                        <!-- Sales Return Button -->
                                        <a href="{{ route('sales-returns.create', ['sale_id' => $sale->id]) }}" 
                                           class="btn btn-outline-warning" title="Return Items">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </a>
                                        <!-- Delete Sale Button -->
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDeleteSale({{ $sale->id }}, '{{ $sale->invoice_no }}')" title="Delete Sale & Restore Stock">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-folder2-open fs-1 text-secondary d-block mb-2"></i>
                                    No credit or installment sales found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($sales->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 1: RECORD INSTALLMENT PAYMENT                            -->
<!-- ============================================================== -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 14px 14px 0 0;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="paymentModalLabel">
                    <i class="bi bi-cash-coin text-success"></i> Receive Installment Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="receivePaymentForm" onsubmit="submitInstallmentPayment(event)">
                <div class="modal-body p-4">
                    <input type="hidden" id="modalSaleId">

                    <!-- Customer & Invoice Info Box -->
                    <div class="p-3 mb-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Invoice No:</span>
                            <span class="fw-bold font-monospace text-dark" id="modalInvoiceNo">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Customer:</span>
                            <span class="fw-semibold text-dark" id="modalCustomerName">-</span>
                        </div>
                        <div class="d-flex justify-content-between pt-2 border-top">
                            <span class="fw-bold text-danger">Outstanding Balance:</span>
                            <span class="fw-bold text-danger fs-5" id="modalCurrentDue">Rs. 0.0</span>
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
                        <label class="form-label small fw-semibold text-dark">Payment Note / Reference</label>
                        <input type="text" id="inputPaymentNote" class="form-control" placeholder="e.g. Installment #1, received at counter">
                    </div>

                    <div id="paymentModalError" class="text-danger small"></div>
                </div>

                <div class="modal-footer border-top py-3 px-4" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-semibold" id="btnSavePayment">
                        <span id="paymentSpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                        <i class="bi bi-check2-circle me-1" id="paymentIcon"></i> Save &amp; Print Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 2: INSTALLMENT HISTORY & LEDGER                          -->
<!-- ============================================================== -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 14px 14px 0 0;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="historyModalLabel">
                    <i class="bi bi-clock-history text-primary"></i> Installments &amp; Payment Ledger
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Sale Summary Box -->
                <div class="row g-3 mb-4 p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                    <div class="col-sm-3">
                        <div class="text-muted small">Invoice #</div>
                        <div class="fw-bold font-monospace" id="histInvoiceNo">-</div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small">Customer</div>
                        <div class="fw-bold" id="histCustomerName">-</div>
                        <div class="small text-muted" id="histCustomerPhone">-</div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small">Sale Total</div>
                        <div class="fw-bold text-dark fs-6" id="histTotal">Rs. 0</div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small">Remaining Balance</div>
                        <div class="fw-bold text-danger fs-6" id="histDue">Rs. 0</div>
                    </div>
                </div>

                <!-- Payments Table -->
                <h6 class="fw-bold text-dark mb-2">Payment Installments Received:</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm align-middle" id="historyPaymentsTable">
                        <thead class="table-light small">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Note / Description</th>
                                <th>Received By</th>
                            </tr>
                        </thead>
                        <tbody id="historyPaymentsBody">
                            <!-- Populated via JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Items Breakdown -->
                <h6 class="fw-bold text-dark mb-2">Purchased Items:</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0" id="historyItemsTable">
                        <thead class="table-light small">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody id="historyItemsBody">
                            <!-- Populated via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-top py-3 px-4" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                <a href="#" id="btnHistPrintStatement" target="_blank" class="btn btn-outline-secondary me-auto">
                    <i class="bi bi-printer me-1"></i> Print Installment Statement
                </a>
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentSaleDue = 0;

    function openPaymentModal(saleId, invoiceNo, customerName, dueAmount) {
        currentSaleDue = dueAmount;
        document.getElementById('modalSaleId').value = saleId;
        document.getElementById('modalInvoiceNo').innerText = invoiceNo;
        document.getElementById('modalCustomerName').innerText = customerName;
        document.getElementById('modalCurrentDue').innerText = 'Rs. ' + dueAmount.toLocaleString();
        
        const amountInput = document.getElementById('inputPaymentAmount');
        amountInput.value = dueAmount;
        amountInput.max = dueAmount;
        document.getElementById('paymentModalError').innerText = '';

        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
    }

    function submitInstallmentPayment(e) {
        e.preventDefault();
        const saleId = document.getElementById('modalSaleId').value;
        const amount = parseFloat(document.getElementById('inputPaymentAmount').value) || 0;
        const paymentDate = document.getElementById('inputPaymentDate').value;
        const paymentMethod = document.getElementById('selectPaymentMethod').value;
        const note = document.getElementById('inputPaymentNote').value;
        const errorBox = document.getElementById('paymentModalError');
        errorBox.innerText = '';

        if (amount <= 0) {
            errorBox.innerText = 'Please enter a valid payment amount.';
            return;
        }

        if (amount > currentSaleDue) {
            errorBox.innerText = 'Payment amount cannot exceed remaining balance (Rs. ' + currentSaleDue.toLocaleString() + ').';
            return;
        }

        const btn = document.getElementById('btnSavePayment');
        const spinner = document.getElementById('paymentSpinner');
        const icon = document.getElementById('paymentIcon');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');

        fetch(`/credit-sales/${saleId}/payments`, {
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

    function openHistoryModal(saleId) {
        fetch(`/credit-sales/${saleId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                const sale = data.sale;

                document.getElementById('histInvoiceNo').innerText = sale.invoice_no;
                document.getElementById('histCustomerName').innerText = sale.customer_name;
                document.getElementById('histCustomerPhone').innerText = sale.customer_phone;
                document.getElementById('histTotal').innerText = 'Rs. ' + sale.total.toLocaleString();
                document.getElementById('histDue').innerText = 'Rs. ' + sale.due_amount.toLocaleString();
                document.getElementById('btnHistPrintStatement').href = `/credit-sales/${sale.id}/receipt`;

                // Render Payments
                const pBody = document.getElementById('historyPaymentsBody');
                pBody.innerHTML = '';
                if (sale.payments.length === 0) {
                    pBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No payments recorded.</td></tr>';
                } else {
                    sale.payments.forEach((p, idx) => {
                        pBody.innerHTML += `
                            <tr>
                                <td>${idx + 1}</td>
                                <td>${p.payment_date}</td>
                                <td class="fw-bold text-success">Rs. ${p.amount.toLocaleString()}</td>
                                <td>${p.payment_method}</td>
                                <td>${p.note}</td>
                                <td class="small text-muted">${p.received_by}</td>
                            </tr>
                        `;
                    });
                }

                // Render Items
                const iBody = document.getElementById('historyItemsBody');
                iBody.innerHTML = '';
                sale.items.forEach(item => {
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

    function confirmDeleteSale(saleId, invoiceNo) {
        if (!confirm(`Are you sure you want to delete Sale ${invoiceNo}?\n\nThis will permanently delete this sale and restore the sold quantities back to product inventory.`)) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/sales/${saleId}`;

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

