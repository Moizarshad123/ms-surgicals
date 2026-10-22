@extends('layouts.app')
@section('title', 'Sales, Purchase & Invoice Profit Report')
@section('page-title', 'Profit & Loss Report')

@push('styles')
<style>
    .kpi-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        background: #ffffff;
        height: 100%;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.05);
    }
    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .report-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        background: #ffffff;
    }
    #profitTable th {
        font-weight: 600;
        font-size: 0.83rem;
        color: #495057;
        background: #f8f9fa;
        border-bottom: 2px solid #edf1f6;
        white-space: nowrap;
    }
    #profitTable td {
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
    .badge-paid {
        background-color: #e8fadf;
        color: #198754;
        border: 1px solid #c7f1dd;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 6px;
    }
    .badge-partial {
        background-color: #fff8e6;
        color: #d97706;
        border: 1px solid #fde68a;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 6px;
    }
    .badge-due {
        background-color: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 6px;
    }
    .margin-badge-high {
        background-color: #e8f8f0;
        color: #0f8a55;
        border: 1px solid #bdf0d7;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.78rem;
    }
    .margin-badge-mid {
        background-color: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.78rem;
    }
    .margin-badge-low {
        background-color: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.78rem;
    }
    .margin-badge-negative {
        background-color: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.78rem;
    }
    .child-row {
        background-color: #fafbfc;
    }
    .nested-items-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        background: #f1f4f8;
        color: #526477;
    }
    .nested-items-table td {
        font-size: 0.82rem;
    }
    .expand-btn {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: transform 0.2s ease;
    }

    /* Print Styles */
    @media print {
        body {
            background: #ffffff !important;
            color: #000000 !important;
        }
        .header, .sidebar, .menu, .filter-card, .btn, .search-group, .print-hide, .card-footer {
            display: none !important;
        }
        .layout-wrapper, .content-wrapper, .content, .container-fluid {
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
        .child-row {
            display: table-row !important;
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
            <p class="mb-0 text-muted">Sales, Purchase &amp; Invoice-Wise Profit Analysis Report</p>
        </div>
        <div class="text-end">
            <div class="fw-semibold">
                Period: 
                @if($period === 'all')
                    All Time Records
                @else
                    {{ $start->format('d M, Y') }} &mdash; {{ $end->format('d M, Y') }}
                @endif
            </div>
            <small class="text-muted">Generated: {{ now()->format('d M Y, h:i A') }}</small>
        </div>
    </div>
</div>

<!-- Header Card -->
<div class="card report-card mb-4 print-hide">
    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 p-2 text-white d-flex align-items-center justify-content-center shadow-sm" style="background: linear-gradient(135deg, #ff6e40, #ff8f6b); width: 48px; height: 48px;">
                <i class="bi bi-graph-up-arrow fs-4"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0">Sales, Purchase &amp; Profit Report</h5>
                <div class="text-muted small">Real-time invoice-level profitability, product cost analysis (COGS), and business margins.</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-secondary border px-3 py-2 fw-semibold">
                <i class="bi bi-calendar3 me-1 text-primary"></i>
                @if($period === 'all')
                    All Time Records
                @else
                    {{ $start->format('d M, Y') }} &mdash; {{ $end->format('d M, Y') }}
                @endif
            </span>
            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 shadow-sm" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
            </button>
            <a href="{{ route('reports.financial') }}" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-bank"></i> Full Financial Report
            </a>
        </div>
    </div>
</div>

<!-- Filter Controls Card -->
<div class="card report-card filter-card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.profit') }}" id="profitFilterForm">
            <div class="row g-2 align-items-end">
                <!-- Period Preset -->
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <label class="form-label small fw-semibold text-dark mb-1">Period</label>
                    <select name="period" id="periodSelect" class="form-select form-select-sm">
                        <option value="daily" @selected($period=='daily')>Daily (Single Day)</option>
                        <option value="weekly" @selected($period=='weekly')>Weekly</option>
                        <option value="monthly" @selected($period=='monthly')>Monthly</option>
                        <option value="yearly" @selected($period=='yearly')>Yearly</option>
                        <option value="custom" @selected($period=='custom')>Custom Range</option>
                        <option value="all" @selected($period=='all')>All Time (Lifetime)</option>
                    </select>
                </div>

                <!-- Reference Date (for daily/weekly/monthly/yearly) -->
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

                <!-- Customer Filter -->
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <label class="form-label small fw-semibold text-dark mb-1">Customer</label>
                    <select name="customer_id" class="form-select form-select-sm">
                        <option value="">All Customers</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected($customerId == $c->id)>{{ $c->name }} {{ $c->phone ? "({$c->phone})" : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Payment Status -->
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <label class="form-label small fw-semibold text-dark mb-1">Payment Status</label>
                    <select name="payment_status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="paid" @selected($paymentStatus == 'paid')>Paid (Cleared)</option>
                        <option value="partial" @selected($paymentStatus == 'partial')>Partial Due</option>
                        <option value="due" @selected($paymentStatus == 'due')>Unpaid (Due)</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <label class="form-label small fw-semibold text-dark mb-1">Search Invoice / Party</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Invoice # or customer..." value="{{ $search ?? '' }}">
                </div>

                <!-- Submit / Reset Buttons -->
                <div class="col-xl-2 col-md-4 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-theme-primary px-3 flex-grow-1 d-flex align-items-center justify-content-center gap-1 shadow-sm">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="{{ route('reports.profit') }}" class="btn btn-sm btn-outline-secondary px-2" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Primary Summary Cards: Total Purchases, Total Sales, Total Profit -->
<div class="row g-3 mb-4">
    <!-- Total Purchases Card -->
    <div class="col-xl-4 col-md-4">
        <div class="card kpi-card shadow-sm border-start border-4 border-info">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Total Purchases</div>
                        <h3 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($totalPurchases) }}</h3>
                        <div class="small text-muted mt-1">
                            <span class="badge bg-info bg-opacity-10 text-info fw-semibold">{{ $totalPurchaseInvoices }} Restock Orders</span>
                            <span class="ms-1">in selected period</span>
                        </div>
                    </div>
                    <div class="kpi-icon" style="background: rgba(13, 202, 240, 0.12); color: #0dcaf0;">
                        <i class="bi bi-bag-plus"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Sales Card -->
    <div class="col-xl-4 col-md-4">
        <div class="card kpi-card shadow-sm border-start border-4 border-primary">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Total Sales Revenue</div>
                        <h3 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($totalSales) }}</h3>
                        <div class="small text-muted mt-1">
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">{{ $totalInvoicesCount }} Invoices</span>
                            <span class="ms-1">&bull; {{ number_format($totalUnitsSold) }} units sold</span>
                        </div>
                    </div>
                    <div class="kpi-icon" style="background: rgba(13, 110, 253, 0.12); color: #0d6efd;">
                        <i class="bi bi-cart-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Gross Profit Card -->
    <div class="col-xl-4 col-md-4">
        <div class="card kpi-card shadow-sm border-start border-4 {{ $totalProfit >= 0 ? 'border-success' : 'border-danger' }}">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Total Gross Profit</div>
                        <h3 class="fw-bold {{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }} mt-1 mb-0">
                            {{ $totalProfit < 0 ? '-' : '' }}Rs. {{ number_format(abs($totalProfit)) }}
                        </h3>
                        <div class="small mt-1 d-flex align-items-center gap-1">
                            <span class="{{ $overallMargin >= 0 ? 'margin-badge-high' : 'margin-badge-negative' }}">
                                {{ $overallMargin }}% Margin
                            </span>
                            <span class="text-muted">COGS: Rs. {{ number_format($totalProductCost) }}</span>
                        </div>
                    </div>
                    <div class="kpi-icon" style="background: {{ $totalProfit >= 0 ? 'rgba(25, 135, 84, 0.12)' : 'rgba(220, 53, 69, 0.12)' }}; color: {{ $totalProfit >= 0 ? '#198754' : '#dc3545' }};">
                        <i class="bi {{ $totalProfit >= 0 ? 'bi-graph-up' : 'bi-graph-down' }}"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Metrics Bar: Cost of Goods, Operating Expenses, Net Profit -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="card kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Product Cost (COGS)</div>
                    <h5 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($totalProductCost) }}</h5>
                    <small class="text-muted">Purchase cost of items sold</small>
                </div>
                <div class="kpi-icon" style="background: rgba(108, 92, 231, 0.1); color: #6c5ce7; width: 40px; height: 40px; font-size: 1.1rem;">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="card kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Operating Expenses</div>
                    <h5 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($totalExpenses) }}</h5>
                    <small class="text-muted">Shop &amp; operational costs</small>
                </div>
                <div class="kpi-icon" style="background: rgba(234, 68, 68, 0.1); color: #ea4444; width: 40px; height: 40px; font-size: 1.1rem;">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="card kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Net Business Profit</div>
                    <h5 class="fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }} mt-1 mb-0">
                        {{ $netProfit < 0 ? '-' : '' }}Rs. {{ number_format(abs($netProfit)) }}
                    </h5>
                    <small class="text-muted">After expenses ({{ $netMargin }}% Net)</small>
                </div>
                <div class="kpi-icon" style="background: rgba(5, 177, 113, 0.1); color: #05b171; width: 40px; height: 40px; font-size: 1.1rem;">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="card kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Avg. Profit / Invoice</div>
                    <h5 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($averageInvoiceProfit) }}</h5>
                    <small class="text-muted">Avg Sale: Rs. {{ number_format($averageInvoiceSale) }}</small>
                </div>
                <div class="kpi-icon" style="background: rgba(255, 110, 64, 0.1); color: #ff6e40; width: 40px; height: 40px; font-size: 1.1rem;">
                    <i class="bi bi-pie-chart"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Invoice-Wise Profit Breakdown Table Card -->
