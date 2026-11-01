@extends('layouts.app')
@section('title', 'Credit Purchases — Supplier Accounts')
@section('page-title', 'Credit Purchases & Supplier Accounts')

@push('styles')
<style>
    .kpi-card {
        border-radius: 12px;
        border: 1px solid #e9edf2;
        background: #ffffff;
        padding: 18px 20px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
    }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
    .kpi-icon {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
    }
    .supplier-row:hover { background: #f8fafc; }
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
                    <div class="text-muted small fw-semibold text-uppercase">Active Suppliers</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalSuppliers) }}</div>
                    <div class="small text-muted mt-1">Suppliers with transactions</div>
                </div>
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-people"></i></div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="kpi-card d-flex align-items-center justify-content-between shadow-sm border-start border-4 border-danger">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Outstanding</div>
                    <div class="fs-4 fw-bold text-danger mt-1">Rs. {{ number_format($totalOutstanding, 2) }}</div>
                    <div class="small text-muted mt-1">Total payable to all suppliers</div>
                </div>
                <div class="kpi-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-diamond"></i></div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="kpi-card d-flex align-items-center justify-content-between shadow-sm border-start border-4 border-success">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Advance Held</div>
                    <div class="fs-4 fw-bold text-success mt-1">Rs. {{ number_format($totalAdvances, 2) }}</div>
                    <div class="small text-muted mt-1">Advance balances with suppliers</div>
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
                    <a href="{{ route('credit-purchases.suppliers', ['status' => 'pending']) }}"
                       class="btn btn-sm {{ $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-secondary' }}">
                        <i class="bi bi-hourglass-split me-1"></i> Pending Due
                    </a>
                    <a href="{{ route('credit-purchases.suppliers', ['status' => 'all']) }}"
                       class="btn btn-sm {{ $statusFilter === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}">
                        <i class="bi bi-list-check me-1"></i> All Suppliers
                    </a>
                    <a href="{{ route('credit-purchases.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-receipt me-1"></i> All Purchases (Per Invoice)
                    </a>
                </div>
                <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-primary shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Record Purchase
                </a>
            </div>

            {{-- Search --}}
            <form method="GET" action="{{ route('credit-purchases.suppliers') }}" class="row g-2 align-items-center mt-3 pt-3 border-top">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Search supplier name..." value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark">Filter</button>
                    @if($search)
                        <a href="{{ route('credit-purchases.suppliers', ['status' => $statusFilter]) }}" class="btn btn-sm btn-outline-secondary">
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
                            <th class="ps-4 py-3">Supplier Name</th>
                            <th class="text-center">Invoices</th>
                            <th class="text-end">Total Billed</th>
                            <th class="text-end">Total Paid</th>
                            <th class="text-end">Balance Due</th>
                            <th class="text-center">Last Purchase</th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                            @php
                                $balanceDue = max(0, (float)$supplier->total_billed - (float)$supplier->total_paid);
                                $advanceBalance = \App\Models\SupplierAdvance::where('supplier_name', $supplier->supplier_name)->value('advance_balance') ?? 0;
                                $encodedName = urlencode($supplier->supplier_name);
                            @endphp
                            <tr class="supplier-row">
                                <td class="ps-4">
                                    <div class="fw-bold text-dark fs-6">{{ $supplier->supplier_name ?: 'General Supplier' }}</div>
                                    @if($advanceBalance > 0)
                                        <span class="advance-badge"><i class="bi bi-wallet me-1"></i>Advance Balance: Rs. {{ number_format($advanceBalance, 2) }}</span>
                                    @else
                                        <small class="text-muted">Vendor</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2 fs-6">{{ $supplier->total_invoices }}</span>
                                </td>
                                <td class="text-end fw-semibold text-dark">Rs. {{ number_format((float)$supplier->total_billed, 2) }}</td>
                                <td class="text-end fw-semibold text-success">Rs. {{ number_format((float)$supplier->total_paid, 2) }}</td>
                                <td class="text-end fw-bold {{ $balanceDue > 0 ? 'text-danger' : 'text-success' }}">
                                    Rs. {{ number_format($balanceDue, 2) }}
                                </td>
                                <td class="text-center text-muted small">
                                    {{ \Carbon\Carbon::parse($supplier->last_purchase_date)->format('d M, Y') }}
                                </td>
                                <td class="text-center pe-4">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        @if($balanceDue > 0)
                                            <button type="button" 
                                                    class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 shadow-sm px-2"
                                                    onclick="openSupplierPayModal('{{ addslashes($supplier->supplier_name) }}', {{ $balanceDue }})"
                                                    title="Pay Supplier">
                                                <i class="bi bi-cash-stack"></i> Pay Amount
                                            </button>
                                        @endif
                                        <a href="{{ route('credit-purchases.supplier-detail', $encodedName) }}"
                                           class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm px-2" title="View Details">
                                            <i class="bi bi-eye-fill"></i> View Details
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    No suppliers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($suppliers->hasPages())
                <div class="px-4 py-3 border-top">{{ $suppliers->links() }}</div>
            @endif
        </div>
    </div>

