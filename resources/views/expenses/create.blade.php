@extends('layouts.app')
@section('title', 'Add Expense')
@section('page-title', 'Add Expense')

@push('styles')
<style>
    /* Remove up/down spinner arrows from number inputs */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number] {
        -moz-appearance: textfield;
        appearance: textfield;
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

    .form-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }

    .section-title {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #ff6e40;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-control:focus, .form-select:focus {
        border-color: #ff6e40;
        box-shadow: 0 0 0 3px rgba(255, 110, 64, 0.15);
    }

    .category-list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        border-radius: 8px;
        background: #fafbfc;
        border: 1px solid #f0f3f6;
        margin-bottom: 6px;
    }
</style>
@endpush

@section('content')
<!-- Header Card -->
<div class="card form-card mb-4">
    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 p-2 text-white d-flex align-items-center justify-content-center" style="background: #ff6e40; width: 44px; height: 44px;">
                <i class="bi bi-wallet2 fs-4"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0">Record New Expense</h5>
                <div class="text-muted small">Track shop expenses, utility bills, maintenance, or petty cash costs.</div>
            </div>
        </div>
        <div>
            <a href="{{ route('expenses.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back to Expenses
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- LEFT COLUMN: Record Expense Form --}}
    <div class="col-lg-7">
        <div class="card form-card">
            <div class="card-header bg-white border-bottom py-3">
                <div class="section-title mb-0">
                    <i class="bi bi-pencil-square"></i> Expense Information
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('expenses.store') }}" id="expenseForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Expense Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               placeholder="e.g. Shop Rent, Office Electricity, Packaging Materials"
                               value="{{ old('title') }}" required autofocus>
                        @error('title')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Expense Category</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-tag"></i></span>
                            <select name="expense_category_id" class="form-select @error('expense_category_id') is-invalid @enderror">
                                <option value="">-- Select Category (Optional) --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('expense_category_id') == $cat->id)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('expense_category_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">Rs.</span>
                                <input type="number" step="0.01" min="0" max="999999" name="amount"
                                       class="form-control no-negative @error('amount') is-invalid @enderror"
                                       placeholder="0.00"
                                       value="{{ old('amount') }}" required>
                            </div>
                            @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Expense Date <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror"
                                       value="{{ old('expense_date', date('Y-m-d')) }}" required>
                            </div>
                            @error('expense_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Payment Type <span class="text-danger">*</span></label>
                        <select name="payment_method" id="expPayMethod" class="form-select @error('payment_method') is-invalid @enderror" onchange="updatePaymentUI()" required>
                            <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>
                                💵 Cash / Cash in Hand (Available Balance: Rs. {{ number_format($cashInHand ?? 0, 2) }})
                            </option>
                            <option value="bank" @selected(old('payment_method') === 'bank')>
                                🏦 Bank Account (Deduct from Bank Balance)
                            </option>
                            <option value="easypaisa" @selected(old('payment_method') === 'easypaisa')>📱 EasyPaisa</option>
                            <option value="jazzcash" @selected(old('payment_method') === 'jazzcash')>📱 JazzCash</option>
                            <option value="cheque" @selected(old('payment_method') === 'cheque')>📄 Cheque</option>
                            <option value="other" @selected(old('payment_method') === 'other')>📌 Other</option>
                        </select>
                        @error('payment_method')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Bank Account Select --}}
                    <div class="mb-3 {{ old('payment_method') === 'bank' ? '' : 'd-none' }}" id="expBankSelectRow">
                        <label class="form-label small fw-semibold text-dark">Select Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_id" id="expPayBankId" class="form-select @error('bank_id') is-invalid @enderror" onchange="updatePaymentUI()">
                            <option value="" data-balance="0">-- Choose Bank Account --</option>
                            @foreach($banks as $b)
                                <option value="{{ $b->id }}" data-balance="{{ (float) $b->current_balance }}" data-name="{{ $b->name }}" @selected(old('bank_id') == $b->id)>
                                    {{ $b->name }} @if($b->account_title)({{ $b->account_title }})@endif — Available Balance: Rs. {{ number_format($b->current_balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('bank_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Live Balance & Deduction Notice Box --}}
                    <div id="paymentBalanceNotice" class="alert alert-light border py-2 px-3 mb-3 small d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i id="balanceNoticeIcon" class="bi bi-wallet2 text-success fs-5"></i>
                            <div>
                                <div id="balanceNoticeTitle" class="fw-semibold text-dark">Available Cash in Hand</div>
                                <div id="balanceNoticeSub" class="text-muted" style="font-size: 0.78rem;">Expense amount will be deducted from Cash in Hand</div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted" style="font-size: 0.72rem;">Available Balance</div>
                            <div id="balanceNoticeAmount" class="fw-bold text-success fs-6">Rs. {{ number_format($cashInHand ?? 0, 2) }}</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-dark">Note / Description (Optional)</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Additional details, invoice reference...">{{ old('note') }}</textarea>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-theme-primary px-4 py-2 d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle fs-5"></i> Save Expense
                        </button>
                        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary px-4 py-2">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- RIGHT COLUMN: Category Quick Creation & Existing List --}}
    <div class="col-lg-5">
        <!-- Quick Add Category Card -->
        <div class="card form-card mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <div class="section-title mb-0">
                    <i class="bi bi-folder-plus"></i> Quick Add Category
                </div>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('expense-categories.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Office Stationery, Repairs" required>
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-plus-lg"></i> Add Expense Category
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Categories Card -->
        <div class="card form-card">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div class="section-title mb-0">
                    <i class="bi bi-tags"></i> Existing Categories
                </div>
                <span class="badge bg-light text-secondary border">{{ $categories->count() }} active</span>
            </div>
            <div class="card-body p-3" style="max-height: 280px; overflow-y: auto;">
                @forelse ($categories as $cat)
                    <div class="category-list-item">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-tag text-primary"></i>
                            <span class="fw-semibold text-dark small">{{ $cat->name }}</span>
                        </div>
                        <span class="badge bg-light text-muted border" style="font-size: 0.72rem;">
                            {{ $cat->expenses_count }} {{ Str::plural('record', $cat->expenses_count) }}
                        </span>
                    </div>
                @empty
                    <div class="text-center text-muted py-3 small">
                        No expense categories created yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Prevent typing/pasting negative values
    document.querySelectorAll('.no-negative').forEach(function (input) {
        input.addEventListener('input', function () {
            if (this.value !== '' && parseFloat(this.value) < 0) {
                this.value = 0;
            }
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === '-' || e.key === 'e' || e.key === 'E') {
                e.preventDefault();
            }
        });
        input.addEventListener('wheel', function () {
            this.blur();
        });
    });

    const cashInHand = {{ (float) ($cashInHand ?? 0) }};

    function updatePaymentUI() {
        const methodSelect = document.getElementById('expPayMethod');
        const method = methodSelect ? methodSelect.value : 'cash';
        const bankRow = document.getElementById('expBankSelectRow');
        const bankSelect = document.getElementById('expPayBankId');
        const noticeBox = document.getElementById('paymentBalanceNotice');
        const icon = document.getElementById('balanceNoticeIcon');
        const title = document.getElementById('balanceNoticeTitle');
        const sub = document.getElementById('balanceNoticeSub');
        const amt = document.getElementById('balanceNoticeAmount');
        const amountInput = document.querySelector('input[name="amount"]');
        const expenseAmount = parseFloat(amountInput ? amountInput.value : 0) || 0;

        if (method === 'bank') {
            bankRow.classList.remove('d-none');
            bankSelect.required = true;
            const selectedOpt = bankSelect.options[bankSelect.selectedIndex];
            const bankBalance = parseFloat(selectedOpt ? selectedOpt.getAttribute('data-balance') : 0) || 0;
            const bankName = selectedOpt ? (selectedOpt.getAttribute('data-name') || 'Bank Account') : 'Bank Account';

            icon.className = 'bi bi-bank2 text-primary fs-5';
            title.textContent = selectedOpt && selectedOpt.value ? bankName : 'Bank Account Selected';
            sub.textContent = 'Debit transaction will deduct Rs. ' + expenseAmount.toFixed(2) + ' from this bank';
            amt.textContent = 'Rs. ' + bankBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            amt.className = bankBalance >= expenseAmount ? 'fw-bold text-primary fs-6' : 'fw-bold text-danger fs-6';
        } else if (method === 'cash') {
            bankRow.classList.add('d-none');
            bankSelect.required = false;

            icon.className = 'bi bi-wallet2 text-success fs-5';
            title.textContent = 'Available Cash in Hand';
            sub.textContent = 'Amount will be deducted from Cash in Hand (funded by Capital & Sales)';
            amt.textContent = 'Rs. ' + cashInHand.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            amt.className = cashInHand >= expenseAmount ? 'fw-bold text-success fs-6' : 'fw-bold text-danger fs-6';
        } else {
            bankRow.classList.add('d-none');
            bankSelect.required = false;

            icon.className = 'bi bi-credit-card text-info fs-5';
            title.textContent = method.toUpperCase() + ' Payment';
            sub.textContent = 'Expense recorded via ' + method;
            amt.textContent = 'Rs. ' + expenseAmount.toFixed(2);
            amt.className = 'fw-bold text-dark fs-6';
        }
    }

    // Attach amount input listener to update notice live
    document.querySelector('input[name="amount"]')?.addEventListener('input', updatePaymentUI);

    // Initialize on load
    document.addEventListener('DOMContentLoaded', updatePaymentUI);
</script>
@endpush
