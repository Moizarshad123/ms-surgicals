@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
    .dash-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        background: #ffffff;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .dash-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0,0,0,0.05);
    }
    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
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
    .rank-badge {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        font-weight: 700;
    }
    .rank-1 { background: #ffeaa7; color: #d63031; }
    .rank-2 { background: #dfe6e9; color: #2d3436; }
    .rank-3 { background: #ffdfba; color: #d35400; }
    .rank-other { background: #f1f2f6; color: #747d8c; }

    .mini-stat-bar {
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #edf1f6;
        padding: 10px 14px;
    }
</style>
@endpush

@section('content')
<!-- Welcome Header Banner with Quick Actions -->
<div class="card dash-card mb-4 border-0" style="background: linear-gradient(135deg, #ffffff 0%, #fff7f4 100%); border: 1px solid #ffe6dc !important;">
    <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary border" style="background: rgba(255,110,64,0.1); color: #ff6e40 !important;">
                    <i class="bi bi-shield-check me-1"></i> Operational Dashboard
                </span>
                <span class="text-muted small">&bull; {{ now()->format('l, d F Y') }}</span>
            </div>
            <h4 class="fw-bold text-dark mb-1">Welcome back, {{ Auth::user()->name ?? 'Manager' }}!</h4>
            <p class="text-muted small mb-0">Here is the real-time financial, sales, inventory, and procurement overview.</p>
        </div>

        <!-- Quick Action Buttons -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('pos.index') }}" class="btn btn-theme-primary px-3 py-2 d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-cart fs-5"></i> Open POS
            </a>
            <a href="{{ route('products.create') }}" class="btn btn-sm btn-outline-dark px-3 py-2 d-flex align-items-center gap-1 bg-white">
                <i class="bi bi-plus-circle"></i> Add Product
            </a>
            <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-outline-dark px-3 py-2 d-flex align-items-center gap-1 bg-white">
                <i class="bi bi-bag-plus"></i> New Purchase
            </a>
            <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-outline-dark px-3 py-2 d-flex align-items-center gap-1 bg-white">
                <i class="bi bi-wallet2"></i> Add Expense
            </a>
        </div>
    </div>
</div>

<!-- Primary Financial KPIs Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card dash-card dash-kpi">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Today's Sales Revenue</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($todaySales) }}</h4>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-receipt text-primary me-1"></i>{{ $todaySalesCount }} {{ Str::plural('order', $todaySalesCount) }} today
                    </div>
                </div>
                <div class="kpi-icon" style="background: rgba(255,110,64,0.1); color: #ff6e40;">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card dash-card dash-kpi">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Today's Purchases</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($todayPurchases) }}</h4>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-truck text-info me-1"></i>{{ $todayPurchasesCount }} restock {{ Str::plural('order', $todayPurchasesCount) }}
                    </div>
                </div>
                <div class="kpi-icon" style="background: rgba(37,194,227,0.1); color: #25c2e3;">
                    <i class="bi bi-credit-card"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card dash-card dash-kpi">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Today's Expenses</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($todayExpenses) }}</h4>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-receipt-cutoff text-danger me-1"></i>{{ $todayExpensesCount }} expense {{ Str::plural('entry', $todayExpensesCount) }}
                    </div>
                </div>
                <div class="kpi-icon" style="background: rgba(234,68,68,0.1); color: #ea4444;">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card dash-card dash-kpi">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Net Daily Balance</div>
                    <h4 class="fw-bold mt-1 mb-0 {{ $todayNetCash >= 0 ? 'text-success' : 'text-danger' }}">
                        Rs. {{ number_format($todayNetCash) }}
                    </h4>
                    <div class="small mt-1">
                        @if($todayNetCash >= 0)
                            <span class="badge bg-success-subtle text-success border"><i class="bi bi-arrow-up-right me-1"></i>Positive Cashflow</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-arrow-down-right me-1"></i>Negative Outflow</span>
                        @endif
                    </div>
                </div>
                <div class="kpi-icon" style="background: rgba(5,177,113,0.1); color: #05b171;">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section Row -->
<div class="row g-3 mb-4">
    <!-- Area Chart: 7-Day Performance Trend -->
    <div class="col-lg-8">
        <div class="card dash-card h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-activity text-primary"></i> 7-Day Financial Performance
                    </h6>
                    <div class="text-muted small">Daily comparison of Sales Revenue, Restock Purchases, and Operating Expenses.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border px-2 py-1 small">
                        <i class="bi bi-dot text-primary fs-6"></i> Sales
                    </span>
                    <span class="badge bg-light text-dark border px-2 py-1 small">
                        <i class="bi bi-dot text-info fs-6"></i> Purchases
                    </span>
                    <span class="badge bg-light text-dark border px-2 py-1 small">
                        <i class="bi bi-dot text-danger fs-6"></i> Expenses
                    </span>
                </div>
            </div>
            <div class="card-body p-3">
                <div id="revenueTrendChart" style="min-height: 310px;"></div>
            </div>
        </div>
    </div>

    <!-- Donut/Pie Chart: Product Inventory Distribution by Category -->
    <div class="col-lg-4">
        <div class="card dash-card h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-pie-chart text-primary"></i> Products by Category
                    </h6>
                    <div class="text-muted small">Inventory distribution across categories</div>
                </div>
                <a href="{{ route('categories.index') }}" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Manage Categories">
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center">
                @if(count($donutCategorySeries) > 0 && array_sum($donutCategorySeries) > 0)
                    <div id="categoryDonutChart" style="width: 100%; min-height: 270px;"></div>
                @else
                    <div class="py-5 text-center text-muted">
                        <i class="bi bi-pie-chart fs-1 d-block mb-2 text-secondary"></i>
                        <p class="small mb-0">No categorized products available.</p>
                    </div>
                @endif
            </div>
            <div class="card-footer bg-white border-top py-2 px-3 text-center">
                <small class="text-muted">Total Catalog: <strong>{{ $totalProducts }}</strong> items in <strong>{{ $totalCategories }}</strong> categories</small>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Widgets Row: Recent Sales & Top Products / Low Stock Alerts -->
<div class="row g-3 mb-4">
    <!-- Left: Recent Sales Transactions -->
    <div class="col-lg-7">
        <div class="card dash-card h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-primary"></i> Recent Sales Orders
                    </h6>
                    <div class="text-muted small">Latest transactions recorded through the POS terminal.</div>
                </div>
                <a href="{{ route('reports.sales') }}" class="btn btn-sm btn-outline-secondary">
                    View Sales Report <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th class="text-center">Items</th>
                                <th class="text-end">Total</th>
                                <th>Time</th>
                                <th class="text-center" style="width: 50px;">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSales as $sale)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark font-monospace border px-2 py-1">
                                            #{{ $sale->invoice_no }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-dark border">{{ $sale->items->sum('quantity') }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        Rs. {{ number_format($sale->total) }}
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $sale->created_at->setTimezone('Asia/Karachi')->format('d M, h:i A') }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a target="_blank" href="{{ route('pos.invoice', $sale->id) }}" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Print Invoice Receipt">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="confirmDeleteSale({{ $sale->id }}, '{{ $sale->invoice_no }}')" title="Delete Sale & Restore Stock">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                                        No sales transactions logged today yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Top Products & Low Stock Alerts -->
    <div class="col-lg-5">
        <!-- Top Selling Products -->
        <div class="card dash-card mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-trophy text-primary"></i> Top Selling Products
                </h6>
                <div class="text-muted small">Highest sales volume products</div>
            </div>
            <div class="card-body p-3">
                @forelse ($topProducts as $index => $item)
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom {{ $loop->last ? 'border-0' : '' }}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rank-badge {{ $index == 0 ? 'rank-1' : ($index == 1 ? 'rank-2' : ($index == 2 ? 'rank-3' : 'rank-other')) }}">
                                #{{ $index + 1 }}
                            </span>
                            <div>
                                <div class="fw-semibold text-dark small text-truncate" style="max-width: 170px;">
                                    {{ $item->product->name ?? 'Deleted Product' }}
                                </div>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    {{ $item->product && $item->product->code ? '#' . $item->product->code : '' }}
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-dark small">{{ $item->total_qty }} sold</div>
                            <div class="text-success small" style="font-size: 0.75rem;">Rs. {{ number_format($item->total_revenue) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-3 small">
                        No sales data available yet to rank top products.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="card dash-card">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-danger-subtle text-danger p-1 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 0.75rem;"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Low Stock Inventory Alerts</h6>
                        <div class="text-muted small">{{ $totalLowStock }} items need restocking</div>
                    </div>
                </div>
                <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size: 0.75rem;">
                    <i class="bi bi-plus-lg me-1"></i> Restock
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Threshold</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lowStockProducts as $product)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark small text-truncate" style="max-width: 160px;" title="{{ $product->name }}">
                                            {{ $product->name }}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ $product->category->name ?? 'General' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger text-white">{{ $product->stock }}</span>
                                    </td>
                                    <td class="text-center text-muted small">
                                        {{ $product->alert_quantity }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3 small">
                                        <i class="bi bi-check2-circle text-success fs-4 d-block mb-1"></i>
                                        All inventory items are currently healthy!
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

<!-- Secondary Summary Ribbon -->
<div class="row g-3">
    <div class="col-sm-6 col-md-3">
        <div class="mini-stat-bar d-flex align-items-center justify-content-between">
            <span class="text-muted small">This Month's Sales</span>
            <span class="fw-bold text-dark fs-6">Rs. {{ number_format($monthSales) }}</span>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="mini-stat-bar d-flex align-items-center justify-content-between">
            <span class="text-muted small">Total Inventory Units</span>
            <span class="fw-bold text-dark fs-6">{{ number_format($totalInventory) }}</span>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="mini-stat-bar d-flex align-items-center justify-content-between">
            <span class="text-muted small">Product Categories</span>
            <span class="fw-bold text-dark fs-6">{{ number_format($totalCategories) }}</span>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="mini-stat-bar d-flex align-items-center justify-content-between">
            <span class="text-muted small">Registered Customers</span>
            <span class="fw-bold text-dark fs-6">{{ number_format($totalCustomers) }}</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // -----------------------------------------------------------------
        // 1. 7-Day Performance Trend Chart (Area Chart)
        // -----------------------------------------------------------------
        const trendOptions = {
            series: [
                {
                    name: 'Sales Revenue',
                    data: @json($chartSales)
                },
                {
                    name: 'Purchases (Restock)',
                    data: @json($chartPurchases)
                },
                {
                    name: 'Expenses',
                    data: @json($chartExpenses)
                }
            ],
            chart: {
                type: 'area',
                height: 310,
                toolbar: {
                    show: false
                },
                fontFamily: 'inherit'
            },
            colors: ['#ff6e40', '#25c2e3', '#ea4444'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2.5
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 95, 100]
                }
            },
            xaxis: {
                categories: @json($chartLabels),
                labels: {
                    style: {
                        colors: '#8c98a4',
                        fontSize: '12px'
                    }
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return "Rs. " + Number(value).toLocaleString();
                    },
                    style: {
                        colors: '#8c98a4',
                        fontSize: '11px'
                    }
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (y) {
                        if (typeof y !== "undefined") {
                            return "Rs. " + y.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                        return y;
                    }
                }
            },
            legend: {
                show: false
            },
            grid: {
                borderColor: '#f1f4f8',
                strokeDashArray: 4
            }
        };

        const trendChartEl = document.querySelector("#revenueTrendChart");
        if (trendChartEl) {
            const trendChart = new ApexCharts(trendChartEl, trendOptions);
            trendChart.render();
        }

        // -----------------------------------------------------------------
        // 2. Product Categories Distribution Donut / Pie Chart
        // -----------------------------------------------------------------
        const donutSeries = @json($donutCategorySeries);
        const donutLabels = @json($donutCategoryLabels);

        if (donutSeries && donutSeries.length > 0) {
            const donutOptions = {
                series: donutSeries,
                chart: {
                    type: 'donut',
                    height: 270,
                    fontFamily: 'inherit'
                },
                labels: donutLabels,
                colors: ['#ff6e40', '#25c2e3', '#05b171', '#6c5ce7', '#fbc531', '#e84118'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Products',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    position: 'bottom',
                    fontSize: '12px',
                    markers: {
                        width: 10,
                        height: 10,
                        radius: 3
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val + " items";
                        }
                    }
                }
            };

            const donutChartEl = document.querySelector("#categoryDonutChart");
            if (donutChartEl) {
                const donutChart = new ApexCharts(donutChartEl, donutOptions);
                donutChart.render();
            }
        }
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
