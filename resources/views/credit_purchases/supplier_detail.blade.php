@extends('layouts.app')
@section('title', 'Supplier Account — ' . $supplierName)
@section('page-title', 'Supplier Account Details')

@push('styles')
<style>
    .detail-kpi {
        border-radius: 12px; border: 1px solid #e9edf2;
        background: #fff; padding: 16px 18px; height: 100%;
    }
    .badge-status-partial { background-color:#fff8e6; color:#b78103; border:1px solid #ffeeba; font-weight:600; padding:3px 9px; border-radius:5px; }
    .badge-status-due { background-color:#fdf2f2; color:#dc3545; border:1px solid #f8d7da; font-weight:600; padding:3px 9px; border-radius:5px; }
    .badge-status-paid { background-color:#edfcf5; color:#0f8a55; border:1px solid #c7f1dd; font-weight:600; padding:3px 9px; border-radius:5px; }
    .advance-highlight { background: linear-gradient(135deg, #edfcf5, #d1fae5); border: 2px solid #6ee7b7; border-radius: 12px; padding: 18px; }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    {{-- Back + Title --}}
    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('credit-purchases.suppliers') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Suppliers
            </a>
            <div>
                <h4 class="fw-bold mb-0 text-dark">{{ $supplierName ?: 'General Supplier' }}</h4>
                <small class="text-muted">{{ $totalInvoices }} {{ Str::plural('invoice', $totalInvoices) }} &bull; Transaction History</small>
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($balanceDue > 0)
                <button type="button" class="btn btn-sm btn-success shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#supplierPayModal">
                    <i class="bi bi-cash-stack me-1"></i> Pay Supplier
                </button>
            @endif
            <a href="{{ route('purchases.create') }}?supplier={{ urlencode($supplierName) }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> New Purchase
            </a>
        </div>
    </div>

    {{-- KPI Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="detail-kpi border-start border-4 border-primary">
                <div class="text-muted small fw-semibold text-uppercase">Total Billed</div>
                <div class="fs-4 fw-bold text-dark mt-1">Rs. {{ number_format($totalBilled, 2) }}</div>
                <div class="small text-muted">Across {{ $totalInvoices }} invoices</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="detail-kpi border-start border-4 border-success">
                <div class="text-muted small fw-semibold text-uppercase">Total Paid</div>
                <div class="fs-4 fw-bold text-success mt-1">Rs. {{ number_format($totalPaid, 2) }}</div>
                <div class="small text-muted">{{ $paidInvoices }} invoices cleared</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="detail-kpi border-start border-4 border-danger">
                <div class="text-muted small fw-semibold text-uppercase">Balance Due</div>
                <div class="fs-4 fw-bold text-danger mt-1">Rs. {{ number_format($balanceDue, 2) }}</div>
                <div class="small text-muted">{{ $pendingInvoices }} invoices pending</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            @if($advanceBalance > 0)
                <div class="advance-highlight h-100">
                    <div class="text-success small fw-bold text-uppercase"><i class="bi bi-wallet-fill me-1"></i>Advance Balance</div>
                    <div class="fs-4 fw-bold text-success mt-1">Rs. {{ number_format($advanceBalance, 2) }}</div>
                    <div class="small text-success fw-semibold">Automatically applied on next purchase</div>
                </div>
            @else
                <div class="detail-kpi border-start border-4 border-secondary">
                    <div class="text-muted small fw-semibold text-uppercase">Advance Balance</div>
                    <div class="fs-4 fw-bold text-muted mt-1">Rs. 0.00</div>
                    <div class="small text-muted">No advance balance held</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Invoice History --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-receipt me-2 text-primary"></i>Purchase Invoices</h6>
            <span class="badge bg-light text-dark border">{{ $purchases->count() }} records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Reference #</th>
                            <th>Purchase Date</th>
                            <th>Due Date</th>
                            <th class="text-center">Items</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            @php
                                $isOverdue = $purchase->due_date && $purchase->due_date->isPast() && $purchase->due_amount > 0;
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold font-monospace text-dark">
                                        <i class="bi bi-file-earmark-text text-primary me-1"></i>{{ $purchase->reference_no }}
                                    </span>
                                </td>
                                <td>{{ $purchase->purchase_date->format('d M, Y') }}</td>
                                <td>
                                    @if($purchase->due_date)
                                        <span class="{{ $isOverdue ? 'text-danger fw-semibold' : '' }}">
                                            {{ $purchase->due_date->format('d M, Y') }}
                                        </span>
                                        @if($isOverdue) <span class="badge bg-danger ms-1">Overdue</span> @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <small class="text-muted">{{ $purchase->items->count() }} items</small>
                                </td>
                                <td class="text-end fw-semibold">Rs. {{ number_format((float)$purchase->total, 2) }}</td>
                                <td class="text-end text-success fw-semibold">Rs. {{ number_format((float)$purchase->paid_amount, 2) }}</td>
                                <td class="text-end fw-bold {{ $purchase->due_amount > 0 ? 'text-danger' : 'text-success' }}">
                                    Rs. {{ number_format((float)$purchase->due_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($purchase->payment_status === 'paid')
                                        <span class="badge-status-paid">Paid</span>
                                    @elseif($purchase->payment_status === 'partial')
                                        <span class="badge-status-partial">Partial</span>
                                    @else
                                        <span class="badge-status-due">Due</span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    <a href="{{ route('credit-purchases.receipt', $purchase->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Print / View Voucher" target="_blank">
                                        <i class="bi bi-printer me-1"></i> Voucher
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-4 text-muted">No invoices found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Payment History --}}
    <div class="card border-0 shadow-sm" style="border-radius:14px;">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-success"></i>All Payments Made to Supplier</h6>
            <span class="badge bg-light text-dark border">{{ $allPayments->count() }} transactions</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Date</th>
                            <th>Invoice Reference</th>
                            <th>Payment Method</th>
                            <th>Note / Details</th>
                            <th class="text-end pe-4">Paid Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allPayments as $payment)
                            <tr>
                                <td class="ps-4">
                                    <div class="text-dark fw-semibold">{{ $payment->payment_date->format('d M, Y') }}</div>
                                    <small class="text-muted">{{ $payment->payment_date->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <span class="font-monospace text-primary fw-semibold">{{ $payment->purchase->reference_no ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary text-capitalize px-2 py-1">
                                        {{ str_replace('_', ' ', $payment->payment_method) }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $payment->note ?: 'Supplier Payment' }}</td>
                                <td class="text-end pe-4 fw-bold text-success fs-6">Rs. {{ number_format((float)$payment->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
                <input type="hidden" id="suppPaySupplierName" value="{{ $supplierName }}">

                <div class="modal-body p-4">
                    <!-- Info Box (Supplier Wise Total Bill - NO Invoice Number) -->
                    <div class="p-3 rounded-3 mb-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Supplier Name:</span>
                            <span class="fw-bold text-dark fs-6">{{ $supplierName ?: 'General Supplier' }}</span>
                        </div>
                        <div class="d-flex justify-content-between pt-2 border-top">
                            <span class="fw-bold text-danger">Total Outstanding Balance:</span>
                            <span class="fw-bold text-danger fs-5">Rs. {{ number_format($balanceDue, 2) }}</span>
                        </div>
                    </div>

                    <!-- Payment Amount Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Payment Amount (Rs.) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" id="suppPayAmount" class="form-control form-control-lg fw-bold text-end" value="{{ number_format($balanceDue, 2, '.', '') }}" required placeholder="0.00">
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

