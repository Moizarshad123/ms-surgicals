@extends('layouts.app')
@section('title', 'Sales Report')
@section('page-title', 'Sales Report')

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
    .sales-nav-pills .nav-link {
        font-weight: 600;
        font-size: 0.92rem;
        padding: 9px 20px;
        color: #556270;
        border-radius: 8px;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .sales-nav-pills .nav-link:hover {
        color: #293134;
        background: #f1f4f8;
    }
    .sales-nav-pills .nav-link.active {
        background: #ff6e40;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(255, 110, 64, 0.25);
    }
    .sales-nav-pills .nav-link.active .badge {
        background: #ffffff !important;
        color: #ff6e40 !important;
    }
    .sales-nav-pills .nav-link#tab-bank-link.active {
        background: #0d6efd;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.25);
    }
    .sales-nav-pills .nav-link#tab-bank-link.active .badge {
        color: #0d6efd !important;
    }
    .sales-nav-pills .nav-link#tab-cash-link.active {
        background: #05b171;
        box-shadow: 0 2px 8px rgba(5, 177, 113, 0.25);
    }
    .sales-nav-pills .nav-link#tab-cash-link.active .badge {
        color: #05b171 !important;
    }

    #salesTable th, #cashSalesTable th, #bankSalesTable th {
        font-weight: 600;
        font-size: 0.84rem;
        color: #556270;
        background: #fbfbfc;
        border-bottom: 2px solid #edf1f6;
    }
    #salesTable td, #cashSalesTable td, #bankSalesTable td {
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
        .header, .sidebar, .filter-card, .btn, .search-group, .print-hide, .nav {
            display: none !important;
        }
        .tab-pane {
            display: block !important;
            opacity: 1 !important;
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
            <h3 class="fw-bold mb-0">TG SURGICALS</h3>
            <p class="mb-0 text-muted">Sales &amp; Revenue Report</p>
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
                <i class="bi bi-graph-up-arrow fs-4"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0">Sales &amp; Revenue Report</h5>
                <div class="text-muted small">Analyze overall sales, Cash Sales, and Bank Credited Sales with real-time settlements.</div>
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
        <form method="GET" action="{{ route('reports.sales') }}" id="filterForm">
            <input type="hidden" name="tab" id="activeTabInput" value="{{ $activeTab }}">
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

                {{-- Customer / Invoice Search Filter --}}
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small fw-semibold text-dark mb-1">Search Customer / Invoice</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Invoice # or Customer name / phone..." value="{{ $search }}">
                </div>

                <div class="col-lg-3 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-theme-primary px-3 flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> Apply Filter
                    </button>
                    <a href="{{ route('reports.sales') }}" class="btn btn-sm btn-outline-secondary" title="Reset filter to today">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Navigation Tabs for All Sales, Cash Sales, Bank Sales -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 print-hide">
    <ul class="nav nav-pills sales-nav-pills gap-2 bg-white p-2 rounded-3 border" id="salesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'all' ? 'active' : '' }}" id="tab-all-link" data-bs-toggle="pill" data-bs-target="#tab-all" type="button" role="tab" onclick="setReportTab('all')">
                <i class="bi bi-collection"></i> All Sales
                <span class="badge bg-secondary-subtle text-dark border ms-1">{{ $allSales->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'cash' ? 'active' : '' }}" id="tab-cash-link" data-bs-toggle="pill" data-bs-target="#tab-cash" type="button" role="tab" onclick="setReportTab('cash')">
                <i class="bi bi-cash-stack"></i> Cash Sales
                <span class="badge bg-success-subtle text-success border ms-1">{{ $cashSales->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'bank' ? 'active' : '' }}" id="tab-bank-link" data-bs-toggle="pill" data-bs-target="#tab-bank" type="button" role="tab" onclick="setReportTab('bank')">
                <i class="bi bi-bank"></i> Bank Sales &amp; Credits
                <span class="badge bg-primary-subtle text-primary border ms-1">{{ $bankSales->count() }}</span>
            </button>
        </li>
    </ul>

    <!-- Quick Indicator of Default View -->
    @if($period === 'daily')
        <div class="small text-muted">
            <i class="bi bi-clock-history me-1 text-primary"></i> Showing today's records by default (<strong>{{ $start->format('d M, Y') }}</strong>)
        </div>
    @endif
</div>

<!-- TAB CONTENT AREA -->
<div class="tab-content" id="salesTabsContent">

    <!-- ============================================================== -->
    <!-- TAB 1: ALL SALES (Default)                                     -->
    <!-- ============================================================== -->
    <div class="tab-pane fade {{ $activeTab === 'all' ? 'show active' : '' }}" id="tab-all" role="tabpanel">
        <!-- KPI Summary Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card kpi-card">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <div class="text-muted small fw-semibold">Total Revenue</div>
                            <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($totalSales) }}</h4>
                        </div>
                        <div class="kpi-icon" style="background: rgba(255,110,64,0.1); color: #ff6e40;">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="card kpi-card">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <div class="text-muted small fw-semibold">Invoices / Orders</div>
                            <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalInvoices) }}</h4>
                        </div>
                        <div class="kpi-icon" style="background: rgba(37,194,227,0.1); color: #25c2e3;">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="card kpi-card">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <div class="text-muted small fw-semibold">Units Sold (Net)</div>
                            <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalItems) }}</h4>
                            @if(isset($totalReturnedUnits) && $totalReturnedUnits > 0)
                                <div class="small mt-1 text-muted" style="font-size: 0.73rem;">
                                    <span class="text-danger fw-semibold">-{{ number_format($totalReturnedUnits) }} returned</span>
                                </div>
                            @endif
                        </div>
                        <div class="kpi-icon" style="background: rgba(108,92,231,0.1); color: #6c5ce7;">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl col-md-6 col-sm-6">
                <div class="card kpi-card">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <div class="text-muted small fw-semibold">Cash Collected</div>
                            <h4 class="fw-bold text-success mt-1 mb-0">Rs. {{ number_format($cashPaymentsInPeriod) }}</h4>
                        </div>
                        <div class="kpi-icon" style="background: rgba(5,177,113,0.1); color: #05b171;">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl col-md-6 col-sm-6">
                <div class="card kpi-card">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <div class="text-muted small fw-semibold">Bank Credited</div>
                            <h4 class="fw-bold text-primary mt-1 mb-0">Rs. {{ number_format($totalBankCreditAmount) }}</h4>
                        </div>
                        <div class="kpi-icon" style="background: rgba(13,110,253,0.1); color: #0d6efd;">
                            <i class="bi bi-bank"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Table Card -->
        <div class="card report-card">
            <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="fw-bold text-dark mb-0">All Sales Transactions Log</h5>
                    <div class="text-muted small">Showing <span id="visibleSalesCount">{{ $sales->count() }}</span> sales records</div>
                </div>
                <div class="search-group print-hide" style="width: 250px;">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="salesSearchInput" class="form-control form-control-sm" placeholder="Quick search in table...">
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="salesTable">
                        <thead>
                            <tr>
                                <th style="width: 130px;">Invoice #</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th class="text-end">Net Total</th>
                                <th class="text-end">Paid Amount</th>
                                <th class="text-end">Balance Due</th>
                                <th class="text-center">Status</th>
                                <th>Date &amp; Time</th>
                                <th class="text-center print-hide" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="salesTableBody">
                            @forelse ($sales as $sale)
                                @php
                                    $due = $sale->due_amount;
                                    $itemsPayload = $sale->items->map(function ($it) {
                                        return [
                                            'product' => $it->product->name ?? 'Item',
                                            'code' => $it->product->code ?? '',
                                            'quantity' => $it->quantity,
                                            'price' => (float) $it->price,
                                            'total' => (float) $it->total,
                                            'returned' => (int) ($it->returnItems ? $it->returnItems->sum('quantity') : 0),
                                        ];
                                    });
                                @endphp
                                <tr class="sale-row" data-search="{{ strtolower($sale->invoice_no . ' ' . ($sale->customer->name ?? '') . ' ' . ($sale->customer->phone ?? '') . ' ' . $sale->created_at->format('d M Y')) }}">
                                    <td>
                                        <span class="badge bg-light text-dark font-monospace border px-2 py-1">
                                            <i class="bi bi-hash text-muted"></i>{{ $sale->invoice_no }}
                                        </span>
                                        @if($sale->returns && $sale->returns->count() > 0)
                                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 px-1 py-0 small d-block mt-1" style="width: fit-content;" title="This sale has items returned">
                                                <i class="bi bi-arrow-counterclockwise text-warning"></i> Returned
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $sale->customer->phone ?? '—' }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        Rs. {{ number_format($sale->total) }}
                                    </td>
                                    <td class="text-end text-success fw-semibold">
                                        Rs. {{ number_format($sale->paid_amount) }}
                                    </td>
                                    <td class="text-end fw-bold {{ $due > 0 ? 'text-danger' : 'text-muted' }}">
                                        Rs. {{ number_format($due) }}
                                    </td>
                                    <td class="text-center">
                                        @if($sale->payment_status === 'paid' || $due <= 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1" style="color: #fff !important;">Paid</span>
                                        @elseif($sale->payment_status === 'partial')
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 text-dark">Partial</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Due</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-dark small fw-medium">{{ $sale->created_at->format('d M, Y') }}</div>
                                        <div class="text-muted small" style="font-size: 0.72rem;">{{ $sale->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="text-center print-hide">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info py-1 px-2 btn-view-sale"
                                                    data-invoice="{{ $sale->invoice_no }}"
                                                    data-customer="{{ $sale->customer->name ?? 'Walk-in Customer' }}"
                                                    data-phone="{{ $sale->customer->phone ?? '—' }}"
                                                    data-date="{{ $sale->created_at->format('d M, Y h:i A') }}"
                                                    data-subtotal="Rs. {{ number_format($sale->sub_total) }}"
                                                    data-discount="Rs. {{ number_format($sale->discount) }}"
                                                    data-total="Rs. {{ number_format($sale->total) }}"
                                                    data-paid="Rs. {{ number_format($sale->paid_amount) }}"
                                                    data-due="Rs. {{ number_format($due) }}"
                                                    data-status="{{ ucfirst($sale->payment_status) }}"
                                                    data-items='@json($itemsPayload)'
                                                    title="View Sold Products">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-outline-primary py-1 px-2" title="Edit Order">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a target="_blank" href="{{ route('pos.invoice', $sale->id) }}" class="btn btn-outline-secondary py-1 px-2" title="Print Invoice">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            @if($due > 0 || $sale->payments->count() > 1)
                                                <a target="_blank" href="{{ route('credit-sales.receipt', $sale->id) }}" class="btn btn-outline-primary py-1 px-2" title="Installment Statement">
                                                    <i class="bi bi-file-earmark-text"></i>
                                                </a>
                                            @endif
                                            <a href="{{ route('sales-returns.create', ['sale_id' => $sale->id]) }}" class="btn btn-outline-warning py-1 px-2" title="Process Sales Return">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger py-1 px-2" onclick="confirmDeleteSale({{ $sale->id }}, '{{ $sale->invoice_no }}')" title="Delete Sale & Restore Stock">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                        No sales transactions found for this selected period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($sales->count() > 0)
                            <tfoot class="table-light border-top">
                                <tr class="fw-bold text-dark">
                                    <td colspan="3" class="text-end">Summary Totals:</td>
                                    <td class="text-end">Rs. {{ number_format($totalSubTotal) }}</td>
                                    <td class="text-end text-danger">Rs. {{ number_format($totalDiscount) }}</td>
                                    <td class="text-end text-success fs-6">Rs. {{ number_format($totalSales) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <!-- No Search Results Row -->
                <div id="noSearchMatch" class="py-5 text-center text-muted d-none">
                    <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                    <h6 class="fw-semibold text-dark">No matching transactions found</h6>
                    <p class="small mb-0">Try searching with a different invoice number or customer name.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 2: CASH SALES                                              -->
    <!-- ============================================================== -->
    <div class="tab-pane fade {{ $activeTab === 'cash' ? 'show active' : '' }}" id="tab-cash" role="tabpanel">
        <!-- Cash KPI Summary Row -->
        <div class="row g-3 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card kpi-card">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <div class="text-muted small fw-semibold">Total Cash Sales Billed</div>
                            <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($cashTotalSales) }}</h4>
                            <small class="text-muted">{{ $cashTotalInvoices }} Cash Invoices</small>
                        </div>
                        <div class="kpi-icon" style="background: rgba(5,177,113,0.1); color: #05b171;">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="card kpi-card">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <div class="text-muted small fw-semibold">Cash Collected (Period)</div>
                            <h4 class="fw-bold text-success mt-1 mb-0">Rs. {{ number_format($cashPaymentsInPeriod) }}</h4>
                            <small class="text-muted">Total physical cash received</small>
                        </div>
                        <div class="kpi-icon" style="background: rgba(5,177,113,0.1); color: #05b171;">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-12">
                <div class="card kpi-card">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <div class="text-muted small fw-semibold">Remaining Due on Cash Orders</div>
                            <h4 class="fw-bold text-danger mt-1 mb-0">Rs. {{ number_format(max(0, $cashTotalSales - $cashTotalPaid)) }}</h4>
                            <small class="text-muted">Uncollected balance</small>
                        </div>
                        <div class="kpi-icon" style="background: rgba(234,68,68,0.1); color: #ea4444;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash Sales Table -->
        <div class="card report-card">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-cash-stack text-success"></i> Cash Sales Directory
                    </h5>
                    <div class="text-muted small">Transactions paid with cash counter receipts</div>
                </div>
                <span class="badge bg-success-subtle text-success border px-3 py-2">
                    {{ $cashSales->count() }} Orders
                </span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="cashSalesTable">
                        <thead>
                            <tr>
                                <th style="width: 130px;">Invoice #</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th class="text-end">Total Bill</th>
                                <th class="text-end">Cash Paid</th>
                                <th class="text-end">Due Balance</th>
                                <th class="text-center">Status</th>
                                <th>Order Date</th>
                                <th class="text-center print-hide" style="width: 90px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cashSales as $sale)
                                @php
                                    $due = $sale->due_amount;
                                    $itemsPayload = $sale->items->map(function ($it) {
                                        return [
                                            'product' => $it->product->name ?? 'Item',
                                            'code' => $it->product->code ?? '',
                                            'quantity' => $it->quantity,
                                            'price' => (float) $it->price,
                                            'total' => (float) $it->total,
                                            'returned' => (int) ($it->returnItems ? $it->returnItems->sum('quantity') : 0),
                                        ];
                                    });
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark font-monospace border px-2 py-1">
                                            {{ $sale->invoice_no }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $sale->customer->phone ?? '—' }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        Rs. {{ number_format($sale->total) }}
                                    </td>
                                    <td class="text-end text-success fw-semibold">
                                        Rs. {{ number_format($sale->paid_amount) }}
                                    </td>
                                    <td class="text-end fw-bold {{ $due > 0 ? 'text-danger' : 'text-muted' }}">
                                        Rs. {{ number_format($due) }}
                                    </td>
                                    <td class="text-center">
                                        @if($sale->payment_status === 'paid' || $due <= 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1" style="color: #fff !important;">Paid</span>
                                        @elseif($sale->payment_status === 'partial')
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 text-dark">Partial</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Due</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-dark small fw-medium">{{ $sale->created_at->format('d M, Y') }}</div>
                                        <div class="text-muted small" style="font-size: 0.72rem;">{{ $sale->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="text-center print-hide">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info py-1 px-2 btn-view-sale"
                                                    data-invoice="{{ $sale->invoice_no }}"
                                                    data-customer="{{ $sale->customer->name ?? 'Walk-in Customer' }}"
                                                    data-phone="{{ $sale->customer->phone ?? '—' }}"
                                                    data-date="{{ $sale->created_at->format('d M, Y h:i A') }}"
                                                    data-subtotal="Rs. {{ number_format($sale->sub_total) }}"
                                                    data-discount="Rs. {{ number_format($sale->discount) }}"
                                                    data-total="Rs. {{ number_format($sale->total) }}"
                                                    data-paid="Rs. {{ number_format($sale->paid_amount) }}"
                                                    data-due="Rs. {{ number_format($due) }}"
                                                    data-status="{{ ucfirst($sale->payment_status) }}"
                                                    data-items='@json($itemsPayload)'
                                                    title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-outline-primary py-1 px-2" title="Edit Order">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a target="_blank" href="{{ route('pos.invoice', $sale->id) }}" class="btn btn-outline-secondary py-1 px-2" title="Print Invoice">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="bi bi-cash-stack fs-1 d-block mb-2 text-secondary"></i>
                                        No cash sales recorded in this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($cashSales->count() > 0)
                            <tfoot class="table-light border-top">
                                <tr class="fw-bold text-dark">
                                    <td colspan="3" class="text-end">Cash Sales Totals:</td>
                                    <td class="text-end">Rs. {{ number_format($cashTotalSales) }}</td>
                                    <td class="text-end text-success">Rs. {{ number_format($cashTotalPaid) }}</td>
                                    <td class="text-end text-danger">Rs. {{ number_format(max(0, $cashTotalSales - $cashTotalPaid)) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 3: BANK SALES & CREDITS                                    -->
    <!-- ============================================================== -->
    <div class="tab-pane fade {{ $activeTab === 'bank' ? 'show active' : '' }}" id="tab-bank" role="tabpanel">
        <!-- Bank KPI Summary Row -->
        <div class="row g-3 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card kpi-card">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <div class="text-muted small fw-semibold">Bank Credited (Receipts)</div>
                            <h4 class="fw-bold text-primary mt-1 mb-0">Rs. {{ number_format($totalBankCreditAmount) }}</h4>
                            <small class="text-muted">Total credited into bank accounts</small>
                        </div>
                        <div class="kpi-icon" style="background: rgba(13,110,253,0.1); color: #0d6efd;">
                            <i class="bi bi-bank"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="card kpi-card">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <div class="text-muted small fw-semibold">Bank Invoiced Orders</div>
                            <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($bankTotalSales) }}</h4>
                            <small class="text-muted">{{ $bankTotalInvoices }} Orders linked with Bank</small>
                        </div>
                        <div class="kpi-icon" style="background: rgba(13,110,253,0.1); color: #0d6efd;">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-12">
                <div class="card kpi-card">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <div class="text-muted small fw-semibold">Bank Transactions Count</div>
                            <h4 class="fw-bold text-success mt-1 mb-0">{{ $bankTransactions->count() }} Deposits</h4>
                            <small class="text-muted">Direct credit deposits</small>
                        </div>
                        <div class="kpi-icon" style="background: rgba(5,177,113,0.1); color: #05b171;">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Credited Inflows Table -->
        <div class="card report-card mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-down-left-circle text-success"></i> Direct Bank Credits (Amount Credited in Bank)
                    </h5>
                    <div class="text-muted small">Customer receipts and deposits credited into Bank Islami &amp; Meezan Bank</div>
                </div>
                <span class="badge bg-primary-subtle text-primary border px-3 py-2">
                    Rs. {{ number_format($totalBankCreditAmount) }} Total Credited
                </span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date &amp; Time</th>
                                <th>Bank Account</th>
                                <th>Customer / Party Name</th>
                                <th>Reference / Cheque #</th>
                                <th>Category</th>
                                <th class="text-end">Amount Credited (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bankTransactions as $bt)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $bt->transaction_date->format('d M, Y') }}</div>
                                        <div class="small text-muted">{{ $bt->transaction_time ? date('h:i A', strtotime($bt->transaction_time)) : '—' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ str_contains($bt->bank->name ?? '', 'Islami') ? 'bg-primary' : 'bg-success' }} px-2 py-1">
                                            {{ $bt->bank->name ?? 'Bank' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $bt->person_name }}</div>
                                        @if($bt->description)
                                            <div class="small text-muted">{{ $bt->description }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-monospace text-muted small">{{ $bt->reference_no ?: '—' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border px-2 py-1">
                                            {{ $bt->category ?: 'Customer Receipt' }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-success fs-6">
                                        + Rs. {{ number_format($bt->amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-bank fs-2 text-secondary d-block mb-1"></i>
                                        No bank credit deposits recorded in this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($bankTransactions->count() > 0)
                            <tfoot class="table-light border-top">
                                <tr class="fw-bold text-dark">
                                    <td colspan="5" class="text-end">Total Amount Credited in Bank:</td>
                                    <td class="text-end text-success fs-6">+ Rs. {{ number_format($totalBankCreditAmount, 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- Sales Invoices Paid via Bank -->
        <div class="card report-card">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-receipt text-primary"></i> Sale Orders Paid via Bank Transfer
                    </h5>
                    <div class="text-muted small">Orders settled or partially settled via bank transfers</div>
                </div>
                <span class="badge bg-primary-subtle text-primary border px-3 py-2">
                    {{ $bankSales->count() }} Invoices
                </span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="bankSalesTable">
                        <thead>
                            <tr>
                                <th style="width: 130px;">Invoice #</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th class="text-end">Net Total</th>
                                <th class="text-end">Bank Paid</th>
                                <th class="text-end">Balance Due</th>
                                <th class="text-center">Status</th>
                                <th>Order Date</th>
                                <th class="text-center print-hide" style="width: 90px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bankSales as $sale)
                                @php
                                    $due = $sale->due_amount;
                                    $bankPaidOnSale = (float) $sale->payments->whereIn('payment_method', ['bank', 'bank_transfer'])->sum('amount');
                                    $itemsPayload = $sale->items->map(function ($it) {
                                        return [
                                            'product' => $it->product->name ?? 'Item',
                                            'code' => $it->product->code ?? '',
                                            'quantity' => $it->quantity,
                                            'price' => (float) $it->price,
                                            'total' => (float) $it->total,
                                            'returned' => (int) ($it->returnItems ? $it->returnItems->sum('quantity') : 0),
                                        ];
                                    });
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark font-monospace border px-2 py-1">
                                            {{ $sale->invoice_no }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $sale->customer->phone ?? '—' }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        Rs. {{ number_format($sale->total) }}
                                    </td>
                                    <td class="text-end text-primary fw-semibold">
                                        Rs. {{ number_format($bankPaidOnSale) }}
                                    </td>
                                    <td class="text-end fw-bold {{ $due > 0 ? 'text-danger' : 'text-muted' }}">
                                        Rs. {{ number_format($due) }}
                                    </td>
                                    <td class="text-center">
                                        @if($sale->payment_status === 'paid' || $due <= 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1" style="color: #fff !important;">Paid</span>
                                        @elseif($sale->payment_status === 'partial')
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 text-dark">Partial</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Due</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-dark small fw-medium">{{ $sale->created_at->format('d M, Y') }}</div>
                                        <div class="text-muted small" style="font-size: 0.72rem;">{{ $sale->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="text-center print-hide">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info py-1 px-2 btn-view-sale"
                                                    data-invoice="{{ $sale->invoice_no }}"
                                                    data-customer="{{ $sale->customer->name ?? 'Walk-in Customer' }}"
                                                    data-phone="{{ $sale->customer->phone ?? '—' }}"
                                                    data-date="{{ $sale->created_at->format('d M, Y h:i A') }}"
                                                    data-subtotal="Rs. {{ number_format($sale->sub_total) }}"
                                                    data-discount="Rs. {{ number_format($sale->discount) }}"
                                                    data-total="Rs. {{ number_format($sale->total) }}"
                                                    data-paid="Rs. {{ number_format($sale->paid_amount) }}"
                                                    data-due="Rs. {{ number_format($due) }}"
                                                    data-status="{{ ucfirst($sale->payment_status) }}"
                                                    data-items='@json($itemsPayload)'
                                                    title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-outline-primary py-1 px-2" title="Edit Order">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a target="_blank" href="{{ route('pos.invoice', $sale->id) }}" class="btn btn-outline-secondary py-1 px-2" title="Print Invoice">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="bi bi-bank fs-2 text-secondary d-block mb-1"></i>
                                        No bank sales invoices recorded in this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($bankSales->count() > 0)
                            <tfoot class="table-light border-top">
                                <tr class="fw-bold text-dark">
                                    <td colspan="3" class="text-end">Bank Sales Totals:</td>
                                    <td class="text-end">Rs. {{ number_format($bankTotalSales) }}</td>
                                    <td class="text-end text-primary">Rs. {{ number_format($bankTotalPaid) }}</td>
                                    <td class="text-end text-danger">Rs. {{ number_format(max(0, $bankTotalSales - $bankTotalPaid)) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- SALE DETAILS MODAL -->
<div class="modal fade" id="saleDetailModal" tabindex="-1" aria-labelledby="saleDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 12px 12px 0 0;">
                <div>
                    <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="saleDetailModalLabel">
                        <i class="bi bi-receipt text-primary"></i> Sale Order Details
                    </h6>
                    <div class="text-muted small mt-1" id="mSaleInvoice">INV-</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Meta Summary Row -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-3 col-6">
                        <div class="p-2 bg-light rounded-3 border">
                            <small class="text-muted d-block">Customer</small>
                            <span class="fw-semibold text-dark" id="mSaleCustomer">-</span>
                            <div class="text-muted small" id="mSalePhone" style="font-size: 0.75rem;">-</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-2 bg-light rounded-3 border">
                            <small class="text-muted d-block">Order Date</small>
                            <span class="fw-semibold text-dark" id="mSaleDate">-</span>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-2 bg-light rounded-3 border">
                            <small class="text-muted d-block">Total Amount</small>
                            <span class="fw-bold text-dark" id="mSaleTotal">Rs. 0</span>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-2 bg-light rounded-3 border">
                            <small class="text-muted d-block">Payment Status</small>
                            <span class="badge bg-success" id="mSaleStatusBadge">Paid</span>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-responsive border rounded-3 mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Code</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-center">Qty</th>
                                <th class="text-center">Returned</th>
                                <th class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody id="mSaleItemsBody">
                            {{-- Populated by JavaScript --}}
                        </tbody>
                    </table>
                </div>

                <!-- Financial Breakdown -->
                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">Subtotal:</span>
                                <span class="fw-semibold text-dark" id="mSaleSubTotal">Rs. 0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">Discount:</span>
                                <span class="fw-semibold text-danger" id="mSaleDiscount">- Rs. 0</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold text-dark">Grand Total:</span>
                                <span class="fw-bold text-success fs-6" id="mSaleGrandTotal">Rs. 0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">Paid Amount:</span>
                                <span class="fw-semibold text-primary" id="mSalePaid">Rs. 0</span>
                            </div>
                            <div class="d-flex justify-content-between pt-1 border-top">
                                <span class="fw-bold text-danger">Remaining Balance Due:</span>
                                <span class="fw-bold text-danger" id="mSaleNetDue">Rs. 0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4" style="background:#fafbfc; border-radius: 0 0 12px 12px;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function setReportTab(tab) {
        document.getElementById('activeTabInput').value = tab;
        // Update URL query parameter without page reload
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }

    // Toggle custom date range inputs on period select change
    document.getElementById('periodSelect').addEventListener('change', function () {
        const val = this.value;
        const customCols = document.querySelectorAll('.custom-date-col');
        const singleDateWrap = document.getElementById('singleDateWrap');

        if (val === 'custom') {
            customCols.forEach(el => el.style.display = 'block');
            singleDateWrap.style.display = 'none';
        } else {
            customCols.forEach(el => el.style.display = 'none');
            singleDateWrap.style.display = 'block';
        }
    });

    // In-page quick search filter across table rows
    document.getElementById('salesSearchInput')?.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#salesTableBody .sale-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            if (searchData.includes(query)) {
                row.classList.remove('d-none');
                visibleCount++;
            } else {
                row.classList.add('d-none');
            }
        });

        document.getElementById('visibleSalesCount').innerText = visibleCount;
        const noMatch = document.getElementById('noSearchMatch');
        if (visibleCount === 0 && rows.length > 0) {
            noMatch.classList.remove('d-none');
        } else {
            noMatch.classList.add('d-none');
        }
    });

    // Sale Details Modal Populator
    $(document).ready(function () {
        $('.btn-view-sale').on('click', function () {
            const invoice = $(this).data('invoice');
            const customer = $(this).data('customer');
            const phone = $(this).data('phone');
            const date = $(this).data('date');
            const subtotal = $(this).data('subtotal');
            const discount = $(this).data('discount');
            const total = $(this).data('total');
            const paid = $(this).data('paid');
            const due = $(this).data('due');
            const status = $(this).data('status');
            const items = $(this).data('items') || [];

            $('#mSaleInvoice').text('Invoice # ' + invoice);
            $('#mSaleCustomer').text(customer);
            $('#mSalePhone').text(phone);
            $('#mSaleDate').text(date);
            $('#mSaleTotal').text(total);
            $('#mSaleSubTotal').text(subtotal);
            $('#mSaleDiscount').text(discount);
            $('#mSaleGrandTotal').text(total);
            $('#mSalePaid').text(paid);
            $('#mSaleNetDue').text(due);
            $('#mSaleDue').text(due);

            const badge = $('#mSaleStatusBadge');
            badge.text(status);
            badge.removeClass('bg-success bg-warning bg-danger text-dark text-white');
            if (status.toLowerCase() === 'paid') {
                badge.addClass('bg-success text-white');
            } else if (status.toLowerCase() === 'partial') {
                badge.addClass('bg-warning text-dark');
            } else {
                badge.addClass('bg-danger text-white');
            }

            let rows = '';
            items.forEach((item, index) => {
                rows += `
                    <tr>
                        <td class="text-muted small">${index + 1}</td>
                        <td class="fw-semibold text-dark">${item.product}</td>
                        <td><span class="badge bg-light text-dark border font-monospace">${item.code || '—'}</span></td>
                        <td class="text-end">Rs. ${parseFloat(item.price).toLocaleString()}</td>
                        <td class="text-center"><span class="badge bg-secondary-subtle text-dark border px-2">${item.quantity}</span></td>
                        <td class="text-center">${item.returned > 0 ? '<span class="badge bg-warning-subtle text-warning border px-2">-' + item.returned + '</span>' : '<span class="text-muted">—</span>'}</td>
                        <td class="text-end fw-bold text-dark">Rs. ${parseFloat(item.total).toLocaleString()}</td>
                    </tr>
                `;
            });

            $('#mSaleItemsBody').html(rows);

            const modal = new bootstrap.Modal(document.getElementById('saleDetailModal'));
            modal.show();
        });
    });

    function confirmDeleteSale(saleId, invoiceNo) {
        if (!confirm(`Are you sure you want to delete Sale ${invoiceNo}?\n\nThis will permanently delete this sale and restore the sold quantities back to product inventory.`)) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/sales/${saleId}`;

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);

        document.body.appendChild(form);
        form.submit();
    }
</script>
@endpush
