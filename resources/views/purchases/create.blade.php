@extends('layouts.app')
@section('title', 'New Purchase')
@section('page-title', 'New Purchase')

@push('styles')
{{-- Local copy (no internet needed): public/admin/vendor/tom-select/ --}}
<link href="{{ asset('admin/vendor/tom-select/tom-select.bootstrap5.min.css') }}" rel="stylesheet">
<style>
    /* Searchable dropdown theming */
    .ts-wrapper { display: block; width: 100%; }
    .ts-wrapper.form-select, .ts-wrapper.form-select-sm {
        padding: 0 !important; border: 0 !important; background: none !important;
        box-shadow: none !important; height: auto !important; min-height: 0 !important;
    }
    .ts-wrapper.single .ts-control {
        min-height: 31px;
        padding: 0.25rem 1.75rem 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.375rem;
        background: #fff;
        flex-wrap: nowrap;
        align-items: center;
    }
    .ts-wrapper.single .ts-control .item {
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #ff6e40;
        box-shadow: 0 0 0 3px rgba(255, 110, 64, 0.15);
    }
    .ts-dropdown {
        z-index: 2000 !important;
        font-size: 0.875rem;
        background: #fff !important;
        min-width: 380px;
        height: auto !important;
        padding: 0 !important;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
    .ts-dropdown .ts-dropdown-content { max-height: 260px; background: #fff; }
    .ts-dropdown .option {
        padding: 7px 12px !important;
        line-height: 1.4 !important;
        height: auto !important;
        white-space: normal;
        background: #fff;
    }
    .ts-dropdown .option.active { background: #fff1ec !important; color: #ff6e40; }
    .ts-dropdown .no-results { padding: 8px 12px; }

    /* Remove up/down spinner arrows from number inputs */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number] {
        -moz-appearance: textfield;
        appearance: textfield;
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

    .form-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }

    .section-title {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #ff6e40;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-control:focus, .form-select:focus {
        border-color: #ff6e40;
        box-shadow: 0 0 0 3px rgba(255, 110, 64, 0.15);
    }

    #itemsTable th {
        font-weight: 600;
        font-size: 0.82rem;
        color: #556270;
        background: #fbfbfc;
        border-bottom: 2px solid #edf1f6;
    }
    #itemsTable td {
        vertical-align: middle;
        font-size: 0.88rem;
    }

    .summary-card {
        border-radius: 12px;
        background: #fafbfc;
        border: 1px solid #e9edf2;
        padding: 16px;
    }
</style>
@endpush

