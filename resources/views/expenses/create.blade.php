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

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-dark">Note / Description (Optional)</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Additional details, invoice reference, or payment method...">{{ old('note') }}</textarea>
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
</script>
@endpush