<!-- ============================================================== -->
<!-- MODAL: PAY SUPPLIER (TOTAL OUTSTANDING BILL)                   -->
<!-- ============================================================== -->
<div class="modal fade" id="supplierPayModal" tabindex="-1" aria-labelledby="supplierPayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 14px 14px 0 0;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="supplierPayModalLabel">
                    <i class="bi bi-cash-stack text-success"></i> Pay Supplier
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="supplierPayForm" onsubmit="submitSupplierPayment(event)">
                @csrf
                <input type="hidden" id="suppPaySupplierName" value="">

                <div class="modal-body p-4">
                    <!-- Info Box (Supplier Wise Total Bill - NO Invoice Number) -->
                    <div class="p-3 rounded-3 mb-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Supplier Name:</span>
                            <span class="fw-bold text-dark fs-6" id="suppPayDisplayName">-</span>
                        </div>
                        <div class="d-flex justify-content-between pt-2 border-top">
                            <span class="fw-bold text-danger">Total Outstanding Balance:</span>
                            <span class="fw-bold text-danger fs-5" id="suppPayTotalDue">Rs. 0.00</span>
                        </div>
                    </div>

                    <!-- Payment Amount Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Payment Amount (Rs.) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" id="suppPayAmount" class="form-control form-control-lg fw-bold text-end" required placeholder="0.00">
                        <div class="form-text text-muted mt-1 small">
                            <i class="bi bi-info-circle me-1"></i>Any amount exceeding total due will be stored as <strong>Supplier Advance Balance</strong>.
                        </div>
                    </div>

                    <!-- Payment Date & Method -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" id="suppPayDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Payment Method <span class="text-danger">*</span></label>
                            <select id="suppPayMethod" class="form-select" onchange="toggleSuppBankSelect(this.value)" required>
                                <option value="cash" selected>Cash (Physical Cash-in-Hand)</option>
                                <option value="bank">Bank Account</option>
                                <option value="easypaisa">EasyPaisa</option>
                                <option value="jazzcash">JazzCash</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bank Account Select (shows when Method is Bank) -->
                    <div class="mb-3 d-none" id="suppBankSelectRow">
                        <label class="form-label small fw-semibold text-dark">
                            Source Bank Account <span class="text-danger">*</span>
                        </label>
                        <select id="suppPayBankId" class="form-select">
                            <option value="">-- Choose Bank Account --</option>
                            @foreach($banks ?? [] as $b)
                                <option value="{{ $b->id }}">{{ $b->name }} @if($b->account_title) ({{ $b->account_title }}) @endif</option>
                            @endforeach
                        </select>
                        <div class="form-text text-danger small">
                            <i class="bi bi-info-circle me-1"></i>A Bank Transaction (debit) will be created automatically to decrement your bank balance.
                        </div>
                    </div>

                    <!-- Reference # & Notes -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Ref / Cheque #</label>
                            <input type="text" id="suppPayRef" class="form-control" placeholder="e.g. CHQ-10492">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Notes / Remarks</label>
                            <input type="text" id="suppPayNote" class="form-control" placeholder="e.g. Paid pending ledger balance">
                        </div>
                    </div>

                    <div id="suppPayModalError" class="text-danger small"></div>
                </div>

                <div class="modal-footer border-top py-3 px-4" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-semibold" id="btnSaveSuppPayment">
                        <span id="suppPaySpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                        <i class="bi bi-check2-circle me-1" id="suppPayIcon"></i> Confirm Payment
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
    function toggleSuppBankSelect(method) {
        const bankRow = document.getElementById('suppBankSelectRow');
        const bankSelect = document.getElementById('suppPayBankId');
        if (method === 'bank') {
            bankRow.classList.remove('d-none');
            bankSelect.required = true;
        } else {
            bankRow.classList.add('d-none');
            bankSelect.required = false;
        }
    }

    function openSupplierPayModal(supplierName, totalDue) {
        document.getElementById('suppPaySupplierName').value = supplierName;
        document.getElementById('suppPayDisplayName').innerText = supplierName || 'General Supplier';
        document.getElementById('suppPayTotalDue').innerText = 'Rs. ' + parseFloat(totalDue).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        const amountInput = document.getElementById('suppPayAmount');
        amountInput.value = parseFloat(totalDue).toFixed(2);
        
        document.getElementById('suppPayDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('suppPayMethod').value = 'cash';
        toggleSuppBankSelect('cash');
        document.getElementById('suppPayRef').value = '';
        document.getElementById('suppPayNote').value = '';
        document.getElementById('suppPayModalError').innerText = '';

        const modal = new bootstrap.Modal(document.getElementById('supplierPayModal'));
        modal.show();
    }

    function submitSupplierPayment(e) {
        e.preventDefault();
        const supplierName = document.getElementById('suppPaySupplierName').value;
        const amount = parseFloat(document.getElementById('suppPayAmount').value) || 0;
        const paymentDate = document.getElementById('suppPayDate').value;
        const paymentMethod = document.getElementById('suppPayMethod').value;
        const bankId = document.getElementById('suppPayBankId').value;
        const referenceNo = document.getElementById('suppPayRef').value;
        const note = document.getElementById('suppPayNote').value;
        const errorBox = document.getElementById('suppPayModalError');
        errorBox.innerText = '';

        if (amount <= 0) {
            errorBox.innerText = 'Please enter a valid amount.';
            return;
        }

        if (paymentMethod === 'bank' && !bankId) {
            errorBox.innerText = 'Please select a source bank account.';
            return;
        }

        const btn = document.getElementById('btnSaveSuppPayment');
        const spinner = document.getElementById('suppPaySpinner');
        const icon = document.getElementById('suppPayIcon');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');

        fetch(`{{ route('credit-purchases.suppliers.payments.store') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                supplier_name: supplierName,
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

