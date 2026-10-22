@extends('layouts.app')
@section('title', 'Bank Accounts & Transactions')
@section('page-title', 'Bank Accounts & Transactions')

@push('styles')
<style>
    .bank-card {
        border-radius: 14px;
        border: 1px solid #e9edf2;
        background: #ffffff;
        padding: 22px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .bank-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    }
    .bank-card-islami {
        border-top: 4px solid #0d6efd;
    }
    .bank-card-meezan {
        border-top: 4px solid #198754;
    }
    .bank-card-total {
        border-top: 4px solid #ff6e40;
        background: linear-gradient(135deg, #ffffff 0%, #fff8f5 100%);
    }
    .bank-icon-badge {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .badge-credit {
        background-color: #edfcf5;
        color: #0f8a55;
        border: 1px solid #c7f1dd;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }
    .badge-debit {
        background-color: #fdf2f2;
        color: #dc3545;
        border: 1px solid #f8d7da;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <!-- Bank Accounts Summary Cards Row -->
    <div class="row g-3 mb-4">
        <!-- Bank Islami -->
        @if($bankIslami)
            <div class="col-xl-4 col-md-6">
                <div class="bank-card bank-card-islami d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bank-icon-badge bg-primary bg-opacity-10 text-primary">
                                    <i class="bi bi-bank"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0">{{ $bankIslami->name }}</h5>
                                    <small class="text-muted">{{ $bankIslami->account_title ?: 'TG Surgicals' }}</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openEditBankModal({{ json_encode($bankIslami) }})" title="Edit Account Details">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted fw-semibold text-uppercase">Current Balance</small>
                            <h3 class="fw-bold text-dark mb-1">Rs. {{ number_format($bankIslami->current_balance) }}</h3>
                            <div class="small text-muted font-monospace">
                                A/C: {{ $bankIslami->account_number ?: 'Not Set' }} &bull; {{ $bankIslami->branch ?: 'Main Branch' }}
                            </div>
                        </div>
                    </div>
                    <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                        <span>Opening: <strong>Rs. {{ number_format($bankIslami->opening_balance) }}</strong></span>
                        <a href="{{ route('reports.bank', ['bank_id' => $bankIslami->id]) }}" class="text-primary text-decoration-none fw-semibold">
                            Statement <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Meezan Bank -->
        @if($meezanBank)
            <div class="col-xl-4 col-md-6">
                <div class="bank-card bank-card-meezan d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bank-icon-badge bg-success bg-opacity-10 text-success">
                                    <i class="bi bi-bank2"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0">{{ $meezanBank->name }}</h5>
                                    <small class="text-muted">{{ $meezanBank->account_title ?: 'TG Surgicals' }}</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openEditBankModal({{ json_encode($meezanBank) }})" title="Edit Account Details">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted fw-semibold text-uppercase">Current Balance</small>
                            <h3 class="fw-bold text-dark mb-1">Rs. {{ number_format($meezanBank->current_balance) }}</h3>
                            <div class="small text-muted font-monospace">
                                A/C: {{ $meezanBank->account_number ?: 'Not Set' }} &bull; {{ $meezanBank->branch ?: 'Main Branch' }}
                            </div>
                        </div>
                    </div>
                    <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                        <span>Opening: <strong>Rs. {{ number_format($meezanBank->opening_balance) }}</strong></span>
                        <a href="{{ route('reports.bank', ['bank_id' => $meezanBank->id]) }}" class="text-success text-decoration-none fw-semibold">
                            Statement <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Combined Total Card -->
        <div class="col-xl-4 col-md-12">
            <div class="bank-card bank-card-total d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bank-icon-badge" style="background: rgba(255,110,64,0.1); color: #ff6e40;">
                                <i class="bi bi-wallet-fill"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Total Bank Balance</h5>
                                <small class="text-muted">Bank Islami + Meezan Bank</small>
                            </div>
                        </div>
                        <a href="{{ route('reports.bank') }}" class="btn btn-sm btn-outline-secondary" title="View Full Report">
                            <i class="bi bi-receipt"></i>
                        </a>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted fw-semibold text-uppercase">Total Funds In Banks</small>
                        <h3 class="fw-bold mb-1" style="color: #ff6e40;">Rs. {{ number_format($totalBankBalance) }}</h3>
                        <div class="small text-muted">
                            Available combined liquid funds across both banks
                        </div>
                    </div>
                </div>
                <div class="border-top pt-2 mt-3 d-flex justify-content-between small">
                    <span class="text-success">Received: <strong>Rs. {{ number_format($totalPeriodCredit) }}</strong></span>
                    <span class="text-danger">Paid: <strong>Rs. {{ number_format($totalPeriodDebit) }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold text-dark mb-0">Bank Transactions Ledger</h5>
                    <span class="badge bg-light text-secondary border px-2">{{ $transactions->total() }} records</span>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Filter Form -->
                    <form action="{{ route('banks.index') }}" method="GET" class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Bank Filter -->
                        <select name="bank_id" class="form-select form-select-sm" style="width: 140px;">
                            <option value="">All Banks</option>
                            @foreach($banks as $b)
                                <option value="{{ $b->id }}" @selected($bankId == $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>

                        <!-- Type Filter -->
                        <select name="type" class="form-select form-select-sm" style="width: 130px;">
                            <option value="">All Types</option>
                            <option value="credit" @selected($type === 'credit')>Credit (Inflow +)</option>
                            <option value="debit" @selected($type === 'debit')>Debit (Outflow -)</option>
                        </select>

                        <!-- Search -->
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" value="{{ $search }}" class="form-control border-start-0" placeholder="Party, cheque, note...">
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary px-3">Filter</button>
                        @if($bankId || $type || $search || $startDate || $endDate)
                            <a href="{{ route('banks.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                        @endif
                    </form>

                    <!-- Add Transaction Button -->
                    <button type="button" class="btn btn-sm btn-success fw-semibold px-3 d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                        <i class="bi bi-plus-circle"></i> Record Transaction
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3 py-3">Date &amp; Time</th>
                            <th>Bank</th>
                            <th>Type</th>
                            <th>Party / Person Name (Kise di / Kis sa li)</th>
                            <th>Ref / Cheque #</th>
                            <th>Category</th>
                            <th class="text-end">Amount</th>
                            <th>Description</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold text-dark">{{ $tx->transaction_date->format('d M, Y') }}</div>
                                    <div class="small text-muted">{{ $tx->transaction_time ? date('h:i A', strtotime($tx->transaction_time)) : '—' }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ str_contains($tx->bank->name, 'Islami') ? 'bg-primary' : 'bg-success' }} px-2 py-1">
                                        {{ $tx->bank->name }}
                                    </span>
                                </td>
                                <td>
                                    @if($tx->type === 'credit')
                                        <span class="badge-credit d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-arrow-down-left"></i> Credit (Received)
                                        </span>
                                    @else
                                        <span class="badge-debit d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-arrow-up-right"></i> Debit (Paid)
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $tx->person_name }}</div>
                                </td>
                                <td>
                                    <span class="text-muted font-monospace small">{{ $tx->reference_no ?: '—' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border px-2 py-1">
                                        {{ $tx->category ?: 'General' }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold {{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->type === 'credit' ? '+' : '-' }} Rs. {{ number_format($tx->amount) }}
                                </td>
                                <td>
                                    <span class="text-muted small" title="{{ $tx->description }}">
                                        {{ Str::limit($tx->description ?: '—', 35) }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary py-1 px-2" onclick="openEditTxModal({{ json_encode($tx) }})" title="Edit Transaction">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('banks.transactions.destroy', $tx->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this bank transaction?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger py-1 px-2" title="Delete Transaction">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-bank fs-1 d-block mb-2 text-secondary"></i>
                                    No bank transactions recorded yet.
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                                            Record First Transaction
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($transactions->hasPages())
            <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} transactions
                </div>
                <div>
                    {{ $transactions->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal: Record Bank Transaction -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <h6 class="modal-title fw-bold text-dark" id="addTransactionModalLabel">
                    <i class="bi bi-plus-circle text-success me-1"></i> Record Bank Transaction
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('banks.transactions.store') }}" method="POST" id="addTxForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Select Bank -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Bank Name <span class="text-danger">*</span></label>
                            <select name="bank_id" class="form-select" required>
                                @foreach($banks as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }} (Balance: Rs. {{ number_format($b->current_balance) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Type (Debit / Credit) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Transaction Type <span class="text-danger">*</span></label>
                            <select name="type" id="addTxType" class="form-select" required>
                                <option value="credit" class="text-success fw-bold">Credit / Received (Inflow +) — رقم وصول کی</option>
                                <option value="debit" class="text-danger fw-bold">Debit / Paid (Outflow -) — رقم ادا کی</option>
                            </select>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" name="amount" id="addTxAmount" class="form-control fw-bold" placeholder="e.g. 50000" required>
                            <!-- Outstanding due hint shown when a supplier/customer is picked -->
                            <div id="addTxDueHint" class="form-text text-info fw-semibold d-none"></div>
                        </div>

                        <!-- Category / Purpose  ← NOW a <select> -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Category / Purpose</label>
                            <select name="category" id="addTxCategory" class="form-select">
                                <option value="">— Select Category —</option>
                                <optgroup label="Linked Categories">
                                    <option value="Customer Receipt">Customer Receipt</option>
                                    <option value="Supplier Payment">Supplier Payment</option>
                                </optgroup>
                                <optgroup label="General">
                                    <option value="Shop Expense">Shop Expense</option>
                                    <option value="Utility Bill">Utility Bill</option>
                                    <option value="Cash Deposit">Cash Deposit</option>
                                    <option value="Cash Withdrawal">Cash Withdrawal</option>
                                    <option value="Owner Drawing">Owner Drawing</option>
                                    <option value="Salary Payment">Salary Payment</option>
                                    <option value="Other">Other</option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Supplier dropdown (shown only when category=Supplier Payment + type=debit) -->
                        <div class="col-md-12 d-none" id="addTxSupplierRow">
                            <label class="form-label small fw-semibold text-dark">
                                <i class="bi bi-truck text-danger me-1"></i>
                                Select Supplier <span class="text-danger">*</span>
                                <span class="badge bg-danger-subtle text-danger border ms-1 small">Outstanding balance only</span>
                            </label>
                            <select name="linked_supplier" id="addTxSupplier" class="form-select">
                                <option value="">— Loading suppliers… —</option>
                            </select>
                        </div>

                        <!-- Customer dropdown (shown only when category=Customer Receipt + type=credit) -->
                        <div class="col-md-12 d-none" id="addTxCustomerRow">
                            <label class="form-label small fw-semibold text-dark">
                                <i class="bi bi-person-check text-success me-1"></i>
                                Select Customer <span class="text-danger">*</span>
                                <span class="badge bg-success-subtle text-success border ms-1 small">Outstanding balance only</span>
                            </label>
                            <select name="linked_customer_id" id="addTxCustomer" class="form-select">
                                <option value="">— Loading customers… —</option>
                            </select>
                        </div>

                        <!-- Party / Person Name -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">
                                Party / Person Name <span class="text-danger">*</span>
                                <small class="text-muted">(Kise di / Kis se receive ki)</small>
                            </label>
                            <input type="text" name="person_name" id="addTxPersonName" class="form-control" placeholder="e.g. Customer name, Supplier, or Self" required>
                        </div>

                        <!-- Date -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Date <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>

                        <!-- Time -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Time</label>
                            <input type="time" name="transaction_time" class="form-control" value="{{ now()->format('H:i') }}">
                        </div>

                        <!-- Cheque / Ref # -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Cheque # / Slip # / Ref ID</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="e.g. CHQ-92841">
                        </div>

                        <!-- Description -->
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold text-dark">Description / Remarks</label>
                            <input type="text" name="description" class="form-control" placeholder="Optional notes about this transaction">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Bank Transaction -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-labelledby="editTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <h6 class="modal-title fw-bold text-dark" id="editTransactionModalLabel">
                    <i class="bi bi-pencil text-primary me-1"></i> Edit Bank Transaction
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTxForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Bank Name <span class="text-danger">*</span></label>
                            <select name="bank_id" id="editTxBankId" class="form-select" required>
                                @foreach($banks as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Transaction Type <span class="text-danger">*</span></label>
                            <select name="type" id="editTxType" class="form-select" required>
                                <option value="credit">Credit / Received (Inflow +)</option>
                                <option value="debit">Debit / Paid (Outflow -)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" name="amount" id="editTxAmount" class="form-control fw-bold" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Party / Person Name <span class="text-danger">*</span></label>
                            <input type="text" name="person_name" id="editTxPersonName" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Date <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" id="editTxDate" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Time</label>
                            <input type="time" name="transaction_time" id="editTxTime" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Cheque # / Slip # / Ref ID</label>
                            <input type="text" name="reference_no" id="editTxRef" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Category / Purpose</label>
                            <input type="text" name="category" id="editTxCategory" list="categoryPresets" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Description / Remarks</label>
                            <input type="text" name="description" id="editTxDesc" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">Update Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Bank Account Details -->
<div class="modal fade" id="editBankModal" tabindex="-1" aria-labelledby="editBankModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <h6 class="modal-title fw-bold text-dark" id="editBankModalLabel">
                    <i class="bi bi-bank text-primary me-1"></i> Update Bank Details: <span id="modalBankTitleName"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editBankForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Account Title</label>
                        <input type="text" name="account_title" id="mBankTitle" class="form-control" placeholder="e.g. TG Surgicals">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Account Number / IBAN</label>
                        <input type="text" name="account_number" id="mBankNumber" class="form-control" placeholder="e.g. 0102-0103-XXXXX">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Branch Name / City</label>
                        <input type="text" name="branch" id="mBankBranch" class="form-control" placeholder="e.g. Main Branch, Sialkot">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Opening Balance (Rs.)</label>
                        <input type="number" step="0.01" name="opening_balance" id="mBankOpening" class="form-control fw-bold" required>
                        <small class="text-muted">Initial funds available before recording transactions in software.</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark">Notes</label>
                        <textarea name="notes" id="mBankNotes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">Save Bank Details</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ─────────────────────────────────────────────────────────────
    // Add Transaction Modal — Dynamic Category / Linked Entity Logic
    // ─────────────────────────────────────────────────────────────
    const addTxType     = document.getElementById('addTxType');
    const addTxCategory = document.getElementById('addTxCategory');
    const addTxAmount   = document.getElementById('addTxAmount');
    const addTxPersonName = document.getElementById('addTxPersonName');
    const addTxDueHint  = document.getElementById('addTxDueHint');
    const supplierRow   = document.getElementById('addTxSupplierRow');
    const customerRow   = document.getElementById('addTxCustomerRow');
    const supplierSel   = document.getElementById('addTxSupplier');
    const customerSel   = document.getElementById('addTxCustomer');

    let suppliersCache = null;
    let customersCache = null;

    function formatAmount(n) {
        return 'Rs. ' + Number(n).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function updateLinkedRow() {
        const cat  = addTxCategory.value;
        const type = addTxType.value;

        supplierRow.classList.add('d-none');
        customerRow.classList.add('d-none');
        supplierSel.required = false;
        customerSel.required = false;
        addTxDueHint.classList.add('d-none');

        if (cat === 'Supplier Payment' && type === 'debit') {
            supplierRow.classList.remove('d-none');
            supplierSel.required = true;
            loadSuppliers();
        } else if (cat === 'Customer Receipt' && type === 'credit') {
            customerRow.classList.remove('d-none');
            customerSel.required = true;
            loadCustomers();
        }
    }

    function loadSuppliers() {
        if (suppliersCache) { populateSupplierSelect(suppliersCache); return; }
        supplierSel.innerHTML = '<option value="">— Loading… —</option>';
        fetch('{{ route("banks.suppliers-with-due") }}')
            .then(r => r.json())
            .then(data => {
                suppliersCache = data;
                populateSupplierSelect(data);
            });
    }

    function populateSupplierSelect(data) {
        if (!data.length) {
            supplierSel.innerHTML = '<option value="">No suppliers with outstanding balance</option>';
            return;
        }
        supplierSel.innerHTML = '<option value="">— Select Supplier —</option>' +
            data.map(s =>
                `<option value="${s.name}" data-due="${s.total_due}">${s.name} — Due: ${formatAmount(s.total_due)}</option>`
            ).join('');
        onSupplierChange();
    }

    function loadCustomers() {
        if (customersCache) { populateCustomerSelect(customersCache); return; }
        customerSel.innerHTML = '<option value="">— Loading… —</option>';
        fetch('{{ route("banks.customers-with-due") }}')
            .then(r => r.json())
            .then(data => {
                customersCache = data;
                populateCustomerSelect(data);
            });
    }

    function populateCustomerSelect(data) {
        if (!data.length) {
            customerSel.innerHTML = '<option value="">No customers with outstanding balance</option>';
            return;
        }
        customerSel.innerHTML = '<option value="">— Select Customer —</option>' +
            data.map(c =>
                `<option value="${c.id}" data-due="${c.total_due}" data-name="${c.name}">${c.name} — Due: ${formatAmount(c.total_due)}</option>`
            ).join('');
        onCustomerChange();
    }

    function onSupplierChange() {
        const opt = supplierSel.selectedOptions[0];
        if (!opt || !opt.dataset.due) {
            addTxDueHint.classList.add('d-none');
            return;
        }
        const due = parseFloat(opt.dataset.due);
        addTxAmount.max = '';  // allow up to full due (system supports exact match)
        addTxDueHint.textContent = `⚠ Outstanding balance for ${opt.value}: ${formatAmount(due)}`;
        addTxDueHint.classList.remove('d-none');
        // Auto-fill person name
        if (!addTxPersonName.value) addTxPersonName.value = opt.value;
    }

    function onCustomerChange() {
        const opt = customerSel.selectedOptions[0];
        if (!opt || !opt.dataset.due) {
            addTxDueHint.classList.add('d-none');
            return;
        }
        const due = parseFloat(opt.dataset.due);
        addTxAmount.max = '';
        addTxDueHint.textContent = `⚠ Outstanding balance for ${opt.dataset.name}: ${formatAmount(due)}`;
        addTxDueHint.classList.remove('d-none');
        // Auto-fill person name
        if (!addTxPersonName.value) addTxPersonName.value = opt.dataset.name;
    }

    addTxType.addEventListener('change', updateLinkedRow);
    addTxCategory.addEventListener('change', updateLinkedRow);
    supplierSel.addEventListener('change', onSupplierChange);
    customerSel.addEventListener('change', onCustomerChange);

    // Reset caches when modal closes so fresh data is fetched next time
    document.getElementById('addTransactionModal').addEventListener('hidden.bs.modal', function () {
        suppliersCache = null;
        customersCache = null;
        addTxCategory.value = '';
        addTxType.value = 'credit';
        addTxPersonName.value = '';
        addTxDueHint.classList.add('d-none');
        supplierRow.classList.add('d-none');
        customerRow.classList.add('d-none');
        supplierSel.required = false;
        customerSel.required = false;
    });

    // ─────────────────────────────────────────────────────────────
    // Edit Transaction Modal
    // ─────────────────────────────────────────────────────────────
    function openEditTxModal(tx) {
        document.getElementById('editTxForm').action = `/banks/transactions/${tx.id}`;
        document.getElementById('editTxBankId').value = tx.bank_id;
        document.getElementById('editTxType').value = tx.type;
        document.getElementById('editTxAmount').value = tx.amount;
        document.getElementById('editTxPersonName').value = tx.person_name;
        document.getElementById('editTxDate').value = tx.transaction_date.substring(0, 10);
        document.getElementById('editTxTime').value = tx.transaction_time ? tx.transaction_time.substring(0, 5) : '';
        document.getElementById('editTxRef').value = tx.reference_no || '';
        document.getElementById('editTxCategory').value = tx.category || '';
        document.getElementById('editTxDesc').value = tx.description || '';

        new bootstrap.Modal(document.getElementById('editTransactionModal')).show();
    }

    // ─────────────────────────────────────────────────────────────
    // Edit Bank Modal
    // ─────────────────────────────────────────────────────────────
    function openEditBankModal(bank) {
        document.getElementById('editBankForm').action = `/banks/${bank.id}`;
        document.getElementById('modalBankTitleName').innerText = bank.name;
        document.getElementById('mBankTitle').value = bank.account_title || '';
        document.getElementById('mBankNumber').value = bank.account_number || '';
        document.getElementById('mBankBranch').value = bank.branch || '';
        document.getElementById('mBankOpening').value = bank.opening_balance;
        document.getElementById('mBankNotes').value = bank.notes || '';

        new bootstrap.Modal(document.getElementById('editBankModal')).show();
    }
</script>
@endpush