<div class="card report-card">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-receipt-cutoff text-primary"></i> Invoice-Wise Profit Breakdown
            </h5>
            <div class="text-muted small">
                Showing itemized product costs, selling prices, and net margin earned on each invoice sale.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Client Live Search Filter -->
            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableFilterInput" class="form-control border-start-0" placeholder="Filter invoices...">
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllAccordions()">
                <i class="bi bi-arrows-expand"></i> <span id="toggleAllBtnText">Expand All</span>
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="profitTable">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="text-center">#</th>
                        <th style="width: 150px;">Invoice #</th>
                        <th>Sale Date</th>
                        <th>Customer</th>
                        <th class="text-center">Payment</th>
                        <th class="text-end">Total Sale</th>
                        <th class="text-end">Product Cost (COGS)</th>
                        <th class="text-end">Profit Earned</th>
                        <th class="text-center">Margin %</th>
                        <th class="text-end pe-3" style="width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody id="profitTableBody">
                    @forelse($invoicesData as $sale)
                        @php
                            $isProfitPositive = $sale->invoice_profit >= 0;
                            $marginClass = 'margin-badge-high';
                            if ($sale->profit_margin < 0) {
                                $marginClass = 'margin-badge-negative';
                            } elseif ($sale->profit_margin < 10) {
                                $marginClass = 'margin-badge-low';
                            } elseif ($sale->profit_margin < 25) {
                                $marginClass = 'margin-badge-mid';
                            }
                        @endphp
                        <tr class="main-invoice-row" id="row-{{ $sale->id }}" data-search="{{ strtolower($sale->invoice_no . ' ' . ($sale->customer->name ?? 'walk-in') . ' ' . ($sale->customer->phone ?? '')) }}">
                            <!-- Expand Button -->
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-light border expand-btn" onclick="toggleInvoiceItems({{ $sale->id }})" title="View Product Cost Breakdown">
                                    <i class="bi bi-chevron-down" id="expand-icon-{{ $sale->id }}"></i>
                                </button>
                            </td>

                            <!-- Invoice No -->
                            <td>
                                <a href="{{ route('pos.invoice', $sale->id) }}" target="_blank" class="fw-bold text-decoration-none text-dark font-monospace d-flex align-items-center gap-1">
                                    <i class="bi bi-file-earmark-text text-primary"></i> {{ $sale->invoice_no }}
                                </a>
                                @if($sale->items->count() > 0)
                                    <small class="text-muted">{{ $sale->items->count() }} {{ Str::plural('item', $sale->items->count()) }}</small>
                                @endif
                            </td>

                            <!-- Sale Date -->
                            <td>
                                <div class="fw-semibold text-dark">{{ $sale->created_at->format('d M, Y') }}</div>
                                <div class="small text-muted">{{ $sale->created_at->format('h:i A') }}</div>
                            </td>

                            <!-- Customer -->
                            <td>
                                <div class="fw-semibold text-dark">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                                @if(!empty($sale->customer->phone))
                                    <div class="small text-muted font-monospace"><i class="bi bi-telephone text-secondary"></i> {{ $sale->customer->phone }}</div>
                                @endif
                            </td>

                            <!-- Payment Status Badge -->
                            <td class="text-center">
                                @if($sale->payment_status === 'paid')
                                    <span class="badge-paid d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-check-circle-fill"></i> Paid
                                    </span>
                                @elseif($sale->payment_status === 'partial')
                                    <span class="badge-partial d-inline-flex align-items-center gap-1" title="Paid: Rs. {{ number_format($sale->paid_amount) }} / Total: Rs. {{ number_format($sale->total) }}">
                                        <i class="bi bi-hourglass-split"></i> Partial
                                    </span>
                                @else
                                    <span class="badge-due d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-exclamation-circle-fill"></i> Unpaid
                                    </span>
                                @endif
                            </td>

                            <!-- Total Sale Amount -->
                            <td class="text-end fw-bold text-dark">
                                Rs. {{ number_format($sale->total) }}
                                @if($sale->discount > 0)
                                    <div class="text-danger small fw-normal" style="font-size: 0.75rem;">
                                        -Rs. {{ number_format($sale->discount) }} disc
                                    </div>
                                @endif
                            </td>

                            <!-- Product Cost (COGS) -->
                            <td class="text-end fw-semibold text-secondary">
                                Rs. {{ number_format($sale->product_cost) }}
                            </td>

                            <!-- Profit Earned -->
                            <td class="text-end fw-bold {{ $isProfitPositive ? 'text-success' : 'text-danger' }}">
                                {{ $isProfitPositive ? '+' : '-' }} Rs. {{ number_format(abs($sale->invoice_profit)) }}
                            </td>

                            <!-- Margin % -->
                            <td class="text-center">
                                <span class="{{ $marginClass }}">
                                    {{ $sale->profit_margin }}%
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" onclick="toggleInvoiceItems({{ $sale->id }})" title="View Product Cost Breakdown">
                                        <i class="bi bi-eye"></i> Cost
                                    </button>
                                    <a href="{{ route('pos.invoice', $sale->id) }}" target="_blank" class="btn btn-outline-primary" title="Print Invoice">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- Child Row: Itemized Cost Breakdown Accordion -->
                        <tr class="child-row" id="child-row-{{ $sale->id }}" style="display: none;">
                            <td colspan="10" class="p-3 bg-light">
                                <div class="card border shadow-sm">
                                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                        <div class="small fw-bold text-dark d-flex align-items-center gap-2">
                                            <i class="bi bi-box text-primary"></i> Product Cost &amp; Profit Breakdown for Invoice #{{ $sale->invoice_no }}
                                        </div>
                                        <div class="small text-muted">
                                            Sold to: <strong>{{ $sale->customer->name ?? 'Walk-in Customer' }}</strong> &bull; {{ $sale->created_at->format('d M, Y h:i A') }}
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm nested-items-table align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="ps-3">Product Name &amp; Code</th>
                                                    <th class="text-center">Qty Sold</th>
                                                    <th class="text-end">Unit Cost (Purchased)</th>
                                                    <th class="text-end">Unit Sale Price</th>
                                                    <th class="text-end">Total Product Cost</th>
                                                    <th class="text-end">Total Line Sale</th>
                                                    <th class="text-end">Line Profit</th>
                                                    <th class="text-center pe-3">Line Margin</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sale->items_breakdown as $item)
                                                    <tr>
                                                        <td class="ps-3 fw-semibold text-dark">
                                                            {{ $item['product_name'] }}
                                                            <div class="small text-muted font-monospace">{{ $item['product_code'] }}</div>
                                                        </td>
                                                        <td class="text-center fw-bold">{{ $item['quantity'] }}</td>
                                                        <td class="text-end text-muted">Rs. {{ number_format($item['unit_cost'], 2) }}</td>
                                                        <td class="text-end fw-semibold">Rs. {{ number_format($item['unit_price'], 2) }}</td>
                                                        <td class="text-end text-secondary fw-semibold">Rs. {{ number_format($item['line_cost'], 2) }}</td>
                                                        <td class="text-end text-dark fw-bold">Rs. {{ number_format($item['line_total'], 2) }}</td>
                                                        <td class="text-end fw-bold {{ $item['line_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                            {{ $item['line_profit'] >= 0 ? '+' : '-' }} Rs. {{ number_format(abs($item['line_profit']), 2) }}
                                                        </td>
                                                        <td class="text-center pe-3">
                                                            <span class="{{ $item['line_margin'] >= 20 ? 'margin-badge-high' : ($item['line_margin'] >= 0 ? 'margin-badge-mid' : 'margin-badge-negative') }}">
                                                                {{ $item['line_margin'] }}%
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr class="fw-bold">
                                                    <td colspan="4" class="ps-3 text-dark">
                                                        Total Items Cost vs Sale Amount
                                                    </td>
                                                    <td class="text-end text-secondary">
                                                        Rs. {{ number_format($sale->product_cost, 2) }}
                                                    </td>
                                                    <td class="text-end text-dark">
                                                        Rs. {{ number_format($sale->sub_total, 2) }}
                                                    </td>
                                                    <td class="text-end {{ ($sale->sub_total - $sale->product_cost) >= 0 ? 'text-success' : 'text-danger' }}">
                                                        Rs. {{ number_format($sale->sub_total - $sale->product_cost, 2) }}
                                                    </td>
                                                    <td></td>
                                                </tr>
                                                @if($sale->discount > 0)
                                                    <tr class="text-muted small">
                                                        <td colspan="5" class="ps-3 text-danger">
                                                            <i class="bi bi-tag-fill me-1"></i> Less Order Discount Applied:
                                                        </td>
                                                        <td class="text-end text-danger fw-bold">
                                                            -Rs. {{ number_format($sale->discount, 2) }}
                                                        </td>
                                                        <td class="text-end text-danger">
                                                            Adjusts Net Profit
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                @endif
                                                <tr class="table-active fw-bold border-top border-2">
                                                    <td colspan="4" class="ps-3 text-dark">
                                                        <i class="bi bi-calculator me-1 text-primary"></i> Final Net Invoice Profit:
                                                    </td>
                                                    <td class="text-end text-secondary">
                                                        Rs. {{ number_format($sale->product_cost, 2) }}
                                                    </td>
                                                    <td class="text-end text-dark">
                                                        Rs. {{ number_format($sale->total, 2) }}
                                                    </td>
                                                    <td class="text-end {{ $sale->invoice_profit >= 0 ? 'text-success' : 'text-danger' }} fs-6">
                                                        Rs. {{ number_format($sale->invoice_profit, 2) }}
                                                    </td>
                                                    <td class="text-center pe-3">
                                                        <span class="{{ $marginClass }}">{{ $sale->profit_margin }}% Net</span>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                <h5>No sales records found for the selected period.</h5>
                                <p class="small text-muted mb-3">Adjust your date range or customer filters to display profitability data.</p>
                                <a href="{{ route('reports.profit') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($invoicesData->count() > 0)
                    <tfoot class="table-light border-top border-2 fw-bold">
                        <tr>
                            <td colspan="5" class="ps-3 text-dark fs-6">
                                Grand Total ({{ $invoicesData->count() }} Invoices)
                            </td>
                            <td class="text-end text-dark fs-6">
                                Rs. {{ number_format($totalSales) }}
                            </td>
                            <td class="text-end text-secondary fs-6">
                                Rs. {{ number_format($totalProductCost) }}
                            </td>
                            <td class="text-end {{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }} fs-6">
                                Rs. {{ number_format($totalProfit) }}
                            </td>
                            <td class="text-center fs-6">
                                <span class="{{ $overallMargin >= 0 ? 'margin-badge-high' : 'margin-badge-negative' }} fs-6">
                                    {{ $overallMargin }}%
                                </span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Period Dropdown Switcher
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

    // Toggle Child Row for an Invoice
    function toggleInvoiceItems(saleId) {
        const childRow = document.getElementById('child-row-' + saleId);
        const icon = document.getElementById('expand-icon-' + saleId);
        if (!childRow) return;

        if (childRow.style.display === 'none' || childRow.style.display === '') {
            childRow.style.display = 'table-row';
            if (icon) {
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-up');
            }
        } else {
            childRow.style.display = 'none';
            if (icon) {
                icon.classList.remove('bi-chevron-up');
                icon.classList.add('bi-chevron-down');
            }
        }
    }

    // Toggle All Accordions
    let allExpanded = false;
    function toggleAllAccordions() {
        const childRows = document.querySelectorAll('.child-row');
        const icons = document.querySelectorAll('.expand-btn i');
        const btnText = document.getElementById('toggleAllBtnText');

        allExpanded = !allExpanded;

        childRows.forEach(row => {
            row.style.display = allExpanded ? 'table-row' : 'none';
        });

        icons.forEach(icon => {
            if (allExpanded) {
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-up');
            } else {
                icon.classList.remove('bi-chevron-up');
                icon.classList.add('bi-chevron-down');
            }
        });

        if (btnText) {
            btnText.textContent = allExpanded ? 'Collapse All' : 'Expand All';
        }
    }

    // Client-side quick filter
    const tableFilterInput = document.getElementById('tableFilterInput');
    if (tableFilterInput) {
        tableFilterInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.main-invoice-row');

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                const id = row.id.replace('row-', '');
                const childRow = document.getElementById('child-row-' + id);

                if (searchData.includes(query)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                    if (childRow) childRow.style.display = 'none';
                }
            });
        });
    }
</script>
@endpush

