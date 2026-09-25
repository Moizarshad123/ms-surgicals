@extends('layouts.app')
@section('title', 'Expense Report')
@section('page-title', 'Expense Report')

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
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }
    .report-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    #expensesTable th {
        font-weight: 600;
        font-size: 0.84rem;
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

    /* Print Specific Media Styles */
    @media print {
        body {
            background: #ffffff !important;
            color: #000000 !important;
        }
        .header, .sidebar, .filter-card, .btn, .search-group, .print-hide {
            display: none !important;
        }
        .layout-wrapper, .content-wrapper, .content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .report-card, .kpi-card {
            border: 1px solid #cccccc !important;
            box-shadow: none !important;
        }
        .print-header {
            display: block !important;
            margin-bottom: 20px;
            border-bottom: 2px solid #333333;
            padding-bottom: 10px;
        }
    }
    .print-header {
        display: none;
    }
</style>
@endpush

@section('content')
<!-- Print Document Header -->
<div class="print-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-0">MS SURGICALS</h3>
            <p class="mb-0 text-muted">Business Expenditures &amp; Cost Report</p>
        </div>
        <div class="text-end">
            <div class="fw-semibold">Period: {{ $start->format('d M, Y') }} — {{ $end->format('d M, Y') }}</div>
            <small class="text-muted">Generated: {{ now()->format('d M Y, h:i A') }}</small>
        </div>
    </div>
</div>

<!-- Header Card -->
<div class="card report-card mb-4 print-hide">
    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 p-2 text-white d-flex align-items-center justify-content-center" style="background: #ff6e40; width: 44px; height: 44px;">
                <i class="bi bi-wallet2 fs-4"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0">Expense &amp; Cost Report</h5>
                <div class="text-muted small">Analyze shop operating expenses, recurring bills, and category cost allocations.</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-secondary border px-3 py-2">
                <i class="bi bi-calendar3 me-1 text-primary"></i> {{ $start->format('d M, Y') }} &mdash; {{ $end->format('d M, Y') }}
            </span>
            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>
</div>

<!-- Filter Controls Card -->
<div class="card report-card filter-card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.expenses') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold text-dark mb-1">Period Preset</label>
                    <select name="period" id="periodSelect" class="form-select form-select-sm">
                        <option value="daily" @selected($period=='daily')>Daily (Single Day)</option>
                        <option value="weekly" @selected($period=='weekly')>Weekly</option>
                        <option value="monthly" @selected($period=='monthly')>Monthly</option>
                        <option value="yearly" @selected($period=='yearly')>Yearly</option>
                        <option value="custom" @selected($period=='custom')>Custom Date Range</option>
                    </select>
                </div>

                {{-- Single date input for daily/weekly/monthly/yearly --}}
                <div class="col-lg-2 col-md-4" id="singleDateWrap" style="{{ $period == 'custom' ? 'display:none;' : '' }}">
                    <label class="form-label small fw-semibold text-dark mb-1">Reference Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $date ?? date('Y-m-d') }}">
                </div>

                {{-- Custom start & end date inputs --}}
                <div class="col-lg-2 col-md-4 custom-date-col" style="{{ $period != 'custom' ? 'display:none;' : '' }}">
                    <label class="form-label small fw-semibold text-dark mb-1">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate ?? date('Y-m-01') }}">
                </div>

                <div class="col-lg-2 col-md-4 custom-date-col" style="{{ $period != 'custom' ? 'display:none;' : '' }}">
                    <label class="form-label small fw-semibold text-dark mb-1">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate ?? date('Y-m-d') }}">
                </div>

                {{-- Category Filter --}}
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small fw-semibold text-dark mb-1">Expense Category</label>
                    <select name="expense_category_id" class="form-select form-select-sm">
                        <option value="">-- All Expense Categories --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(($categoryId ?? '') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-theme-primary px-3 flex-grow-1 d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-funnel"></i> Apply Filter
                    </button>
                    <a href="{{ route('reports.expenses') }}" class="btn btn-sm btn-outline-secondary px-2" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- KPI Summary Cards Row -->
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
                    <div class="text-muted small fw-semibold">Expense Records</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalEntries) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(37,194,227,0.1); color: #25c2e3;">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Top Spending Category</div>
                    <h5 class="fw-bold text-dark mt-1 mb-0 text-truncate" style="max-width: 150px;">{{ $topCategory }}</h5>
                    <small class="text-muted">Rs. {{ number_format($topCategoryAmount, 2) }}</small>
                </div>
                <div class="kpi-icon" style="background: rgba(108,92,231,0.1); color: #6c5ce7;">
                    <i class="bi bi-tag"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Average Expense</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($averageExpense, 2) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(5,177,113,0.1); color: #05b171;">
                    <i class="bi bi-pie-chart"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Expenses Table Card -->
<div class="card report-card">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-0">Expenses Log</h5>
            <div class="text-muted small">Showing <span id="visibleExpenseCount">{{ $expenses->count() }}</span> expense records</div>
        </div>
        <div class="search-group print-hide" style="width: 250px;">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="expenseSearchInput" class="form-control form-control-sm" placeholder="Quick search in table...">
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
                        <th>Expense Date</th>
                        <th class="text-end">Amount</th>
                        <th>Note / Remarks</th>
                    </tr>
                </thead>
                <tbody id="expensesTableBody">
                    @forelse ($expenses as $index => $expense)
                        <tr class="expense-row" data-search="{{ strtolower($expense->title . ' ' . ($expense->category->name ?? '') . ' ' . ($expense->note ?? '') . ' ' . $expense->expense_date) }}">
                            <td class="text-muted small">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $expense->title }}</div>
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
                                <div class="text-dark small fw-medium">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M, Y') }}</div>
                                <div class="text-muted small" style="font-size: 0.72rem;">{{ \Carbon\Carbon::parse($expense->expense_date)->diffForHumans() }}</div>
                            </td>
                            <td class="text-end fw-bold text-danger">
                                Rs. {{ number_format($expense->amount, 2) }}
                            </td>
                            <td>
                                <span class="text-muted small">{{ $expense->note ?: '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                No expense records found for this selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($expenses->count() > 0)
                    <tfoot class="table-light border-top">
                        <tr class="fw-bold text-dark">
                            <td colspan="4" class="text-end">Total Expenditures:</td>
                            <td class="text-end text-danger fs-6">Rs. {{ number_format($totalExpenses, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <!-- No Search Results Row -->
        <div id="noSearchMatch" class="py-5 text-center text-muted d-none">
            <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
            <h6 class="fw-semibold text-dark">No matching expenses found</h6>
            <p class="small mb-0">Try searching with a different title, category, or note.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        // Toggle Custom Date inputs vs Single Reference Date input
        $('#periodSelect').on('change', function () {
            if ($(this).val() === 'custom') {
                $('#singleDateWrap').hide();
                $('.custom-date-col').show();
            } else {
                $('#singleDateWrap').show();
                $('.custom-date-col').hide();
            }
        });

        // Live table search
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
    });
</script>
@endpush
