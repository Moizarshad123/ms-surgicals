@extends('layouts.app')
@section('title', 'Expenses')
@section('page-title', 'Expenses')

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
    .expenses-table-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    #expensesTable th {
        font-weight: 600;
        font-size: 0.85rem;
        color: #556270;
        background: #fbfbfc;
        border-bottom: 2px solid #edf1f6;
    }
    #expensesTable td {
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
    .expense-icon-avatar {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: rgba(234,68,68,0.08);
        color: #ea4444;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
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
                    <div class="text-muted small fw-semibold">Total Expenses</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($totalExpenses, 2) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(234,68,68,0.1); color: #ea4444;">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">This Month's Expenses</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($thisMonthExpenses, 2) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(255,110,64,0.1); color: #ff6e40;">
                    <i class="bi bi-calendar-check"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Today's Expenses</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($todayExpenses, 2) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(37,194,227,0.1); color: #25c2e3;">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Expense Categories</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($categoriesCount) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(5,177,113,0.1); color: #05b171;">
                    <i class="bi bi-tags"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Expenses Table Card -->
<div class="card expenses-table-card">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-cash-stack text-primary"></i> Expenses History
            </h5>
            <div class="text-muted small">Showing <span id="visibleExpenseCount">{{ $expenses->count() }}</span> expense records on this page</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Live Search -->
            <div class="search-group" style="width: 240px;">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="expenseSearchInput" class="form-control form-control-sm" placeholder="Search title, category...">
            </div>
            <!-- Quick Category Modal Trigger -->
            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-tag"></i> New Category
            </button>
            <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-theme-primary px-3 d-flex align-items-center gap-1">
                <i class="bi bi-plus-lg"></i> Add Expense
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="expensesTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Expense Title</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Note / Remarks</th>
                        <th class="text-end" style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody id="expensesTableBody">
                    @forelse ($expenses as $index => $expense)
                        <tr class="expense-row" data-search="{{ strtolower($expense->title . ' ' . ($expense->category->name ?? '') . ' ' . ($expense->note ?? '') . ' ' . $expense->expense_date) }}">
                            <td class="text-muted small">{{ $expenses->firstItem() + $index }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="expense-icon-avatar">
                                        <i class="bi bi-receipt-cutoff"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $expense->title }}</div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">Recorded {{ $expense->created_at ? $expense->created_at->format('d M Y') : '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($expense->category)
                                    <span class="badge bg-light text-secondary border px-2 py-1">
                                        <i class="bi bi-tag me-1 text-primary"></i>{{ $expense->category->name }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border px-2 py-1">General</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-dark fw-medium">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M, Y') }}</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ \Carbon\Carbon::parse($expense->expense_date)->diffForHumans() }}</div>
                            </td>
                            <td>
                                <span class="fw-bold text-danger fs-6">Rs. {{ number_format($expense->amount, 2) }}</span>
                            </td>
                            <td>
                                <span class="text-muted small">{{ $expense->note ?: '—' }}</span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-expense"
                                        data-url="{{ route('expenses.destroy', $expense) }}"
                                        data-title="{{ $expense->title }}"
                                        data-amount="Rs. {{ number_format($expense->amount, 2) }}"
                                        title="Delete expense">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                No expenses recorded yet. Click "Add Expense" to log your first expenditure.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- No Search Results Row -->
        <div id="noSearchMatch" class="py-5 text-center text-muted d-none">
            <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
            <h6 class="fw-semibold text-dark">No matching expenses found</h6>
            <p class="small mb-0">Try searching with a different keyword or category.</p>
        </div>
    </div>

    @if ($expenses->hasPages())
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-end">
            {{ $expenses->links() }}
        </div>
    @endif
</div>

<!-- QUICK ADD EXPENSE CATEGORY MODAL -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-bottom py-3 px-3" style="background:#fafbfc; border-radius: 12px 12px 0 0;">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="addCategoryModalLabel">
                    <i class="bi bi-tags text-primary"></i> New Expense Category
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('expense-categories.store') }}">
                @csrf
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Utility Bills, Rent, Maintenance" required>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3" style="background:#fafbfc; border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-theme-primary px-3">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        // -------------------------------------------------------------
        // Live Search Filter for Expenses
        // -------------------------------------------------------------
        $('#expenseSearchInput').on('input', function () {
            const query = $(this).val().toLowerCase().trim();
            let visibleCount = 0;

            $('.expense-row').each(function () {
                const searchData = $(this).data('search') || '';
                if (!query || searchData.includes(query)) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });

            $('#visibleExpenseCount').text(visibleCount);
            if (visibleCount === 0 && $('.expense-row').length > 0) {
                $('#noSearchMatch').removeClass('d-none');
            } else {
                $('#noSearchMatch').addClass('d-none');
            }
        });

        // -------------------------------------------------------------
        // SweetAlert2 Delete Confirmation
        // -------------------------------------------------------------
        $('.btn-delete-expense').on('click', function () {
            const url = $(this).data('url');
            const title = $(this).data('title');
            const amount = $(this).data('amount');

            Swal.fire({
                title: 'Delete Expense?',
                text: `Are you sure you want to delete "${title}" (${amount})? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ea4444',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
</script>
@endpush