@section('content')
<!-- Header Card -->
<div class="card form-card mb-4">
    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 p-2 text-white d-flex align-items-center justify-content-center" style="background: #ff6e40; width: 44px; height: 44px;">
                <i class="bi bi-cart-plus fs-4"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0">Record New Purchase</h5>
                <div class="text-muted small">Enter supplier details, add purchased items, and auto-update product inventory.</div>
            </div>
        </div>
        <div>
            <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back to Purchases
            </a>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('purchases.store') }}" id="purchaseForm" novalidate>
    @csrf
    <div class="row g-4">
        {{-- LEFT COLUMN: Supplier Info & Purchase Items Table --}}
        <div class="col-lg-8">
            {{-- Supplier & Date Info Card --}}
            <div class="card form-card mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="section-title mb-0">
                        <i class="bi bi-building"></i> Supplier &amp; Invoice Information
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Supplier Name (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-person-badge"></i></span>
                                <input type="text" name="supplier_name" id="supplierNameInput" list="suppliersDatalist" class="form-control"
                                       placeholder="Type or select supplier name..."
                                       value="{{ old('supplier_name') }}" autocomplete="off" oninput="onSupplierChange()" onchange="onSupplierChange()" autofocus>
                                <datalist id="suppliersDatalist">
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s['name'] }}" data-advance="{{ $s['advance_balance'] }}">
                                            {{ $s['name'] }} @if($s['advance_balance'] > 0) (Advance: Rs. {{ number_format($s['advance_balance'], 2) }}) @endif
                                        </option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div id="supplierAdvanceBadge" class="mt-2 d-none">
                                <div class="alert alert-success py-1 px-3 small mb-0 d-flex justify-content-between align-items-center rounded-2 border border-success-subtle">
                                    <span><i class="bi bi-wallet2 me-1"></i> Available Advance Balance:</span>
                                    <span class="fw-bold fs-6 text-success" id="supplierAdvanceAmountDisplay">Rs. 0.00</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Purchase Date <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" name="purchase_date" class="form-control"
                                       value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items Table Card --}}
            <div class="card form-card mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div class="section-title mb-0">
                        <i class="bi bi-box-seam"></i> Order Items
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Another Row
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="min-width: 200px;">Product Name</th>
                                    <th style="width: 100px;">Quantity</th>
                                    <th style="width: 130px;">Unit Price (Rs.)</th>
                                    <th style="width: 110px;">Discount (%)</th>
                                    <th style="width: 130px;" class="text-end">Subtotal (Rs.)</th>
                                    <th style="width: 45px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <!-- Dynamic rows inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Sticky Summary & Checkout Box --}}
        <div class="col-lg-4">
            <div class="card form-card sticky-top" style="top: 20px;">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="section-title mb-0">
                        <i class="bi bi-receipt"></i> Purchase Summary
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="summary-card mb-3">
                        {{-- @if(isset($cashInHand))
                            <div class="p-2 mb-2 rounded-2 {{ $cashInHand > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }} d-flex justify-content-between align-items-center">
                                <span class="small fw-semibold"><i class="bi bi-wallet2 me-1"></i> Available Cash:</span>
                                <span class="fw-bold">Rs. {{ number_format($cashInHand, 2) }}</span>
                            </div>
                        @endif --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Total Products:</span>
                            <span class="fw-semibold text-dark" id="summaryItemsCount">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Total Units:</span>
                            <span class="fw-semibold text-dark" id="summaryUnitsCount">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2" id="summarySubtotalRow" style="display:none;">
                            <span class="text-muted small">Subtotal:</span>
                            <span class="fw-semibold text-dark">Rs. <span id="summarySubtotal">0.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 text-danger" id="summaryDiscountRow" style="display:none;">
                            <span class="small fw-semibold">Item Discounts:</span>
                            <span class="fw-semibold">- Rs. <span id="summaryDiscount">0.00</span></span>
                        </div>
                        <hr class="my-2 text-muted opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark">Grand Total:</span>
                            <span class="fw-bold text-success fs-5">Rs. <span id="grandTotal">0.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 text-success" id="summaryAdvanceRow" style="display:none;">
                            <span class="small fw-semibold"><i class="bi bi-wallet2 me-1"></i> Advance Payment:</span>
                            <span class="fw-bold">- Rs. <span id="summaryAdvanceAdjusted">0.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2" id="summaryNetPayableRow" style="display:none;">
                            <span class="small fw-semibold text-dark">Remaining Payable:</span>
                            <span class="fw-bold text-dark fs-6">Rs. <span id="summaryNetPayable">0.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="small fw-semibold text-danger">Remaining Due:</span>
                            <span class="fw-bold text-danger" id="remainingDueDisplay">Rs. 0.00</span>
                        </div>
                    </div>

                    <!-- Payment Mode & Installments -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark mb-1">Payment Mode</label>
                        <select id="paymentTypeSelect" class="form-select form-select-sm" onchange="onPaymentTypeChange()">
                            <option value="paid" selected>Full Payment (Paid)</option>
                            <option value="partial">Partial Payment (Advance + Credit)</option>
                            <option value="due">Full Credit / Pay Later (Due)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="paidAmountWrap">
                        <label class="form-label small fw-semibold text-dark mb-1">Amount Paid (Rs.) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="paid_amount" id="paidAmountInput" 
                               class="form-control fw-bold text-end" value="0.00" oninput="onPaidAmountChange()">
                    </div>

                    <div class="mb-3" id="dueDateGroup" style="display:none;">
                        <label class="form-label small fw-semibold text-dark mb-1">Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" id="dueDateInput" class="form-control form-control-sm" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                        <small class="text-muted" style="font-size:0.75rem;">Payment deadline for balance due</small>
                    </div>

                    <div class="mb-3" id="paymentMethodGroup">
                        <label class="form-label small fw-semibold text-dark mb-1">Payment Method</label>
                        <select name="payment_method" id="paymentMethodSelect" class="form-select form-select-sm">
                            <option value="cash" selected>Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="easypaisa">EasyPaisa</option>
                            <option value="jazzcash">JazzCash</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark mb-1">Notes / Remarks</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Optional supplier credit or purchase terms..."></textarea>
                    </div>

                    {{-- <div class="alert alert-info border-0 small mb-3 py-2 px-3" style="background: rgba(37,194,227,0.1); color: #088198;">
                        <i class="bi bi-info-circle me-1"></i> Saving this purchase will immediately increase inventory stock counts.
                    </div> --}}

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-theme-primary py-2 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-check2-circle fs-5"></i> Save Purchase
                        </button>
                        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary py-2">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
{{-- Local copy (no internet needed): public/admin/vendor/tom-select/ --}}
<script src="{{ asset('admin/vendor/tom-select/tom-select.complete.min.js') }}"></script>
<script>
    const products = {!! json_encode($products->map(function($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'price' => (float) $p->purchase_price,
            'stock' => $p->stock,
        ];
    })) !!};

    const existingSuppliers = {!! json_encode($suppliers ?? []) !!};
    let currentSupplierAdvance = 0;
    let currentGrandTotal = 0;
    let currentNetPayable = 0;
    let currentAdvanceApplied = 0;

    let rowIndex = 0;

    function initSearchSelect(el, placeholder = 'Search...') {
        return new TomSelect(el, {
            placeholder: placeholder,
            maxOptions: null,          // show all matches
            copyClassesToDropdown: false, // avoids form-select styles breaking the dropdown
            dropdownParent: 'body',    // prevents clipping inside .table-responsive
        });
    }

    function onSupplierChange() {
        const input = document.getElementById('supplierNameInput');
        const supplierName = (input ? input.value : '').trim();
        const badge = document.getElementById('supplierAdvanceBadge');
        const badgeDisplay = document.getElementById('supplierAdvanceAmountDisplay');

        if (!supplierName) {
            currentSupplierAdvance = 0;
            if (badge) badge.classList.add('d-none');
            calcGrandTotal();
            return;
        }

        const match = existingSuppliers.find(s => s.name.toLowerCase() === supplierName.toLowerCase());
        if (match) {
            const adv = parseFloat(match.advance_balance) || 0;
            currentSupplierAdvance = adv;
            if (adv > 0) {
                if (badge) badge.classList.remove('d-none');
                if (badgeDisplay) badgeDisplay.innerText = 'Rs. ' + adv.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                if (badge) badge.classList.add('d-none');
            }
            calcGrandTotal();
        } else {
            // Optional remote lookup if supplier was typed
            fetch(`/purchases/supplier-advance?name=${encodeURIComponent(supplierName)}`)
                .then(res => res.json())
                .then(data => {
                    const adv = parseFloat(data.advance_balance) || 0;
                    currentSupplierAdvance = adv;
                    if (adv > 0) {
                        if (badge) badge.classList.remove('d-none');
                        if (badgeDisplay) badgeDisplay.innerText = 'Rs. ' + adv.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    } else {
                        if (badge) badge.classList.add('d-none');
                    }
                    calcGrandTotal();
                })
                .catch(() => {
                    currentSupplierAdvance = 0;
                    if (badge) badge.classList.add('d-none');
                    calcGrandTotal();
                });
        }
    }

    function addRow() {
        const tbody = document.getElementById('itemsBody');
        const i = rowIndex++;
        const options = products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name} (${p.code ? '#' + p.code : 'Stock: ' + p.stock})</option>`).join('');

        const tr = document.createElement('tr');
        tr.id = `row-${i}`;
        tr.innerHTML = `
            <td>
                <select name="items[${i}][product_id]" class="form-select form-select-sm product-select" onchange="updatePrice(this, ${i})" required>
                    <option value="">-- Choose Product --</option>
                    ${options}
                </select>
            </td>
            <td>
                <input type="number" name="items[${i}][quantity]" class="form-control form-control-sm qty-input no-negative text-center" min="1" max="99999" value="1" oninput="calcRow(${i})" required>
            </td>
            <td>
                <input type="number" step="0.01" name="items[${i}][price]" class="form-control form-control-sm price-input no-negative text-end" min="0" max="99999" value="0.00" oninput="calcRow(${i})" required id="price-${i}">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" name="items[${i}][discount_percent]" class="form-control form-control-sm discount-input no-negative text-end" min="0" max="100" value="0" placeholder="0" oninput="calcRow(${i})" id="discount-${i}">
                    <span class="input-group-text px-1 text-muted" style="font-size:0.75rem;">%</span>
                </div>
            </td>
            <td class="text-end fw-bold text-dark align-middle">
                Rs. <span id="total-${i}">0.00</span>
                <div class="text-muted small" style="font-size:0.72rem; font-weight:normal;" id="disc-note-${i}"></div>
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeRow(${i})" title="Remove item">
                    <i class="bi bi-x fs-5"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        initSearchSelect(tr.querySelector('.product-select'), 'Search product...');
        calcGrandTotal();
    }

    function removeRow(i) {
        const row = document.getElementById(`row-${i}`);
        if (row) {
            row.querySelector('.product-select')?.tomselect?.destroy();
            row.remove();
            calcGrandTotal();
        }
    }

    function updatePrice(select, i) {
        const product = products.find(p => p.id == select.value);
        document.getElementById(`price-${i}`).value = parseFloat(product ? product.price : 0).toFixed(2);
        calcRow(i);
    }

    function calcRow(i) {
        const row = document.getElementById(`row-${i}`);
        if (!row) return;

        const qtyInput = row.querySelector(`[name="items[${i}][quantity]"]`);
        const priceInput = document.getElementById(`price-${i}`);
        const discountInput = document.getElementById(`discount-${i}`);
        const discNote = document.getElementById(`disc-note-${i}`);

        const qty = parseFloat(qtyInput?.value) || 0;
        const price = parseFloat(priceInput?.value) || 0;
        const discountPercent = parseFloat(discountInput?.value) || 0;

        // Step 1: Calculate discount per unit = price * (discountPercent / 100)
        const discountPerUnit = price * (discountPercent / 100);
        // Step 2: Calculate discounted unit price = price - discountPerUnit
        const discountedPrice = Math.max(0, price - discountPerUnit);
        // Step 3: Calculate final subtotal = discountedPrice * qty
        const lineTotal = discountedPrice * qty;

        const totalEl = document.getElementById(`total-${i}`);
        if (totalEl) {
            totalEl.innerText = lineTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        if (discNote) {
            if (discountPercent > 0) {
                const totalItemDiscount = (price * qty) - lineTotal;
                discNote.innerText = `(-Rs. ${totalItemDiscount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })})`;
            } else {
                discNote.innerText = '';
            }
        }

        calcGrandTotal();
    }

    function calcGrandTotal() {
        let total = 0;
        let rawSubtotal = 0;
        let totalDiscount = 0;
        let totalUnits = 0;
        let itemCount = 0;

        document.querySelectorAll('#itemsBody tr').forEach(row => {
            itemCount++;
            const qtyInput = row.querySelector('.qty-input');
            const priceInput = row.querySelector('.price-input');
            const discountInput = row.querySelector('.discount-input');

            const qty = parseFloat(qtyInput?.value) || 0;
            const price = parseFloat(priceInput?.value) || 0;
            const discountPercent = parseFloat(discountInput?.value) || 0;

            const discountPerUnit = price * (discountPercent / 100);
            const discountedPrice = Math.max(0, price - discountPerUnit);
            const lineTotal = discountedPrice * qty;
            const lineRaw = price * qty;

            rawSubtotal += lineRaw;
            totalDiscount += (lineRaw - lineTotal);
            total += lineTotal;
            totalUnits += qty;
        });

        currentGrandTotal = total;

        // Calculate advance to apply (up to grand total)
        currentAdvanceApplied = Math.min(currentSupplierAdvance, currentGrandTotal);
        currentNetPayable = Math.max(0, currentGrandTotal - currentAdvanceApplied);

        document.getElementById('grandTotal').innerText = total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summaryItemsCount').innerText = itemCount;
        document.getElementById('summaryUnitsCount').innerText = totalUnits;
        const countLabel = document.getElementById('itemCountLabel');
        if (countLabel) countLabel.innerText = itemCount;

        // Subtotal & discount rows in summary
        const subtotalRow = document.getElementById('summarySubtotalRow');
        const discountRow = document.getElementById('summaryDiscountRow');
        if (subtotalRow && discountRow) {
            if (totalDiscount > 0) {
                subtotalRow.style.display = 'flex';
                discountRow.style.display = 'flex';
                document.getElementById('summarySubtotal').innerText = rawSubtotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('summaryDiscount').innerText = totalDiscount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                subtotalRow.style.display = 'none';
                discountRow.style.display = 'none';
            }
        }

        // Advance Adjusted & Net Payable rows in summary
        const advanceRow = document.getElementById('summaryAdvanceRow');
        const netPayableRow = document.getElementById('summaryNetPayableRow');
        if (advanceRow && netPayableRow) {
            if (currentAdvanceApplied > 0) {
                advanceRow.style.display = 'flex';
                netPayableRow.style.display = 'flex';
                document.getElementById('summaryAdvanceAdjusted').innerText = currentAdvanceApplied.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('summaryNetPayable').innerText = currentNetPayable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                advanceRow.style.display = 'none';
                netPayableRow.style.display = 'none';
            }
        }

        syncPaymentWithTotal();
    }

    function syncPaymentWithTotal() {
        const type = document.getElementById('paymentTypeSelect').value;
        const paidInput = document.getElementById('paidAmountInput');
        const dueGroup = document.getElementById('dueDateGroup');
        const dueDisplay = document.getElementById('remainingDueDisplay');
        const methodGroup = document.getElementById('paymentMethodGroup');

        if (type === 'paid') {
            paidInput.value = currentNetPayable.toFixed(2);
            paidInput.readOnly = true;
            dueGroup.style.display = 'none';
            methodGroup.style.display = currentNetPayable > 0 ? 'block' : 'none';
            dueDisplay.innerText = 'Rs. 0.00';
        } else if (type === 'due') {
            paidInput.value = (0).toFixed(2);
            paidInput.readOnly = true;
            dueGroup.style.display = currentNetPayable > 0 ? 'block' : 'none';
            methodGroup.style.display = 'none';
            dueDisplay.innerText = 'Rs. ' + currentNetPayable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            // Partial
            paidInput.readOnly = false;
            let paid = parseFloat(paidInput.value) || 0;
            if (paid > currentNetPayable) {
                paid = currentNetPayable;
                paidInput.value = paid.toFixed(2);
            }
            let due = Math.max(0, currentNetPayable - paid);
            dueGroup.style.display = due > 0 ? 'block' : 'none';
            methodGroup.style.display = 'block';
            dueDisplay.innerText = 'Rs. ' + due.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    function onPaymentTypeChange() {
        syncPaymentWithTotal();
    }

    function onPaidAmountChange() {
        const paidInput = document.getElementById('paidAmountInput');
        let paid = parseFloat(paidInput.value) || 0;
        if (paid > currentNetPayable) {
            paid = currentNetPayable;
            paidInput.value = paid.toFixed(2);
        }
        let due = Math.max(0, currentNetPayable - paid);
        const dueGroup = document.getElementById('dueDateGroup');
        const dueDisplay = document.getElementById('remainingDueDisplay');

        dueGroup.style.display = due > 0 ? 'block' : 'none';
        dueDisplay.innerText = 'Rs. ' + due.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Form submission validation
    document.getElementById('purchaseForm').addEventListener('submit', function (e) {
        const rows = document.querySelectorAll('#itemsBody tr');
        if (rows.length === 0) {
            e.preventDefault();
            Swal.fire({
                title: 'No Items Added',
                text: 'Please add at least one product item to save this purchase.',
                icon: 'warning',
                confirmButtonColor: '#ff6e40'
            });
            return;
        }

        let valid = true;
        rows.forEach(row => {
            const select = row.querySelector('.product-select');
            const qty = row.querySelector('.qty-input');
            const price = row.querySelector('.price-input');

            if (!select.value || !qty.value || parseFloat(qty.value) <= 0 || !price.value || parseFloat(price.value) < 0) {
                valid = false;
                row.classList.add('table-danger');
            } else {
                row.classList.remove('table-danger');
            }
        });

        if (!valid) {
            e.preventDefault();
            Swal.fire({
                title: 'Invalid Items',
                text: 'Please ensure all selected items have a valid product, quantity (> 0), and price.',
                icon: 'error',
                confirmButtonColor: '#ff6e40'
            });
        }
    });

    // Searchable dropdowns for payment fields
    initSearchSelect('#paymentTypeSelect', 'Select payment mode');
    initSearchSelect('#paymentMethodSelect', 'Select payment method');

    // Start with one row
    addRow();

    // Check supplier if previously set
    onSupplierChange();
</script>
@endpush