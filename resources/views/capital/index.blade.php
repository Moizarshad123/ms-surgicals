@extends('layouts.app')
@section('title', 'Initial Capital & Investment')
@section('page-title', 'Initial Capital & Investment')

@push('styles')
<style>
    .capital-card {
        border-radius: 14px;
        border: 1px solid #e9edf2;
        background: #ffffff;
        padding: 22px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
        position: relative;
    }
    .capital-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    }
    .capital-icon-badge {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .badge-initial {
        background-color: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }
    .badge-additional {
        background-color: #fef3c7;
        color: #b45309;
        border: 1px solid #fde68a;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Capital Introduced -->
        <div class="col-xl-3 col-md-6">
            <div class="capital-card d-flex flex-column justify-content-between border-top border-4 border-primary">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="capital-icon-badge bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Total Capital Injected</h5>
                                <small class="text-muted">Initial Cash &amp; Investments</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted fw-semibold text-uppercase">Total Funds Introduced</small>
                        <h3 class="fw-bold text-primary mb-1">Rs. {{ number_format($totalCapital, 2) }}</h3>
                        <div class="small text-muted">
                            {{ $initialCapitalCount }} {{ Str::plural('entry', $initialCapitalCount) }} recorded
                        </div>
                    </div>
                </div>
                <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                    <span>Source: Business Owners / Partners</span>
                    <span class="text-success fw-semibold"><i class="bi bi-shield-check me-1"></i>Verified Funds</span>
                </div>
            </div>
        </div>

        <!-- Available Cash in Hand -->
        <div class="col-xl-3 col-md-6">
            <div class="capital-card d-flex flex-column justify-content-between border-top border-4 {{ $cashInHand >= 0 ? 'border-success' : 'border-danger' }}">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="capital-icon-badge {{ $cashInHand >= 0 ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                                <i class="bi bi-wallet2"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Available Cash in Hand</h5>
                                <small class="text-muted">Current Liquidity for Purchases</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted fw-semibold text-uppercase">Liquid Physical Cash</small>
                        <h3 class="fw-bold mb-1 {{ $cashInHand >= 0 ? 'text-success' : 'text-danger' }}">
                            Rs. {{ number_format($cashInHand, 2) }}
                        </h3>
                        <div class="small text-muted d-flex align-items-center justify-content-between">
                            <span>Capital + Sales − Outflows</span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#cashBreakdownModal">
                                <i class="bi bi-info-circle"></i> Breakdown
                            </button>
                        </div>
                    </div>
                </div>
                <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                    <a href="{{ route('purchases.create') }}" class="text-primary text-decoration-none fw-semibold">
                        <i class="bi bi-cart-plus me-1"></i> Use for New Purchase &rarr;
                    </a>
                    <span class="badge {{ $cashInHand >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border">
                        {{ $cashInHand >= 0 ? 'Positive Cash' : 'Deficit' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Cash Used for Stock Restocking -->
        <div class="col-xl-3 col-md-6">
            <div class="capital-card d-flex flex-column justify-content-between border-top border-4 border-warning">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="capital-icon-badge bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-truck"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Stock Purchased (Cash)</h5>
                                <small class="text-muted">Capital Deployed into Inventory</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted fw-semibold text-uppercase">Total Cash Purchases</small>
                        <h3 class="fw-bold text-dark mb-1">Rs. {{ number_format($cashPurchases, 2) }}</h3>
                        <div class="small text-muted">
                            Paid directly using available cash
                        </div>
                    </div>
                </div>
                <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                    <a href="{{ route('purchases.index') }}" class="text-warning text-decoration-none fw-semibold">
                        <i class="bi bi-journal-text me-1"></i> View Purchases &rarr;
                    </a>
                    <span class="text-muted">Inventory Asset Created</span>
                </div>
            </div>
        </div>

        <!-- Capital Transferred to Bank -->
        <div class="col-xl-3 col-md-6">
            <div class="capital-card d-flex flex-column justify-content-between border-top border-4 border-info">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="capital-icon-badge bg-info bg-opacity-10 text-info">
                                <i class="bi bi-bank"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Transferred to Bank</h5>
                                <small class="text-muted">Deposited into Bank Accounts</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted fw-semibold text-uppercase">Total Capital Transfers</small>
                        <h3 class="fw-bold text-info mb-1">Rs. {{ number_format($capitalTransferredToBank ?? 0, 2) }}</h3>
                        <div class="small text-muted">
                            Moved to bank, excluded from P&L
                        </div>
                    </div>
                </div>
                <div class="border-top pt-2 mt-3 d-flex justify-content-between small text-muted">
                    <button type="button" class="btn btn-link p-0 text-info text-decoration-none fw-semibold" data-bs-toggle="modal" data-bs-target="#transferToBankModal">
                        <i class="bi bi-arrow-right-circle me-1"></i> Transfer Cash &rarr;
                    </button>
                    <span class="text-muted">Internal Transfer</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Bar & Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 14px;">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="fw-bold text-dark mb-0">Initial Capital &amp; Investment History</h5>
                    <div class="text-muted small">Record starting cash capital so you can purchase stock and track cash flow accurately.</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning btn-sm px-3 d-flex align-items-center gap-2 fw-semibold text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#transferToBankModal">
                        <i class="bi bi-bank"></i> Transfer to Bank
                    </button>
                    <button type="button" class="btn btn-primary btn-sm px-3 d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addCapitalModal">
                        <i class="bi bi-plus-circle"></i> Add Capital / Initial Cash
                    </button>
                    <a href="{{ route('purchases.create') }}" class="btn btn-outline-success btn-sm px-3 d-flex align-items-center gap-2">
                        <i class="bi bi-cart-plus"></i> Make Purchase with Cash
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3 py-3" style="width: 50px;">#</th>
                            <th>Date</th>
                            <th>Capital Type</th>
                            <th>Source / Contributor</th>
                            <th class="text-end">Amount (Rs.)</th>
                            <th>Description / Note</th>
                            <th>Recorded By</th>
                            <th class="text-end pe-3" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($capitals as $index => $item)
                            <tr>
                                <td class="ps-3 text-muted">{{ $capitals->firstItem() + $index }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->capital_date->format('d M, Y') }}</div>
                                    <div class="small text-muted">{{ $item->created_at->format('h:i A') }}</div>
                                </td>
                                <td>
                                    @if($item->type === 'initial_cash')
                                        <span class="badge-initial">
                                            <i class="bi bi-door-open me-1"></i> Initial Starting Cash
                                        </span>
                                    @else
                                        <span class="badge-additional">
                                            <i class="bi bi-plus-slash-minus me-1"></i> Additional Investment
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $item->source }}</div>
                                </td>
                                <td class="text-end fw-bold text-success fs-6">
                                    + Rs. {{ number_format($item->amount, 2) }}
                                </td>
                                <td>
                                    <div class="small text-muted">{{ $item->description ?: '—' }}</div>
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $item->user->name ?? 'Admin' }}</span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" onclick="openEditCapitalModal({{ json_encode($item) }})" title="Edit Capital">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('capital.destroy', $item->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this capital entry of Rs. {{ number_format($item->amount) }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Delete Capital">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <div class="mb-3">
                                        <i class="bi bi-cash-stack fs-1 text-secondary"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark">No Initial Capital Recorded Yet</h6>
                                    <p class="small text-muted mb-3">Add the initial cash you started your business with to make stock purchases using cash.</p>
                                    <button type="button" class="btn btn-primary btn-sm px-4" data-bs-toggle="modal" data-bs-target="#addCapitalModal">
                                        <i class="bi bi-plus-circle me-1"></i> Enter Initial Capital Now
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($capitals->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $capitals->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: ADD CAPITAL / INITIAL CASH                              -->
<!-- ============================================================== -->
<div class="modal fade" id="addCapitalModal" tabindex="-1" aria-labelledby="addCapitalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 14px 14px 0 0;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="addCapitalModalLabel">
                    <i class="bi bi-cash-coin text-primary"></i> Add Initial Capital / Cash Investment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('capital.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 small mb-3 py-2 px-3" style="background: rgba(13,110,253,0.08); color: #084298;">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Entering your initial cash capital will immediately increase your <strong>Cash in Hand</strong> balance, allowing you to record stock purchases paid with cash.
                    </div>

                    <!-- Amount -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Capital Amount (Rs.) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-lg fw-bold text-end" required placeholder="e.g. 500000">
                    </div>

                    <!-- Date & Type -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Date <span class="text-danger">*</span></label>
                            <input type="date" name="capital_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Investment Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="initial_cash" selected>Initial Starting Cash</option>
                                <option value="additional_investment">Additional Investment</option>
                            </select>
                        </div>
                    </div>

                    <!-- Source -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Source / Contributor <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="source" class="form-control" value="Owner / Self" placeholder="e.g. Owner, Partner Name, Personal Savings" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Description / Remarks</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="e.g. Starting business cash for inventory procurement"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top py-3 px-4" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Save Capital
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: EDIT CAPITAL                                            -->
<!-- ============================================================== -->
<div class="modal fade" id="editCapitalModal" tabindex="-1" aria-labelledby="editCapitalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 14px 14px 0 0;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="editCapitalModalLabel">
                    <i class="bi bi-pencil text-primary"></i> Edit Capital Entry
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="editCapitalForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <!-- Amount -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Capital Amount (Rs.) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="editCapitalAmount" class="form-control form-control-lg fw-bold text-end" required>
                    </div>

                    <!-- Date & Type -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Date <span class="text-danger">*</span></label>
                            <input type="date" name="capital_date" id="editCapitalDate" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Investment Type <span class="text-danger">*</span></label>
                            <select name="type" id="editCapitalType" class="form-select" required>
                                <option value="initial_cash">Initial Starting Cash</option>
                                <option value="additional_investment">Additional Investment</option>
                            </select>
                        </div>
                    </div>

                    <!-- Source -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Source / Contributor <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="source" id="editCapitalSource" class="form-control" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Description / Remarks</label>
                        <textarea name="description" id="editCapitalDescription" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top py-3 px-4" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Update Capital
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- ============================================================== -->
<!-- MODAL: TRANSFER CAPITAL TO BANK                                -->
<!-- ============================================================== -->
<div class="modal fade" id="transferToBankModal" tabindex="-1" aria-labelledby="transferToBankModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 14px 14px 0 0;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="transferToBankModalLabel">
                    <i class="bi bi-bank text-warning"></i> Transfer Capital to Bank Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('capital.transfer-to-bank') }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 small mb-3 py-2 px-3" style="background: rgba(255,193,7,0.12); color: #664d03;">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        This action records cash transferred from physical Capital Cash into your Bank account. It will <strong>decrease Cash in Hand</strong> and <strong>increase Bank balance</strong> without generating false income/expense in reports.
                    </div>

                    <!-- Bank Selection -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Select Bank Account <span class="text-danger">*</span>
                        </label>
                        <select name="bank_id" class="form-select" required>
                            <option value="">-- Choose Destination Bank --</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }}
                                    @if($bank->account_title) ({{ $bank->account_title }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Amount -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Transfer Amount (Rs.) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="amount"
                               class="form-control form-control-lg fw-bold text-end"
                               placeholder="0.00" required>
                        <div class="form-text text-muted mt-1">
                            Available Cash in Hand: <strong class="text-success">Rs. {{ number_format($cashInHand, 2) }}</strong>
                        </div>
                    </div>

                    <!-- Transfer Date -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Transfer Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="transfer_date" class="form-control"
                               value="{{ date('Y-m-d') }}" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Description / Remarks</label>
                        <textarea name="description" class="form-control" rows="2"
                                  placeholder="e.g. Cash capital transferred to bank account for vendor cheques"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top py-3 px-4" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 fw-semibold text-dark">
                        <i class="bi bi-bank me-1"></i> Confirm Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: CASH IN HAND BREAKDOWN                                  -->
<!-- ============================================================== -->
<div class="modal fade" id="cashBreakdownModal" tabindex="-1" aria-labelledby="cashBreakdownModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 14px 14px 0 0;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="cashBreakdownModalLabel">
                    <i class="bi bi-calculator text-primary"></i> Available Cash in Hand Breakdown
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-light border small mb-3">
                    <i class="bi bi-info-circle text-primary me-1"></i>
                    <strong>Cash in Hand</strong> represents actual liquid physical cash available right now in your cash counter/safe.
                </div>

                <h6 class="fw-bold text-success mb-2"><i class="bi bi-arrow-down-circle me-1"></i> Cash Inflows (+)</h6>
                <table class="table table-sm table-borderless mb-3 small">
                    <tbody>
                        <tr>
                            <td class="text-muted">Total Capital Injected</td>
                            <td class="text-end fw-semibold text-success">+ Rs. {{ number_format($totalCashCapital ?? $totalCapital, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Cash Sales &amp; Receipts</td>
                            <td class="text-end fw-semibold text-success">+ Rs. {{ number_format($cashSalesInflow ?? 0, 2) }}</td>
                        </tr>
                        <tr class="border-top">
                            <td class="fw-bold text-dark">Total Cash Inflows</td>
                            <td class="text-end fw-bold text-success">+ Rs. {{ number_format(($totalCashCapital ?? $totalCapital) + ($cashSalesInflow ?? 0), 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <h6 class="fw-bold text-danger mb-2"><i class="bi bi-arrow-up-circle me-1"></i> Cash Outflows (-)</h6>
                <table class="table table-sm table-borderless mb-3 small">
                    <tbody>
                        <tr>
                            <td class="text-muted">Stock Purchases (Cash)</td>
                            <td class="text-end fw-semibold text-danger">- Rs. {{ number_format($cashPurchases ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Expenses Paid in Cash</td>
                            <td class="text-end fw-semibold text-danger">- Rs. {{ number_format($cashExpenses ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Cash Deposited / Transferred to Bank</td>
                            <td class="text-end fw-semibold text-danger">- Rs. {{ number_format(($cashDepositsToBank ?? 0) + ($capitalTransferredToBank ?? 0), 2) }}</td>
                        </tr>
                        <tr class="border-top">
                            <td class="fw-bold text-dark">Total Cash Outflows</td>
                            <td class="text-end fw-bold text-danger">- Rs. {{ number_format(($cashPurchases ?? 0) + ($cashExpenses ?? 0) + ($cashDepositsToBank ?? 0) + ($capitalTransferredToBank ?? 0), 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="card bg-light border p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="small text-muted text-uppercase fw-semibold">Net Cash in Hand</span>
                            <div class="fw-bold fs-5 {{ ($cashInHand ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                Rs. {{ number_format($cashInHand ?? 0, 2) }}
                            </div>
                        </div>
                        <span class="badge {{ ($cashInHand ?? 0) >= 0 ? 'bg-success' : 'bg-danger' }}">
                            {{ ($cashInHand ?? 0) >= 0 ? 'Surplus' : 'Deficit' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditCapitalModal(item) {
        document.getElementById('editCapitalForm').action = '/capital/' + item.id;
        document.getElementById('editCapitalAmount').value = item.amount;
        document.getElementById('editCapitalDate').value = item.capital_date ? item.capital_date.split('T')[0] : '';
        document.getElementById('editCapitalType').value = item.type;
        document.getElementById('editCapitalSource').value = item.source;
        document.getElementById('editCapitalDescription').value = item.description || '';

        const modal = new bootstrap.Modal(document.getElementById('editCapitalModal'));
        modal.show();
    }
</script>
@endpush

