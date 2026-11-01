@extends('layouts.app')
@section('title', 'Credit Sales — Customer Accounts')
@section('page-title', 'Credit Sales & Customer Accounts')

@push('styles')
<style>
    .kpi-card {
        border-radius: 12px; border: 1px solid #e9edf2;
        background: #ffffff; padding: 18px 20px;
        transition: transform 0.15s ease, box-shadow 0.15s ease; height: 100%;
    }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
    .kpi-icon {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
    }
    .customer-row:hover { background: #f8fafc; }
    .advance-badge {
        background: #edfcf5; color: #0f8a55; border: 1px solid #c7f1dd;
        font-weight: 600; padding: 3px 8px; border-radius: 6px; font-size: 0.8rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    {{-- KPI Row --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-sm-6">
            <div class="kpi-card d-flex align-items-center justify-content-between shadow-sm border-start border-4 border-warning">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Active Customers</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalCustomers) }}</div>
                    <div class="small text-muted mt-1">Customers with pending credit</div>
                </div>
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-people"></i></div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="kpi-card d-flex align-items-center justify-content-between shadow-sm border-start border-4 border-danger">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Pending Recovery</div>
                    <div class="fs-4 fw-bold text-danger mt-1">Rs. {{ number_format($totalOutstanding, 2) }}</div>
                    <div class="small text-muted mt-1">Total due from all customers</div>
                </div>
                <div class="kpi-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-diamond"></i></div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="kpi-card d-flex align-items-center justify-content-between shadow-sm border-start border-4 border-success">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Advance Received</div>
                    <div class="fs-4 fw-bold text-success mt-1">Rs. {{ number_format($totalAdvances, 2) }}</div>
                    <div class="small text-muted mt-1">Customer advance balances</div>
                </div>
                <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-wallet2"></i></div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card border-0 shadow-sm" style="border-radius: 14px;">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex gap-2">
                    <a href="{{ route('credit-sales.customers', ['status' => 'pending']) }}"
                       class="btn btn-sm {{ $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-secondary' }}">
                        <i class="bi bi-hourglass-split me-1"></i> Pending Due
                    </a>
                    <a href="{{ route('credit-sales.customers', ['status' => 'all']) }}"
                       class="btn btn-sm {{ $statusFilter === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}">
                        <i class="bi bi-list-check me-1"></i> All Customers
                    </a>
                    <a href="{{ route('credit-sales.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-receipt me-1"></i> All Sales (Per Invoice)
                    </a>
                </div>
                
            </div>

            {{-- Search --}}
            <form method="GET" action="{{ route('credit-sales.customers') }}" class="row g-2 align-items-center mt-3 pt-3 border-top">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Search customer name or phone..." value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark">Filter</button>
                    @if($search)
                        <a href="{{ route('credit-sales.customers', ['status' => $statusFilter]) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Customer</th>
                            <th class="text-center">Invoices</th>
                            <th class="text-end">Total Billed</th>
                            <th class="text-end">Total Paid</th>
                            <th class="text-end">Balance Due</th>
                            <th class="text-end">Advance</th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            @php
                                $totalBilled = (float)($customer->total_billed ?? 0);
                                $totalPaidSum = (float)($customer->total_paid_sum ?? 0);
                                $balanceDue = max(0, $totalBilled - $totalPaidSum);
                                $advance = (float)$customer->advance_balance;
                            @endphp
                            <tr class="customer-row">
                                <td class="ps-4">
                                    <div class="fw-bold text-dark fs-6">{{ $customer->name }}</div>
                                    @if($customer->phone)
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $customer->phone }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2 fs-6">{{ $customer->total_invoices }}</span>
                                </td>
                                <td class="text-end fw-semibold text-dark">Rs. {{ number_format($totalBilled, 2) }}</td>
                                <td class="text-end fw-semibold text-success">Rs. {{ number_format($totalPaidSum, 2) }}</td>
                                <td class="text-end fw-bold {{ $balanceDue > 0 ? 'text-danger' : 'text-success' }}">
                                    Rs. {{ number_format($balanceDue, 2) }}
                                </td>
                                <td class="text-end">
                                    @if($advance > 0)
                                        <span class="advance-badge">Rs. {{ number_format($advance, 2) }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        @if($balanceDue > 0)
                                            <button type="button" 
                                                    class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 shadow-sm px-2"
                                                    onclick="openCustomerPayModal({{ $customer->id }}, '{{ addslashes($customer->name) }}', {{ $balanceDue }})"
                                                    title="Receive Payment">
                                                <i class="bi bi-cash-stack"></i> Pay / Receive
                                            </button>
                                        @endif
                                        <a href="{{ route('credit-sales.customer-detail', $customer->id) }}"
                                           class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm px-2">
                                            <i class="bi bi-eye-fill"></i> View Details
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    No customers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
                <div class="px-4 py-3 border-top">{{ $customers->links() }}</div>
            @endif
        </div>
    </div>

<!-- ============================================================== -->
<!-- MODAL: RECEIVE CUSTOMER PAYMENT (LEDGER TOTAL BALANCE)         -->
<!-- ============================================================== -->
<div class="modal fade" id="customerPayModal" tabindex="-1" aria-labelledby="customerPayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 14px 14px 0 0;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="customerPayModalLabel">
                    <i class="bi bi-cash-stack text-success"></i> Receive Customer Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="customerPayForm" onsubmit="submitCustomerPayment(event)">
                @csrf
                <input type="hidden" id="custPayCustomerId" value="">

                <div class="modal-body p-4">
                    <!-- Info Box (Customer Wise Total Bill) -->
                    <div class="p-3 rounded-3 mb-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Customer Name:</span>
                            <span class="fw-bold text-dark fs-6" id="custPayCustomerName">-</span>
                        </div>
                        <div class="d-flex justify-content-between pt-2 border-top">
                            <span class="fw-bold text-danger">Total Outstanding Balance:</span>
                            <span class="fw-bold text-danger fs-5" id="custPayTotalDue">Rs. 0.00</span>
                        </div>
                    </div>

                    <!-- Payment Amount Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Received Amount (Rs.) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" id="custPayAmount" class="form-control form-control-lg fw-bold text-end" required placeholder="0.00">
                        <div class="form-text text-muted mt-1 small">
                            <i class="bi bi-info-circle me-1"></i>Any amount exceeding total due will be stored as customer <strong>Advance Balance</strong>.
                        </div>
                    </div>

                    <!-- Payment Date & Method -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" id="custPayDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Payment Method <span class="text-danger">*</span></label>
                            <select id="custPayMethod" class="form-select" onchange="toggleCustBankSelect(this.value)" required>
                                <option value="cash" selected>Cash (Physical Register)</option>
                                <option value="bank">Bank Transfer / Online</option>
                                <option value="easypaisa">EasyPaisa</option>
                                <option value="jazzcash">JazzCash</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bank Account Select (shows when Method is Bank) -->
                    <div class="mb-3 d-none" id="custBankSelectRow">
                        <label class="form-label small fw-semibold text-dark">
                            Destination Bank Account <span class="text-danger">*</span>
                        </label>
                        <select id="custPayBankId" class="form-select">
                            <option value="">-- Choose Bank Account --</option>
                            @foreach($banks ?? [] as $b)
                                <option value="{{ $b->id }}">{{ $b->name }} @if($b->account_title) ({{ $b->account_title }}) @endif</option>
                            @endforeach
                        </select>
                        <div class="form-text text-success small">
                            <i class="bi bi-check-circle me-1"></i>A Bank Transaction will be created automatically to increment your bank balance.
                        </div>
                    </div>

                    <!-- Reference # & Notes -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Ref / Cheque #</label>
                            <input type="text" id="custPayRef" class="form-control" placeholder="e.g. TR-9281">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Notes / Remarks</label>
                            <input type="text" id="custPayNote" class="form-control" placeholder="e.g. Cleared pending balance">
                        </div>
                    </div>

                    <div id="custPayModalError" class="text-danger small"></div>
                </div>

                <div class="modal-footer border-top py-3 px-4" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-semibold" id="btnSaveCustPayment">
                        <span id="custPaySpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                        <i class="bi bi-check2-circle me-1" id="custPayIcon"></i> Confirm Payment Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
    function toggleCustBankSelect(method) {
        const bankRow = document.getElementById('custBankSelectRow');
        const bankSelect = document.getElementById('custPayBankId');
        if (method === 'bank') {
            bankRow.classList.remove('d-none');
            bankSelect.required = true;
        } else {
            bankRow.classList.add('d-none');
            bankSelect.required = false;
        }
    }

    function openCustomerPayModal(customerId, customerName, totalDue) {
        document.getElementById('custPayCustomerId').value = customerId;
        document.getElementById('custPayCustomerName').innerText = customerName;
        document.getElementById('custPayTotalDue').innerText = 'Rs. ' + parseFloat(totalDue).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        const amountInput = document.getElementById('custPayAmount');
        amountInput.value = parseFloat(totalDue).toFixed(2);
        
        document.getElementById('custPayDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('custPayMethod').value = 'cash';
        toggleCustBankSelect('cash');
        document.getElementById('custPayRef').value = '';
        document.getElementById('custPayNote').value = '';
        document.getElementById('custPayModalError').innerText = '';

        const modal = new bootstrap.Modal(document.getElementById('customerPayModal'));
        modal.show();
    }

    function submitCustomerPayment(e) {
        e.preventDefault();
        const customerId = document.getElementById('custPayCustomerId').value;
        const amount = parseFloat(document.getElementById('custPayAmount').value) || 0;
        const paymentDate = document.getElementById('custPayDate').value;
        const paymentMethod = document.getElementById('custPayMethod').value;
        const bankId = document.getElementById('custPayBankId').value;
        const referenceNo = document.getElementById('custPayRef').value;
        const note = document.getElementById('custPayNote').value;
        const errorBox = document.getElementById('custPayModalError');
        errorBox.innerText = '';

        if (amount <= 0) {
            errorBox.innerText = 'Please enter a valid amount.';
            return;
        }

        if (paymentMethod === 'bank' && !bankId) {
            errorBox.innerText = 'Please select a destination bank account.';
            return;
        }

        const btn = document.getElementById('btnSaveCustPayment');
        const spinner = document.getElementById('custPaySpinner');
        const icon = document.getElementById('custPayIcon');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');

        fetch(`/credit-sales/customers/${customerId}/payments`, {
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
                bank_id: bankId || null,
                reference_no: referenceNo,
                note: note,
            }),
        })
        .then(async (res) => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Error recording payment.');

            if (typeof toastr !== 'undefined') {
                toastr.success(data.message || 'Payment recorded successfully!');
            }

            setTimeout(() => {
                window.location.reload();
            }, 600);
        })
        .catch(err => {
            errorBox.innerText = err.message;
            btn.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');
        });
    }
</script>
@endpush

