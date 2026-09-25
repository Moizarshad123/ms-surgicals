@extends('layouts.app')
@section('title', 'Purchase Report')
@section('page-title', 'Purchase Report')

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
    #purchasesTable th {
        font-weight: 600;
        font-size: 0.84rem;
        color: #556270;
        background: #fbfbfc;
        border-bottom: 2px solid #edf1f6;
    }
    #purchasesTable td {
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
        .header, .sidebar, .filter-card, .btn, .search-group, .print-hide, .modal {
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
            <p class="mb-0 text-muted">Purchase &amp; Inventory Restock Report</p>
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
                <i class="bi bi-credit-card-2-front fs-4"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0">Purchase &amp; Restock Report</h5>
                <div class="text-muted small">Analyze inventory acquisition costs, supplier billing, and restocked product volumes.</div>
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
        <form method="GET" action="{{ route('reports.purchases') }}" id="filterForm">
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

                {{-- Supplier Search Filter --}}
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small fw-semibold text-dark mb-1">Supplier Name</label>
                    <input type="text" name="supplier" class="form-control form-control-sm" placeholder="Filter by supplier name..." value="{{ $supplier ?? '' }}">
                </div>

                <div class="col-lg-3 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-theme-primary px-3 flex-grow-1 d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-funnel"></i> Apply Filter
                    </button>
                    <a href="{{ route('reports.purchases') }}" class="btn btn-sm btn-outline-secondary px-2" title="Reset Filters">
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
                    <div class="text-muted small fw-semibold">Total Purchases Spend</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($totalPurchases, 2) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(255,110,64,0.1); color: #ff6e40;">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Purchase Invoices</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalInvoices) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(37,194,227,0.1); color: #25c2e3;">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Units Restocked</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalUnitsRestocked) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(108,92,231,0.1); color: #6c5ce7;">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Average Invoice</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($averageOrder, 2) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(5,177,113,0.1); color: #05b171;">
                    <i class="bi bi-pie-chart"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Purchases Table Card -->
<div class="card report-card">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-0">Purchases Log</h5>
            <div class="text-muted small">Showing <span id="visiblePurchaseCount">{{ $purchases->count() }}</span> purchase records</div>
        </div>
        <div class="search-group print-hide" style="width: 250px;">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="purchaseSearchInput" class="form-control form-control-sm" placeholder="Quick search in table...">
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="purchasesTable">
                <thead>
                    <tr>
                        <th style="width: 170px;">Reference No</th>
                        <th>Supplier</th>
                        <th>Purchase Date</th>
                        <th class="text-center">Items Restocked</th>
                        <th class="text-end">Invoice Total</th>
                        <th class="text-end print-hide" style="width: 110px;">Details</th>
                    </tr>
                </thead>
                <tbody id="purchasesTableBody">
                    @forelse ($purchases as $purchase)
                        @php
                            $itemsPayload = $purchase->items->map(function($it) {
                                return [
                                    'product' => $it->product ? $it->product->name : 'Deleted Product',
                                    'code' => $it->product ? $it->product->code : '-',
                                    'quantity' => $it->quantity,
                                    'price' => (float) $it->price,
                                    'total' => (float) $it->total,
                                ];
                            });
                        @endphp
                        <tr class="purchase-row" data-search="{{ strtolower($purchase->reference_no . ' ' . ($purchase->supplier_name ?? '') . ' ' . $purchase->purchase_date) }}">
                            <td>
                                <span class="badge bg-light text-dark font-monospace border px-2 py-1">
                                    <i class="bi bi-hash text-muted"></i>{{ $purchase->reference_no }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $purchase->supplier_name ?: 'General Supplier' }}</div>
                            </td>
                            <td>
                                <div class="text-dark small fw-medium">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M, Y') }}</div>
                                <div class="text-muted small" style="font-size: 0.72rem;">{{ \Carbon\Carbon::parse($purchase->purchase_date)->diffForHumans() }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary border px-2 py-1" style="background: rgba(255,110,64,0.1); color: #ff6e40 !important;">
                                    {{ $purchase->items->sum('quantity') }} units ({{ $purchase->items->count() }} items)
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                Rs. {{ number_format($purchase->total, 2) }}
                            </td>
                            <td class="text-end print-hide">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-view-purchase py-1 px-2"
                                        data-ref="{{ $purchase->reference_no }}"
                                        data-supplier="{{ $purchase->supplier_name ?: 'General Supplier' }}"
                                        data-date="{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M, Y') }}"
                                        data-total="Rs. {{ number_format($purchase->total, 2) }}"
                                        data-items='@json($itemsPayload)'>
                                    <i class="bi bi-eye me-1"></i> View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                No purchase records found for this selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($purchases->count() > 0)
                    <tfoot class="table-light border-top">
                        <tr class="fw-bold text-dark">
                            <td colspan="3" class="text-end">Summary Totals:</td>
                            <td class="text-center">{{ number_format($totalUnitsRestocked) }} units</td>
                            <td class="text-end text-success fs-6">Rs. {{ number_format($totalPurchases, 2) }}</td>
                            <td class="print-hide"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <!-- No Search Results Row -->
        <div id="noSearchMatch" class="py-5 text-center text-muted d-none">
            <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
            <h6 class="fw-semibold text-dark">No matching purchases found</h6>
            <p class="small mb-0">Try searching with a different reference number or supplier name.</p>
        </div>
    </div>
</div>

<!-- PURCHASE DETAILS MODAL -->
<div class="modal fade" id="purchaseDetailModal" tabindex="-1" aria-labelledby="purchaseDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 12px 12px 0 0;">
                <div>
                    <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="purchaseDetailModalLabel">
                        <i class="bi bi-receipt text-primary"></i> Purchase Invoice Details
                    </h6>
                    <div class="text-muted small mt-1" id="modalRefNo">REF</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Meta Badges -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="text-muted small">Supplier</div>
                            <div class="fw-bold text-dark mt-1" id="modalSupplier">-</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="text-muted small">Purchase Date</div>
                            <div class="fw-bold text-dark mt-1" id="modalDate">-</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="text-muted small">Invoice Total</div>
                            <div class="fw-bold text-success fs-6 mt-1" id="modalTotal">-</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-2">Purchased Products Breakdown</h6>
                <div class="table-responsive border rounded-3">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>SKU / Code</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="modalItemsBody">
                        </tbody>
                    </table>
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
        $('#purchaseSearchInput').on('input', function () {
            const query = $(this).val().toLowerCase().trim();
            let visibleCount = 0;

            $('.purchase-row').each(function () {
                const searchData = $(this).data('search') || '';
                if (!query || searchData.includes(query)) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });

            $('#visiblePurchaseCount').text(visibleCount);
            if (visibleCount === 0 && $('.purchase-row').length > 0) {
                $('#noSearchMatch').removeClass('d-none');
            } else {
                $('#noSearchMatch').addClass('d-none');
            }
        });

        // Open Purchase Details Modal
        $('.btn-view-purchase').on('click', function () {
            const ref = $(this).data('ref');
            const supplier = $(this).data('supplier');
            const date = $(this).data('date');
            const total = $(this).data('total');
            const items = $(this).data('items') || [];

            $('#modalRefNo').text('Invoice Reference: ' + ref);
            $('#modalSupplier').text(supplier);
            $('#modalDate').text(date);
            $('#modalTotal').text(total);

            let rows = '';
            items.forEach((item, index) => {
                rows += `
                    <tr>
                        <td class="text-muted small">${index + 1}</td>
                        <td class="fw-semibold text-dark">${item.product}</td>
                        <td><span class="badge bg-light text-dark border font-monospace">${item.code}</span></td>
                        <td class="text-end">Rs. ${parseFloat(item.price).toFixed(2)}</td>
                        <td class="text-center"><span class="badge bg-secondary-subtle text-dark border px-2">${item.quantity}</span></td>
                        <td class="text-end fw-bold text-dark">Rs. ${parseFloat(item.total).toFixed(2)}</td>
                    </tr>
                `;
            });

            $('#modalItemsBody').html(rows);

            const modal = new bootstrap.Modal(document.getElementById('purchaseDetailModal'));
            modal.show();
        });
    });
</script>
@endpush
