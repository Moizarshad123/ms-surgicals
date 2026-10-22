@extends('layouts.app')
@section('title', 'Customer Ledger Report')
@section('page-title', 'Customer Ledger Report')

@push('styles')
<style>
    .ledger-kpi-card {
        border-radius: 12px;
        border: 1px solid #e9edf2;
        background: #ffffff;
        padding: 18px 20px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
    }
    .ledger-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .ledger-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }
    .badge-status-settled {
        background-color: #edfcf5;
        color: #0f8a55;
        border: 1px solid #c7f1dd;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }
    .badge-status-due {
        background-color: #fdf2f2;
        color: #dc3545;
        border: 1px solid #f8d7da;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <!-- KPI Summary Row -->
    @php
        $totalCustomers = $customers->count();
        $totalGrossSales = $customers->sum('total_sales');
        $totalReceived = $customers->sum('total_paid');
        $totalOutstandingAll = $customers->sum('total_outstanding');
        $customersWithDue = $customers->where('total_outstanding', '>', 0)->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="ledger-kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Customers</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalCustomers) }}</div>
                    <div class="small text-muted mt-1">{{ $customersWithDue }} with outstanding balance</div>
                </div>
                <div class="ledger-kpi-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="ledger-kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Sales Billed</div>
                    <div class="fs-4 fw-bold text-dark mt-1">Rs. {{ number_format($totalGrossSales) }}</div>
                    <div class="small text-muted mt-1">All-time invoices</div>
                </div>
                <div class="ledger-kpi-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="ledger-kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Payments Received</div>
                    <div class="fs-4 fw-bold text-success mt-1">Rs. {{ number_format($totalReceived) }}</div>
                    <div class="small text-muted mt-1">Settled & advance</div>
                </div>
                <div class="ledger-kpi-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="ledger-kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Outstanding</div>
                    <div class="fs-4 fw-bold text-danger mt-1">Rs. {{ number_format($totalOutstandingAll) }}</div>
                    <div class="small text-danger mt-1 fw-medium">Receivable amount</div>
                </div>
                <div class="ledger-kpi-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="fw-bold text-dark mb-0">Customer Ledger Directory</h5>
                    <div class="text-muted small">Select a customer below to view their detailed ledger statement</div>
                </div>

                <form action="{{ route('reports.customer-ledger') }}" method="GET" class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="width: 280px;">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ $search }}" class="form-control border-start-0" placeholder="Search customer name or phone...">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary px-3">Search</button>
                    @if($search)
                        <a href="{{ route('reports.customer-ledger') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3 py-3" style="width: 60px;">#</th>
                            <th>Customer Name</th>
                            <th>Contact / Phone</th>
                            <th class="text-center">Total Invoices</th>
                            <th class="text-end">Total Billed</th>
                            <th class="text-end">Total Paid</th>
                            <th class="text-end">Outstanding Balance</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $index => $customer)
                            @php
                                $due = $customer->total_outstanding;
                            @endphp
                            <tr>
                                <td class="ps-3 text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $customer->name }}</div>
                                </td>
                                <td>
                                    <div class="small text-muted">
                                        <i class="bi bi-telephone me-1"></i>{{ $customer->phone ?: '—' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        {{ $customer->sales_count }}
                                    </span>
                                </td>
                                <td class="text-end fw-semibold text-dark">
                                    Rs. {{ number_format($customer->total_sales) }}
                                </td>
                                <td class="text-end text-success fw-semibold">
                                    Rs. {{ number_format($customer->total_paid) }}
                                </td>
                                <td class="text-end fw-bold {{ $due > 0 ? 'text-danger' : 'text-muted' }}">
                                    Rs. {{ number_format($due) }}
                                </td>
                                <td class="text-center">
                                    @if($due > 0)
                                        <span class="badge-status-due">
                                            <i class="bi bi-exclamation-circle me-1"></i> Outstanding
                                        </span>
                                    @else
                                        <span class="badge-status-settled">
                                            <i class="bi bi-check-circle me-1"></i> Settled
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('reports.customer-ledger.show', $customer->id) }}" class="btn btn-sm btn-primary px-3 d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-journal-text"></i> View Report
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-people fs-1 text-secondary d-block mb-2"></i>
                                    No customers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

