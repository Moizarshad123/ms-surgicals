@extends('layouts.app')
@section('title', 'Supplier-Wise Ledger Report - ' . $supplierName)
@section('page-title', 'Supplier-Wise Ledger Report')

@push('styles')
<style>
    .ledger-filter-btn {
        padding: 6px 16px;
        border-radius: 8px;
        font-size: 0.88rem;
        font-weight: 500;
        color: #556270;
        background: #f1f4f8;
        border: 1px solid #d9e2ec;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    .ledger-filter-btn:hover {
        background: #e2e8f0;
        color: #293134;
    }
    .ledger-filter-btn.active {
        background: #0d6efd;
        color: #ffffff;
        border-color: #0d6efd;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(13, 110, 253, 0.25);
    }
    .kpi-card {
        border-radius: 12px;
        border: 1px solid #e9edf2;
        background: #ffffff;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
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

    /* DOS / Classic Ledger Terminal Styling (matching reference image) */
    .classic-ledger-box {
        background-color: #003399; /* Deep blue background from reference image */
        color: #ffffff;
        font-family: 'Courier New', Courier, monospace;
        border-radius: 8px;
        padding: 24px;
        box-shadow: inset 0 0 15px rgba(0,0,0,0.4);
        margin-top: 20px;
        overflow-x: auto;
    }
    .classic-ledger-box .ledger-header-title {
        font-size: 1.15rem;
        font-weight: 700;
        letter-spacing: 1px;
        color: #ffffff;
    }
    .classic-ledger-box .ledger-divider {
        border-top: 2px dashed #6ea8fe;
        margin: 10px 0;
    }
    .classic-ledger-box .ledger-double-divider {
        border-top: 2px solid #ffffff;
        border-bottom: 2px solid #ffffff;
        height: 6px;
        margin: 8px 0;
    }
    .classic-ledger-table {
        width: 100%;
        color: #ffffff;
        font-family: 'Courier New', Courier, monospace;
        font-size: 0.95rem;
        border-collapse: collapse;
    }
    .classic-ledger-table th {
        padding: 6px 10px;
        font-weight: 700;
        letter-spacing: 0.5px;
        border-top: 1px dashed #6ea8fe;
        border-bottom: 1px dashed #6ea8fe;
        white-space: nowrap;
    }
    .classic-ledger-table td {
        padding: 6px 10px;
        white-space: nowrap;
    }
    .classic-ledger-table tr.opening-row td {
        font-weight: 700;
        color: #ffdf7e;
    }
    .classic-ledger-table tr.total-row td {
        font-weight: 700;
        border-top: 2px dashed #ffffff;
        border-bottom: 2px dashed #ffffff;
        color: #ffffff;
    }

    @media print {
        body {
            background: #ffffff !important;
            font-size: 11px;
            color: #000000;
        }
        .header, .menu, .sidebar, .print-hide, .btn, .card-header, form {
            display: none !important;
        }
        .content-body, .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .print-header {
            display: block !important;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .classic-ledger-box {
            background: #ffffff !important;
            color: #000000 !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: 1px solid #000 !important;
        }
        .classic-ledger-table {
            color: #000000 !important;
        }
        .classic-ledger-box .ledger-header-title {
            color: #000000 !important;
        }
        .classic-ledger-table th, .classic-ledger-table td {
            color: #000000 !important;
            border-color: #000 !important;
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
            <h3 class="fw-bold mb-0">SUPPLIER WISE LEDGER REPORT</h3>
            <p class="mb-0 text-muted">TG SURGICALS</p>
        </div>
        <div class="text-end">
            <div class="fw-semibold">SUPPLIER NAME: {{ strtoupper($supplierName) }}</div>
            <div class="small">PERIOD: {{ $start->format('d-m-Y') }} TO {{ $end->format('d-m-Y') }}</div>
            <small class="text-muted">Generated: {{ now()->format('d M Y, h:i A') }}</small>
        </div>
    </div>
</div>

<div class="container-fluid px-0">

    <!-- Header Actions Card -->
    <div class="card border-0 shadow-sm mb-4 print-hide" style="border-radius: 12px;">
        <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('reports.supplier-ledger') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Back to Supplier List
                </a>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Supplier-Wise Ledger Report</h5>
                    <div class="text-muted small">
                        Supplier: <strong>{{ $supplierName }}</strong>
                    </div>
                </div>
            </div>

            <!-- Date Filter Quick Tabs (1 Month, 3 Months, 6 Months, 1 Year) -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small fw-semibold me-1"><i class="bi bi-funnel me-1"></i>Period:</span>
                <a href="{{ route('reports.supplier-ledger.show', ['supplier' => $supplierName, 'period' => '1m']) }}" 
                   class="ledger-filter-btn {{ $period === '1m' ? 'active' : '' }}">
                    1 Month
                </a>
                <a href="{{ route('reports.supplier-ledger.show', ['supplier' => $supplierName, 'period' => '3m']) }}" 
                   class="ledger-filter-btn {{ $period === '3m' ? 'active' : '' }}">
                    3 Months
                </a>
                <a href="{{ route('reports.supplier-ledger.show', ['supplier' => $supplierName, 'period' => '6m']) }}" 
                   class="ledger-filter-btn {{ $period === '6m' ? 'active' : '' }}">
                    6 Months
                </a>
                <a href="{{ route('reports.supplier-ledger.show', ['supplier' => $supplierName, 'period' => '1y']) }}" 
                   class="ledger-filter-btn {{ $period === '1y' ? 'active' : '' }}">
                    1 Year
                </a>
                <button type="button" class="btn btn-sm btn-outline-dark ms-2 d-flex align-items-center gap-1" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Ledger
                </button>
            </div>
        </div>
    </div>

    <!-- Settlement Alert Banner -->
    <div class="mb-4 print-hide">
        @if($closingBalance > 0)
            <div class="alert alert-danger d-flex align-items-center justify-content-between shadow-sm py-3 px-4 mb-0" style="border-radius: 12px; border-left: 5px solid #dc3545;">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-exclamation-triangle-fill fs-3 text-danger"></i>
                    <div>
                        <h6 class="fw-bold mb-0 text-danger">OUTSTANDING BALANCE PAYABLE</h6>
                        <span class="small text-dark">We have an outstanding payable of <strong>Rs. {{ number_format($closingBalance, 2) }}</strong> to this supplier for the selected period.</span>
                    </div>
                </div>
                <span class="badge bg-danger fs-6 px-3 py-2">Payable: Rs. {{ number_format($closingBalance) }}</span>
            </div>
        @elseif($closingBalance < 0)
            <div class="alert alert-info d-flex align-items-center justify-content-between shadow-sm py-3 px-4 mb-0" style="border-radius: 12px; border-left: 5px solid #0dcaf0;">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-info-circle-fill fs-3 text-info"></i>
                    <div>
                        <h6 class="fw-bold mb-0 text-info">ADVANCE PAID TO SUPPLIER</h6>
                        <span class="small text-dark">We have an advance balance of <strong>Rs. {{ number_format(abs($closingBalance), 2) }}</strong> with this supplier.</span>
                    </div>
                </div>
                <span class="badge bg-info text-dark fs-6 px-3 py-2">Advance: Rs. {{ number_format(abs($closingBalance)) }}</span>
            </div>
        @else
            <div class="alert alert-success d-flex align-items-center justify-content-between shadow-sm py-3 px-4 mb-0" style="border-radius: 12px; border-left: 5px solid #198754;">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                    <div>
                        <h6 class="fw-bold mb-0 text-success">ALL PAYMENTS SETTLED</h6>
                        <span class="small text-dark">All purchases are completely cleared. There is no outstanding balance due to this supplier.</span>
                    </div>
                </div>
                <span class="badge bg-success fs-6 px-3 py-2">Settled (Rs. 0.00)</span>
            </div>
        @endif
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4 print-hide">
        <div class="col-xl-3 col-sm-6">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">Opening Balance</div>
                        <h5 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($openingBalance, 2) }}</h5>
                        <small class="text-muted">Prior to {{ $start->format('d M, Y') }}</small>
                    </div>
                    <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">Total Purchases</div>
                        <h5 class="fw-bold text-primary mt-1 mb-0">Rs. {{ number_format($totalDebit, 2) }}</h5>
                        <small class="text-muted">New bills in period</small>
                    </div>
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-credit-card"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">Total Payments Made</div>
                        <h5 class="fw-bold text-success mt-1 mb-0">Rs. {{ number_format($totalCredit, 2) }}</h5>
                        <small class="text-muted">Paid & returns in period</small>
                    </div>
                    <div class="kpi-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="text-muted small fw-semibold">Closing Balance</div>
                        <h5 class="fw-bold mt-1 mb-0 {{ $closingBalance > 0 ? 'text-danger' : 'text-success' }}">
                            Rs. {{ number_format($closingBalance, 2) }}
                        </h5>
                        <small class="text-muted">
                            {{ $closingBalance > 0 ? 'Outstanding payable' : ($closingBalance < 0 ? 'Advance' : 'Settled') }}
                        </small>
                    </div>
                    <div class="kpi-icon {{ $closingBalance > 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-success bg-opacity-10 text-success' }}">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- CLASSIC BLUE LEDGER REPORT TABLE (MATCHING REFERENCE IMAGE)     -->
    <!-- ============================================================== -->
    <div class="classic-ledger-box">
        <!-- Title & Metadata Header -->
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <div class="ledger-header-title">SUPPLIER WISE LEDGER REPORT</div>
                <div class="small" style="color: #cfe2ff;">
                    FROM {{ $start->format('d-m-Y') }} &nbsp; TO &nbsp; {{ $end->format('d-m-Y') }}
                </div>
            </div>
            <div class="text-end small" style="color: #cfe2ff;">
                <span>PAGE NO &nbsp; 1</span>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
            <div>
                <span class="fw-bold" style="color: #ffdf7e;">SUPPLIER NAME : </span>
                <span class="fw-bold">{{ strtoupper($supplierName) }}</span>
            </div>
            <div class="small" style="color: #cfe2ff;">
                <span>TYPE : CREDITOR</span>
            </div>
        </div>

        <!-- ASCII / Dashed Separator -->
        <div class="ledger-divider"></div>

        <div class="table-responsive">
            <table class="classic-ledger-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">S#.</th>
                        <th style="width: 130px;">DATE</th>
                        <th style="width: 120px;">TRANSAC</th>
                        <th style="width: 110px;">DOC-NO</th>
                        <th class="text-end" style="width: 160px;">INVOICE-AMT</th>
                        <th class="text-end" style="width: 170px;">RETURN+REC_PAY</th>
                        <th class="text-end" style="width: 160px;">BAL-AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row (as in reference image) -->
                    <tr class="opening-row">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-end" colspan="2" style="color: #ffdf7e; font-weight: bold; letter-spacing: 0.5px;">
                            Opening Balance..
                        </td>
                        <td></td>
                        <td class="text-end" style="font-weight: bold; color: #ffdf7e;">
                            {{ number_format($openingBalance, 1) }}
                        </td>
                    </tr>

                    @forelse($transactions as $index => $tx)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ is_string($tx['date']) ? $tx['date'] : $tx['date']->format('d-m-Y') }}</td>
                            <td>
                                @if($tx['type'] === 'INVOICE')
                                    INVOICE
                                @elseif($tx['type'] === 'PAYMENT')
                                    RECIEPT
                                @else
                                    RETURN
                                @endif
                            </td>
                            <td>{{ $tx['doc_no'] }}</td>
                            <td class="text-end">
                                {{ $tx['debit'] > 0 ? number_format($tx['debit'], 2) : '' }}
                            </td>
                            <td class="text-end">
                                {{ $tx['credit'] > 0 ? number_format($tx['credit'], 2) : '' }}
                            </td>
                            <td class="text-end">
                                {{ number_format($tx['balance'], 1) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4" style="color: #cfe2ff;">
                                NO TRANSACTIONS RECORDED IN SELECTED PERIOD.
                            </td>
                        </tr>
                    @endforelse

                    <!-- TOTAL(s) ROW (as in reference image) -->
                    <tr class="total-row">
                        <td></td>
                        <td colspan="3" class="fw-bold">
                            TOTAL (s):
                        </td>
                        <td class="text-end fw-bold">
                            {{ number_format($totalDebit, 2) }}
                        </td>
                        <td class="text-end fw-bold">
                            {{ number_format($totalCredit, 2) }}
                        </td>
                        <td class="text-end fw-bold">
                            {{ number_format($closingBalance, 1) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Bottom Border Divider -->
        <div class="ledger-divider"></div>
        <div class="d-flex justify-content-between align-items-center text-muted small mt-2" style="color: #9ec5fe !important;">
            <span>CLOSING BALANCE: Rs. {{ number_format($closingBalance, 2) }} ({{ $closingBalance > 0 ? 'OUTSTANDING' : ($closingBalance < 0 ? 'ADVANCE' : 'SETTLED') }})</span>
            <span>END OF REPORT</span>
        </div>
    </div>
</div>
@endsection

