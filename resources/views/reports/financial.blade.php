@extends('layouts.app')
@section('title', 'Overall Business Financial Report')
@section('page-title', 'Financial Overview')

@push('styles')
<style>
    .fin-card {
        border-radius: 14px;
        border: 1px solid #e9edf2;
        background: #ffffff;
        padding: 20px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .fin-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    }
    .fin-icon-badge {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .card-capital { border-top: 4px solid #6c5ce7; background: linear-gradient(135deg, #ffffff 0%, #f9f8ff 100%); }
    .card-sales { border-top: 4px solid #0d6efd; background: linear-gradient(135deg, #ffffff 0%, #f5f9ff 100%); }
    .card-purchases { border-top: 4px solid #0dcaf0; background: linear-gradient(135deg, #ffffff 0%, #f4fcfe 100%); }
    .card-expenses { border-top: 4px solid #dc3545; background: linear-gradient(135deg, #ffffff 0%, #fff7f7 100%); }
    .card-profit { border-top: 4px solid #198754; background: linear-gradient(135deg, #ffffff 0%, #f4fbf7 100%); }
    .card-cash { border-top: 4px solid #ff6e40; background: linear-gradient(135deg, #ffffff 0%, #fff8f5 100%); }
    .card-bank { border-top: 4px solid #20c997; background: linear-gradient(135deg, #ffffff 0%, #f3fdf9 100%); }
    .card-receivables { border-top: 4px solid #d97706; background: linear-gradient(135deg, #ffffff 0%, #fffbf0 100%); }
    .card-payables { border-top: 4px solid #842029; background: linear-gradient(135deg, #ffffff 0%, #fdf2f2 100%); }

    .statement-table th {
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        background: #f8fafc;
        color: #4b5563;
        border-bottom: 2px solid #e5e7eb;
    }
    .statement-table td {
        vertical-align: middle;
        font-size: 0.88rem;
    }
    .statement-section-header {
        background: #edf2f7;
        font-weight: 700;
        font-size: 0.85rem;
        color: #2d3748;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-status {
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
    }
    .btn-theme-primary {
        background: #ff6e40;
        border-color: #ff6e40;
        color: #ffffff;
        font-weight: 500;
    }
    .btn-theme-primary:hover {
        background: #f25b2a;
        border-color: #f25b2a;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(255, 110, 64, 0.25);
    }
    .nav-tabs .nav-link {
        font-weight: 600;
        color: #4b5563;
        padding: 10px 18px;
        border: none;
        border-bottom: 3px solid transparent;
    }
    .nav-tabs .nav-link.active {
        color: #ff6e40;
        border-bottom: 3px solid #ff6e40;
        background: transparent;
    }

    /* Print Specific Media Styles */
    @media print {
        body {
            background: #ffffff !important;
            color: #000000 !important;
        }
        .header, .sidebar, .menu, .filter-card, .btn, .search-group, .print-hide, .nav-tabs, .card-footer {
            display: none !important;
        }
        .tab-pane {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .tab-content > .tab-pane {
            display: block !important;
        }
        .layout-wrapper, .content-wrapper, .content, .container-fluid {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .fin-card, .card {
            border: 1px solid #cccccc !important;
            box-shadow: none !important;
            page-break-inside: avoid;
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
            <h2 class="fw-bold mb-0 text-dark">TG SURGICALS</h2>
            <p class="mb-0 text-muted">Comprehensive Business Financial Statement &amp; Executive Performance Report</p>
        </div>
        <div class="text-end">
            <div class="fw-semibold">
                Reporting Period: 
                @if($period === 'all')
                    All Time Consolidated
                @else
                    {{ $start->format('d M, Y') }} &mdash; {{ $end->format('d M, Y') }}
                @endif
            </div>
            <small class="text-muted">Generated on: {{ now()->format('d M Y, h:i A') }}</small>
        </div>
    </div>
</div>

<!-- Executive Header Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4 print-hide" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
    <div class="card-body p-4 text-white">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge bg-primary bg-opacity-25 text-primary-light border border-primary border-opacity-25 px-3 py-1 mb-2 font-monospace">
                    EXECUTIVE FINANCIAL DASHBOARD
                </span>
                <h3 class="fw-bold mb-1 text-white">Overall Business Financial Report</h3>
                <p class="text-white-50 mb-0 small">
                    Consolidated financial standing across Bank Accounts, Operating Expenses, Sales Revenue, and Inventory Purchases.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 px-3 py-2 fw-semibold">
                    <i class="bi bi-clock-history me-1 text-warning"></i>
                    @if($period === 'all')
                        All-Time Records
                    @else
                        {{ $start->format('d M, Y') }} &mdash; {{ $end->format('d M, Y') }}
                    @endif
                </span>
                <button type="button" class="btn btn-sm btn-light fw-semibold d-flex align-items-center gap-1 shadow-sm" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Statement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card fin-card filter-card mb-4">
    <div class="card-body p-1">
        <form method="GET" action="{{ route('reports.financial') }}" id="financialFilterForm">
            <div class="row g-2 align-items-end">
                <!-- Period Preset -->
                <div class="col-xl-3 col-md-4 col-sm-6">
                    <label class="form-label small fw-semibold text-dark mb-1">Time Horizon / Period</label>
                    <select name="period" id="periodSelect" class="form-select form-select-sm">
                        <option value="all" @selected($period=='all')>All Time (Lifetime Financials)</option>
                        <option value="monthly" @selected($period=='monthly')>Monthly</option>
                        <option value="yearly" @selected($period=='yearly')>Yearly</option>
                        <option value="daily" @selected($period=='daily')>Daily (Single Day)</option>
                        <option value="weekly" @selected($period=='weekly')>Weekly</option>
                        <option value="custom" @selected($period=='custom')>Custom Date Range</option>
                    </select>
                </div>

                <!-- Reference Date -->
                <div class="col-xl-2 col-md-4 col-sm-6" id="singleDateWrap" style="{{ in_array($period, ['custom', 'all']) ? 'display:none;' : '' }}">
                    <label class="form-label small fw-semibold text-dark mb-1">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $date ?? date('Y-m-d') }}">
                </div>

                <!-- Start Date -->
                <div class="col-xl-2 col-md-4 col-sm-6 custom-date-col" style="{{ $period != 'custom' ? 'display:none;' : '' }}">
                    <label class="form-label small fw-semibold text-dark mb-1">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate ?? date('Y-m-01') }}">
                </div>

                <!-- End Date -->
                <div class="col-xl-2 col-md-4 col-sm-6 custom-date-col" style="{{ $period != 'custom' ? 'display:none;' : '' }}">
                    <label class="form-label small fw-semibold text-dark mb-1">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate ?? date('Y-m-d') }}">
                </div>

                <!-- Bank Filter -->
                <div class="col-xl-3 col-md-4 col-sm-6">
                    <label class="form-label small fw-semibold text-dark mb-1">Bank Scope</label>
                    <select name="bank_id" class="form-select form-select-sm">
                        <option value="">All Banks Consolidated</option>
                        @foreach($allBanksList as $b)
                            <option value="{{ $b->id }}" @selected($bankId == $b->id)>{{ $b->name }} (A/C: {{ $b->account_number ?: 'Main' }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit / Reset -->
                <div class="col-xl-2 col-md-4 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-theme-primary px-3 flex-grow-1 d-flex align-items-center justify-content-center gap-1 shadow-sm">
                        <i class="bi bi-funnel"></i> Apply
                    </button>
                    <a href="{{ route('reports.financial') }}" class="btn btn-sm btn-outline-secondary px-2" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 9 Key Financial Summary Cards -->
<div class="row g-3 mb-4">
    <!-- 1. Total Investment / Capital Introduced -->
    <div class="col-xl-4 col-md-6">
        <div class="fin-card card-capital d-flex flex-column justify-content-between shadow-sm">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">Capital / Investment</span>
                    <div class="fin-icon-badge bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-safe2"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">Rs. {{ number_format($totalCapitalIntroduced) }}</h3>
                <div class="small text-muted">
                    Total Capital Introduced into Business
                </div>
            </div>
            <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                <span>Bank Opening: <strong>Rs. {{ number_format($totalBankOpening) }}</strong></span>
                <span>Injections: <strong>Rs. {{ number_format($periodCapitalInjected) }}</strong></span>
            </div>
        </div>
    </div>

    <!-- 2. Total Sales -->
    <div class="col-xl-4 col-md-6">
        <div class="fin-card card-sales d-flex flex-column justify-content-between shadow-sm">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">Total Sales Revenue</span>
                    <div class="fin-icon-badge bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-receipt"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">Rs. {{ number_format($netSales) }}</h3>
                <div class="small text-muted">
                    Net Billed Revenue &bull; {{ $salesCount }} Invoices
                </div>
            </div>
            <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                <span>Gross: <strong>Rs. {{ number_format($grossSales) }}</strong></span>
                <span>Disc: <strong>-Rs. {{ number_format($totalDiscounts) }}</strong></span>
            </div>
        </div>
    </div>

    <!-- 3. Total Purchases -->
    <div class="col-xl-4 col-md-6">
        <div class="fin-card card-purchases d-flex flex-column justify-content-between shadow-sm">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">Total Purchases</span>
                    <div class="fin-icon-badge bg-info bg-opacity-10 text-info">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">Rs. {{ number_format($totalPurchases) }}</h3>
                <div class="small text-muted">
                    Inventory Restocking Purchases &bull; {{ $purchasesCount }} Orders
                </div>
            </div>
            <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                <span>Units Restocked: <strong>{{ number_format($totalPurchasedUnits) }}</strong></span>
                <span>Returns: <strong>Rs. {{ number_format($totalPurchaseReturns) }}</strong></span>
            </div>
        </div>
    </div>

    <!-- 4. Total Expenses -->
    <div class="col-xl-4 col-md-6">
        <div class="fin-card card-expenses d-flex flex-column justify-content-between shadow-sm">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">Total Expenses</span>
                    <div class="fin-icon-badge bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-danger mb-1">Rs. {{ number_format($totalExpenses) }}</h3>
                <div class="small text-muted">
                    Operating &amp; Shop Expenses &bull; {{ $expensesCount }} Entries
                </div>
            </div>
            <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                <span>Categories: <strong>{{ $expensesByCategory->count() }}</strong></span>
                <span class="text-danger fw-semibold">Deducted from gross profit</span>
            </div>
        </div>
    </div>

    <!-- 5. Total Profit (Gross & Net) -->
    <div class="col-xl-4 col-md-6">
        <div class="fin-card card-profit d-flex flex-column justify-content-between shadow-sm">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">Total Net Profit</span>
                    <div class="fin-icon-badge {{ $netProfit >= 0 ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                        <i class="bi {{ $netProfit >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' }}"></i>
                    </div>
                </div>
                <h3 class="fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }} mb-1">
                    {{ $netProfit < 0 ? '-' : '' }}Rs. {{ number_format(abs($netProfit)) }}
                </h3>
                <div class="small text-muted">
                    Net Profit ({{ $netMargin }}% Net Margin)
                </div>
            </div>
            <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                <span>Gross: <strong class="{{ $grossProfit >= 0 ? 'text-success' : 'text-danger' }}">Rs. {{ number_format($grossProfit) }}</strong> ({{ $grossMargin }}%)</span>
                <span>COGS: <strong>Rs. {{ number_format($totalProductCostSold) }}</strong></span>
            </div>
        </div>
    </div>

    <!-- 6. Cash in Hand -->
    <div class="col-xl-4 col-md-6">
        <div class="fin-card card-cash d-flex flex-column justify-content-between shadow-sm">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">Cash in Hand</span>
                    <div class="fin-icon-badge" style="background: rgba(255, 110, 64, 0.12); color: #ff6e40;">
                        <i class="bi bi-wallet-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold {{ $cashInHand >= 0 ? 'text-dark' : 'text-danger' }} mb-1">
                    {{ $cashInHand < 0 ? '-' : '' }}Rs. {{ number_format(abs($cashInHand)) }}
                </h3>
                <div class="small text-muted">
                    Physical Cash Register Liquidity
                </div>
            </div>
            <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                <span>Sales Cash In: <strong>Rs. {{ number_format($cashSalesInflow) }}</strong></span>
                <span>Purchases/Exp Out: <strong>Rs. {{ number_format($purchasesFromCash + $expensesFromCash) }}</strong></span>
            </div>
        </div>
    </div>

    <!-- 7. Bank Account Balances -->
    <div class="col-xl-4 col-md-6">
        <div class="fin-card card-bank d-flex flex-column justify-content-between shadow-sm">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">Bank Balances</span>
                    <div class="fin-icon-badge bg-teal bg-opacity-10 text-success">
                        <i class="bi bi-bank"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">Rs. {{ number_format($totalBankBalance) }}</h3>
                <div class="small text-muted">
                    Total Funds Held Across All Banks
                </div>
            </div>
            <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                @foreach($bankDetails as $bd)
                    <span>{{ $bd['name'] }}: <strong>Rs. {{ number_format($bd['current_balance']) }}</strong></span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 8. Outstanding Customer Credit / Receivables -->
    <div class="col-xl-4 col-md-6">
        <div class="fin-card card-receivables d-flex flex-column justify-content-between shadow-sm">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">Customer Receivables</span>
                    <div class="fin-icon-badge bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-warning mb-1">Rs. {{ number_format($totalAllTimeReceivables) }}</h3>
                <div class="small text-muted">
                    Total Outstanding Credit Owed by Customers
                </div>
            </div>
            <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                <span>Debtors: <strong>{{ $topDebtors->count() }} active</strong></span>
                <a href="{{ route('credit-sales.index') }}" class="text-decoration-none fw-semibold text-warning">
                    Manage Credit <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 9. Outstanding Supplier Credit / Payables -->
    <div class="col-xl-4 col-md-6">
        <div class="fin-card card-payables d-flex flex-column justify-content-between shadow-sm">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">Supplier Payables</span>
                    <div class="fin-icon-badge bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-truck"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-danger mb-1">Rs. {{ number_format($outstandingPayables) }}</h3>
                <div class="small text-muted">
                    Total Outstanding Credit Owed to Suppliers
                </div>
            </div>
            <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                <span>Net Restock: <strong>Rs. {{ number_format($netPurchases) }}</strong></span>
                <span>Bank Paid: <strong>Rs. {{ number_format($totalSupplierBankPaid) }}</strong></span>
            </div>
        </div>
    </div>
</div>

<!-- Business Financial Health Snapshot / Balance Sheet -->
<div class="card report-card mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-shield-check text-success"></i> Financial Position &amp; Balance Sheet Snapshot
            </h5>
            <small class="text-muted">Total enterprise asset valuation, liabilities, and calculated net business worth.</small>
        </div>
        <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
            Net Worth: Rs. {{ number_format($netWorth) }}
        </span>
    </div>
    <div class="card-body p-4">
        <div class="row g-4">
            <!-- Assets Column -->
            <div class="col-lg-6">
                <div class="p-3 bg-light rounded-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-primary mb-0 text-uppercase" style="letter-spacing: 0.5px;">
                            <i class="bi bi-plus-circle me-1"></i> Current Business Assets
                        </h6>
                        <span class="fw-bold text-primary fs-6">Rs. {{ number_format($totalCurrentAssets) }}</span>
                    </div>

                    <ul class="list-group list-group-flush small bg-transparent">
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2">
                            <span><i class="bi bi-wallet2 text-secondary me-2"></i> Cash in Hand (Shop Drawer)</span>
                            <span class="fw-bold text-dark">Rs. {{ number_format($cashInHand) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2">
                            <span><i class="bi bi-bank text-secondary me-2"></i> Bank Accounts Balance (Total)</span>
                            <span class="fw-bold text-dark">Rs. {{ number_format($totalBankBalance) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2">
                            <span><i class="bi bi-people text-secondary me-2"></i> Accounts Receivable (Customer Credit Due)</span>
                            <span class="fw-bold text-dark">Rs. {{ number_format($totalAllTimeReceivables) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2">
                            <span><i class="bi bi-boxes text-secondary me-2"></i> Inventory Stock Value ({{ number_format($totalInventoryUnits) }} units at cost)</span>
                            <span class="fw-bold text-dark">Rs. {{ number_format($totalInventoryStockValue) }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Liabilities & Equity Column -->
            <div class="col-lg-6">
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-danger mb-0 text-uppercase" style="letter-spacing: 0.5px;">
                            <i class="bi bi-dash-circle me-1"></i> Current Liabilities
                        </h6>
                        <span class="fw-bold text-danger fs-6">Rs. {{ number_format($totalLiabilities) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small py-1">
                        <span><i class="bi bi-truck text-secondary me-2"></i> Accounts Payable (Supplier Payables)</span>
                        <span class="fw-bold text-danger">Rs. {{ number_format($outstandingPayables) }}</span>
                    </div>
                </div>

                <div class="p-3 rounded-3 border" style="background: linear-gradient(135deg, #f0fdf4 0%, #e8fadf 100%);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-success mb-0 text-uppercase" style="letter-spacing: 0.5px;">
                            <i class="bi bi-check-circle me-1"></i> Net Business Worth (Equity)
                        </h6>
                        <span class="fw-bold text-success fs-5">Rs. {{ number_format($netWorth) }}</span>
                    </div>
                    <div class="small text-muted">
                        Total Assets (Rs. {{ number_format($totalCurrentAssets) }}) &minus; Total Liabilities (Rs. {{ number_format($totalLiabilities) }})
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Consolidated Breakdowns Tabs -->
<div class="card report-card">
    <div class="card-header bg-white border-bottom p-0">
        <ul class="nav nav-tabs px-3 border-0" id="financialTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pnl-tab" data-bs-toggle="tab" data-bs-target="#pnlTab" type="button" role="tab">
                    <i class="bi bi-graph-up me-1"></i> Profit &amp; Loss (P&amp;L) Statement
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="banks-tab" data-bs-toggle="tab" data-bs-target="#banksTab" type="button" role="tab">
                    <i class="bi bi-bank me-1"></i> Bank Accounts Ledger
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="debtors-tab" data-bs-toggle="tab" data-bs-target="#debtorsTab" type="button" role="tab">
                    <i class="bi bi-people me-1"></i> Customer Receivables
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="suppliers-tab" data-bs-toggle="tab" data-bs-target="#suppliersTab" type="button" role="tab">
                    <i class="bi bi-truck me-1"></i> Supplier Purchases &amp; Payables
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expensesTab" type="button" role="tab">
                    <i class="bi bi-pie-chart me-1"></i> Expense Categories
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <div class="tab-content" id="financialTabsContent">
            
            <!-- TAB 1: P&L Statement -->
            <div class="tab-pane fade show active" id="pnlTab" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">Income Statement &amp; Operating Margin Analysis</h6>
                    <span class="text-muted small">Period: {{ $period === 'all' ? 'All Time' : $start->format('d M, Y') . ' — ' . $end->format('d M, Y') }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table statement-table align-middle">
                        <tbody>
                            <!-- REVENUE -->
                            <tr class="statement-section-header">
                                <td colspan="2">1. Operating Revenue</td>
                                <td class="text-end">Amount (Rs.)</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Gross Sales Billing</td>
                                <td class="text-muted small">{{ $salesCount }} sales invoices</td>
                                <td class="text-end fw-semibold">Rs. {{ number_format($grossSales, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4 text-danger">Less: Customer Discounts Given</td>
                                <td class="text-muted small">Invoice deductions</td>
                                <td class="text-end text-danger fw-semibold">-Rs. {{ number_format($totalDiscounts, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4 text-danger">Less: Sales Returns / Refunds</td>
                                <td class="text-muted small">Returned goods</td>
                                <td class="text-end text-danger fw-semibold">-Rs. {{ number_format($totalSaleReturns, 2) }}</td>
                            </tr>
                            <tr class="table-light fw-bold">
                                <td>Net Sales Revenue</td>
                                <td></td>
                                <td class="text-end text-primary fs-6">Rs. {{ number_format($netSales, 2) }}</td>
                            </tr>

                            <!-- COGS -->
                            <tr class="statement-section-header">
                                <td colspan="2">2. Cost of Goods Sold (COGS)</td>
                                <td class="text-end">Amount (Rs.)</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Product Purchase Cost of Goods Sold</td>
                                <td class="text-muted small">{{ number_format($totalSoldUnits) }} units sold at unit purchase price</td>
                                <td class="text-end text-danger fw-semibold">Rs. {{ number_format($totalProductCostSold, 2) }}</td>
                            </tr>
                            <tr class="table-light fw-bold">
                                <td>Gross Profit on Sales</td>
                                <td class="text-muted small">Gross Margin: {{ $grossMargin }}%</td>
                                <td class="text-end {{ $grossProfit >= 0 ? 'text-success' : 'text-danger' }} fs-6">
                                    Rs. {{ number_format($grossProfit, 2) }}
                                </td>
                            </tr>

                            <!-- OPERATING EXPENSES -->
                            <tr class="statement-section-header">
                                <td colspan="2">3. Operating Expenses</td>
                                <td class="text-end">Amount (Rs.)</td>
                            </tr>
                            @forelse($expensesByCategory as $catName => $catData)
                                <tr>
                                    <td class="ps-4">{{ $catName }}</td>
                                    <td class="text-muted small">{{ $catData['count'] }} entries ({{ $catData['percentage'] }}% of total)</td>
                                    <td class="text-end text-danger">Rs. {{ number_format($catData['total'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="ps-4 text-muted" colspan="3">No operating expenses recorded for this period.</td>
                                </tr>
                            @endforelse
                            <tr class="table-light fw-bold">
                                <td>Total Operating Expenses</td>
                                <td class="text-muted small">{{ $expensesCount }} expenses entries</td>
                                <td class="text-end text-danger fs-6">-Rs. {{ number_format($totalExpenses, 2) }}</td>
                            </tr>

                            <!-- NET PROFIT -->
                            <tr class="table-active border-top border-2 border-dark fw-bold fs-6">
                                <td class="text-dark">Net Operating Business Profit</td>
                                <td class="text-muted small">Net Profit Margin: {{ $netMargin }}%</td>
                                <td class="text-end {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }} fs-5">
                                    Rs. {{ number_format($netProfit, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: Bank Accounts -->
            <div class="tab-pane fade" id="banksTab" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">Bank Accounts &amp; Treasury Balances</h6>
                    <a href="{{ route('banks.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Open Bank Module
                    </a>
                </div>

                <div class="row g-3 mb-4">
                    @foreach($bankDetails as $bank)
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                        <i class="bi bi-bank text-primary"></i> {{ $bank['name'] }}
                                    </h6>
                                    <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1">Active</span>
                                </div>
                                <div class="small text-muted mb-2">
                                    Title: <strong>{{ $bank['account_title'] ?: 'TG Surgicals' }}</strong> &bull; A/C: <strong>{{ $bank['account_number'] ?: 'Not Set' }}</strong>
                                </div>
                                <div class="mt-3 d-flex justify-content-between align-items-end">
                                    <div>
                                        <div class="text-muted small">Current Balance</div>
                                        <h4 class="fw-bold text-dark mb-0">Rs. {{ number_format($bank['current_balance']) }}</h4>
                                    </div>
                                    <a href="{{ route('reports.bank', ['bank_id' => $bank['id']]) }}" class="btn btn-sm btn-outline-secondary">
                                        Statement <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                                <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                                    <span>Opening: Rs. {{ number_format($bank['opening_balance']) }}</span>
                                    <span>Period In: <span class="text-success">+Rs. {{ number_format($bank['period_credits']) }}</span></span>
                                    <span>Period Out: <span class="text-danger">-Rs. {{ number_format($bank['period_debits']) }}</span></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- TAB 3: Customer Receivables -->
            <div class="tab-pane fade" id="debtorsTab" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">Accounts Receivable (Customer Credit Due)</h6>
                    <a href="{{ route('credit-sales.index') }}" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-wallet2 me-1"></i> Manage Credit Sales
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Customer Name</th>
                                <th>Contact</th>
                                <th class="text-center">Active Orders</th>
                                <th class="text-end">Total Due Amount</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topDebtors as $debtor)
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <i class="bi bi-person text-secondary me-1"></i> {{ $debtor->name }}
                                    </td>
                                    <td>
                                        <span class="font-monospace small text-muted">{{ $debtor->phone ?: '—' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $debtor->sales->count() }} credit sales</span>
                                    </td>
                                    <td class="text-end fw-bold text-danger">
                                        Rs. {{ number_format($debtor->total_due) }}
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('credit-sales.index', ['search' => $debtor->name]) }}" class="btn btn-sm btn-outline-secondary">
                                            View Sales
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-check2-circle fs-2 text-success d-block mb-1"></i>
                                        All customer balances are fully settled! No outstanding receivables.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 4: Supplier Purchases & Payables -->
            <div class="tab-pane fade" id="suppliersTab" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">Supplier Restocking &amp; Purchase History</h6>
                    <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-credit-card me-1"></i> Purchases Module
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Supplier / Vendor Name</th>
                                <th class="text-center">Orders Count</th>
                                <th class="text-end">Total Purchase Volume</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topSuppliers as $supplier)
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <i class="bi bi-truck text-secondary me-1"></i> {{ $supplier->supplier_name }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $supplier->orders_count }} orders</span>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        Rs. {{ number_format($supplier->total_spent) }}
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('reports.purchases', ['supplier' => $supplier->supplier_name]) }}" class="btn btn-sm btn-outline-secondary">
                                            View Purchases
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        No supplier purchase records recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 5: Operating Expenses -->
            <div class="tab-pane fade" id="expensesTab" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">Operating Expense Category Breakdown</h6>
                    <a href="{{ route('expenses.index') }}" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-cash me-1"></i> Expenses Module
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th class="text-center">Entries</th>
                                <th>Percentage of Budget</th>
                                <th class="text-end pe-3">Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expensesByCategory as $catName => $catData)
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <i class="bi bi-tag text-secondary me-1"></i> {{ $catName }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $catData['count'] }} entries</span>
                                    </td>
                                    <td style="width: 35%;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 8px;">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $catData['percentage'] }}%;"></div>
                                            </div>
                                            <small class="fw-semibold text-muted" style="min-width: 45px;">{{ $catData['percentage'] }}%</small>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-danger pe-3">
                                        Rs. {{ number_format($catData['total']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        No expense records logged in this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const periodSelect = document.getElementById('periodSelect');
    const singleDateWrap = document.getElementById('singleDateWrap');
    const customDateCols = document.querySelectorAll('.custom-date-col');

    if (periodSelect) {
        periodSelect.addEventListener('change', function () {
            if (this.value === 'custom') {
                if (singleDateWrap) singleDateWrap.style.display = 'none';
                customDateCols.forEach(col => col.style.display = 'block');
            } else if (this.value === 'all') {
                if (singleDateWrap) singleDateWrap.style.display = 'none';
                customDateCols.forEach(col => col.style.display = 'none');
            } else {
                if (singleDateWrap) singleDateWrap.style.display = 'block';
                customDateCols.forEach(col => col.style.display = 'none');
            }
        });
    }
</script>
@endpush

