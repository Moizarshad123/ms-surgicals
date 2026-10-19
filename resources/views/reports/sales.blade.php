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
    #salesTable th {
        font-weight: 600;
        font-size: 0.84rem;
        color: #556270;
        background: #fbfbfc;
        border-bottom: 2px solid #edf1f6;
    }
    #salesTable td {
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
                <div class="text-muted small">Analyze transaction performance, item volumes, discounts, and customer billing.</div>
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
                    <label class="form-label small fw-semibold text-dark mb-1">Customer / Invoice</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Invoice # or Customer name/phone" value="{{ $search ?? '' }}">
                </div>

                <div class="col-lg-3 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-theme-primary px-3 flex-grow-1 d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-funnel"></i> Apply Filter
                    </button>
                    <a href="{{ route('reports.sales') }}" class="btn btn-sm btn-outline-secondary px-2" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

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
                    <div class="text-muted small fw-semibold">Discounts Given</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($totalDiscount) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(234,68,68,0.1); color: #ea4444;">
                    <i class="bi bi-tag"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl col-md-6 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Average Order</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($averageOrder) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(5,177,113,0.1); color: #05b171;">
                    <i class="bi bi-pie-chart"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sales Table Card -->
<div class="card report-card">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-0">Transactions Log</h5>
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
                        @php $due = $sale->due_amount; @endphp
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
        $('#salesSearchInput').on('input', function () {
            const query = $(this).val().toLowerCase().trim();
            let visibleCount = 0;

            $('.sale-row').each(function () {
                const searchData = $(this).data('search') || '';
                if (!query || searchData.includes(query)) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });

            $('#visibleSalesCount').text(visibleCount);
            if (visibleCount === 0 && $('.sale-row').length > 0) {
                $('#noSearchMatch').removeClass('d-none');
            } else {
                $('#noSearchMatch').addClass('d-none');
            }
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
