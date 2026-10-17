@extends('layouts.app')
@section('title', 'Bank Statement & Report')
@section('page-title', 'Bank Statement & Report')

@push('styles')
<style>
    .report-card {
        border-radius: 12px;
        border: 1px solid #e9edf2;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
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
        .report-card, .card {
            border: none !important;
            box-shadow: none !important;
        }
        .print-header {
            display: block !important;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 11px !important;
        }
        th, td {
            border: 1px solid #000 !important;
            padding: 4px 6px !important;
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
            <p class="mb-0 text-muted">Bank Statement &amp; Financial Ledger</p>
        </div>
        <div class="text-end">
            <div class="fw-semibold">Bank: {{ $selectedBank ? $selectedBank->name : 'All Banks (Bank Islami & Meezan Bank)' }}</div>
            <div class="small">Period: {{ $start->format('d M, Y') }} &mdash; {{ $end->format('d M, Y') }}</div>
            <small class="text-muted">Generated: {{ now()->format('d M Y, h:i A') }}</small>
        </div>
    </div>
</div>

<!-- Header Card -->
<div class="card report-card mb-4 print-hide">
    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 p-2 text-white d-flex align-items-center justify-content-center" style="background: #0d6efd; width: 44px; height: 44px;">
                <i class="bi bi-bank fs-4"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0">Bank Statement &amp; Ledger Report</h5>
                <div class="text-muted small">
                    Tracking Bank Islami and Meezan Bank deposits, withdrawals, party disbursements, and running balances.
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-secondary border px-3 py-2">
                <i class="bi bi-calendar3 me-1 text-primary"></i> {{ $start->format('d M, Y') }} &mdash; {{ $end->format('d M, Y') }}
            </span>
            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Statement
            </button>
        </div>
    </div>
</div>

<!-- Filter Controls Card -->
<div class="card report-card mb-4 print-hide">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.bank') }}" id="bankFilterForm">
            <div class="row g-2 align-items-end">
                <!-- Period Preset -->
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold text-dark mb-1">Period Preset</label>
                    <select name="period" id="periodSelect" class="form-select form-select-sm" onchange="toggleCustomDates()">
                        <option value="daily" @selected($period=='daily')>Daily (Single Day)</option>
                        <option value="weekly" @selected($period=='weekly')>Weekly</option>
                        <option value="monthly" @selected($period=='monthly')>Monthly</option>
                        <option value="yearly" @selected($period=='yearly')>Yearly</option>
                        <option value="custom" @selected($period=='custom')>Custom Date Range</option>
                    </select>
                </div>

                <!-- Single Date input (For daily/weekly/monthly/yearly) -->
                <div class="col-lg-2 col-md-4" id="singleDateCol">
                    <label class="form-label small fw-semibold text-dark mb-1">Reference Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $date ?: now()->toDateString() }}">
                </div>

                <!-- Custom Start Date -->
                <div class="col-lg-2 col-md-4 custom-date-field d-none">
                    <label class="form-label small fw-semibold text-dark mb-1">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>

                <!-- Custom End Date -->
                <div class="col-lg-2 col-md-4 custom-date-field d-none">
                    <label class="form-label small fw-semibold text-dark mb-1">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>

                <!-- Bank Filter -->
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold text-dark mb-1">Bank</label>
                    <select name="bank_id" class="form-select form-select-sm">
                        <option value="">All Banks</option>
                        @foreach($banks as $b)
                            <option value="{{ $b->id }}" @selected($bankId == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Transaction Type Filter -->
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold text-dark mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Transactions</option>
                        <option value="credit" @selected($type === 'credit')>Credit (Received +)</option>
                        <option value="debit" @selected($type === 'debit')>Debit (Paid -)</option>
                    </select>
                </div>

                <!-- Search -->
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold text-dark mb-1">Search Party / Cheque</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Party or Cheque #">
                </div>

                <!-- Submit & Reset -->
                <div class="col-lg-2 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill">Apply Filter</button>
                    <a href="{{ route('reports.bank') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- KPI Metrics Summary Row -->
<div class="row g-3 mb-4">
    <!-- Opening Balance -->
    <div class="col-xl col-md-4 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Opening Balance</div>
                    <h5 class="fw-bold text-dark mt-1 mb-0">Rs. {{ number_format($openingBalance) }}</h5>
                    <small class="text-muted">Prior to {{ $start->format('d M') }}</small>
                </div>
                <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Inflow / Credit -->
    <div class="col-xl col-md-4 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Total Received (Credit)</div>
                    <h5 class="fw-bold text-success mt-1 mb-0">+ Rs. {{ number_format($totalCredit) }}</h5>
                    <small class="text-muted">Total deposits / in</small>
                </div>
                <div class="kpi-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-arrow-down-left"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Outflow / Debit -->
    <div class="col-xl col-md-4 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Total Paid (Debit)</div>
                    <h5 class="fw-bold text-danger mt-1 mb-0">- Rs. {{ number_format($totalDebit) }}</h5>
                    <small class="text-muted">Total payments / out</small>
                </div>
                <div class="kpi-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-arrow-up-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Net Change -->
    <div class="col-xl col-md-6 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Net Flow</div>
                    <h5 class="fw-bold mt-1 mb-0 {{ $netChange >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $netChange >= 0 ? '+' : '' }}Rs. {{ number_format($netChange) }}
                    </h5>
                    <small class="text-muted">Inflow minus outflow</small>
                </div>
                <div class="kpi-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Closing Balance -->
    <div class="col-xl col-md-6 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Closing Balance</div>
                    <h5 class="fw-bold text-primary mt-1 mb-0">Rs. {{ number_format($closingBalance) }}</h5>
                    <small class="text-muted">As of {{ $end->format('d M, Y') }}</small>
                </div>
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statement Table Card -->
<div class="card report-card">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-0">Account Statement &amp; Running Ledger</h5>
            <div class="text-muted small">
                Showing {{ $statementRows->count() }} transaction entries for period {{ $start->format('d M, Y') }} &mdash; {{ $end->format('d M, Y') }}
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3" style="width: 140px;">Date &amp; Time</th>
                        <th>Bank</th>
                        <th>Party / Person Name (Kis sa li / Kise di)</th>
                        <th>Ref / Cheque #</th>
                        <th>Category</th>
                        <th class="text-end text-success">Credit (In +)</th>
                        <th class="text-end text-danger">Debit (Out -)</th>
                        <th class="text-end pe-3">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row -->
                    <tr class="table-light fw-bold">
                        <td class="ps-3 text-muted">{{ $start->format('d M, Y') }}</td>
                        <td colspan="4" class="text-uppercase text-secondary">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Opening Balance Brought Forward
                        </td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end pe-3 fw-bold text-dark">
                            Rs. {{ number_format($openingBalance) }}
                        </td>
                    </tr>

                    @forelse($statementRows as $tx)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold text-dark">{{ $tx->transaction_date->format('d M, Y') }}</div>
                                <div class="small text-muted">{{ $tx->transaction_time ? date('h:i A', strtotime($tx->transaction_time)) : '—' }}</div>
                            </td>
                            <td>
                                <span class="badge {{ str_contains($tx->bank->name, 'Islami') ? 'bg-primary' : 'bg-success' }} px-2 py-1">
                                    {{ $tx->bank->name }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $tx->person_name }}</div>
                                @if($tx->description)
                                    <div class="small text-muted">{{ $tx->description }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="font-monospace small text-muted">{{ $tx->reference_no ?: '—' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border px-2 py-1">
                                    {{ $tx->category ?: 'General' }}
                                </span>
                            </td>
                            <!-- Credit (In) -->
                            <td class="text-end fw-semibold text-success">
                                @if($tx->type === 'credit')
                                    Rs. {{ number_format($tx->amount) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <!-- Debit (Out) -->
                            <td class="text-end fw-semibold text-danger">
                                @if($tx->type === 'debit')
                                    Rs. {{ number_format($tx->amount) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <!-- Running Balance -->
                            <td class="text-end pe-3 fw-bold text-dark">
                                Rs. {{ number_format($tx->running_balance) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No bank transactions recorded in this selected period.
                            </td>
                        </tr>
                    @endforelse

                    <!-- Closing Balance Row -->
                    <tr class="table-light fw-bold border-top border-dark">
                        <td class="ps-3 text-muted">{{ $end->format('d M, Y') }}</td>
                        <td colspan="4" class="text-uppercase text-primary">
                            <i class="bi bi-flag me-1"></i> Closing Balance Carried Forward
                        </td>
                        <td class="text-end text-success">+ Rs. {{ number_format($totalCredit) }}</td>
                        <td class="text-end text-danger">- Rs. {{ number_format($totalDebit) }}</td>
                        <td class="text-end pe-3 fw-bold text-primary fs-6">
                            Rs. {{ number_format($closingBalance) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleCustomDates() {
        const period = document.getElementById('periodSelect').value;
        const singleDateCol = document.getElementById('singleDateCol');
        const customFields = document.querySelectorAll('.custom-date-field');

        if (period === 'custom') {
            singleDateCol.classList.add('d-none');
            customFields.forEach(f => f.classList.remove('d-none'));
        } else {
            singleDateCol.classList.remove('d-none');
            customFields.forEach(f => f.classList.add('d-none'));
        }
    }

    document.addEventListener('DOMContentLoaded', toggleCustomDates);
</script>
@endpush

