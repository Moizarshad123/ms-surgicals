@extends('layouts.app')
@section('title', 'New Purchase')
@section('page-title', 'New Purchase')

@push('styles')
<style>
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
                                <input type="text" name="supplier_name" class="form-control"
                                       placeholder="e.g. Sialkot Surgical Supplies Co."
                                       value="{{ old('supplier_name') }}" autofocus>
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
                    <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" onclick="addRow()">
                        <i class="bi bi-plus-lg"></i> Add Item
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="min-width: 220px;">Product Name</th>
                                    <th style="width: 120px;">Quantity</th>
                                    <th style="width: 150px;">Unit Price (Rs.)</th>
                                    <th style="width: 130px;" class="text-end">Subtotal</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <!-- Dynamic rows inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Another Row
                    </button>
                    <span class="text-muted small">Total line items: <strong id="itemCountLabel">0</strong></span>
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
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Total Products:</span>
                            <span class="fw-semibold text-dark" id="summaryItemsCount">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Total Units:</span>
                            <span class="fw-semibold text-dark" id="summaryUnitsCount">0</span>
                        </div>
                        <hr class="my-2 text-muted opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark">Grand Total:</span>
                            <span class="fw-bold text-success fs-5">Rs. <span id="grandTotal">0.00</span></span>
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
                        <select name="payment_method" class="form-select form-select-sm">
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

                    <div class="alert alert-info border-0 small mb-3 py-2 px-3" style="background: rgba(37,194,227,0.1); color: #088198;">
                        <i class="bi bi-info-circle me-1"></i> Saving this purchase will immediately increase inventory stock counts.
                    </div>

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

    let rowIndex = 0;

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
                <input type="number" name="items[${i}][quantity]" class="form-control form-control-sm qty-input no-negative" min="1" max="99999" value="1" oninput="calcRow(${i})" required>
            </td>
            <td>
                <input type="number" step="0.01" name="items[${i}][price]" class="form-control form-control-sm price-input no-negative" min="0" max="99999" value="0.00" oninput="calcRow(${i})" required id="price-${i}">
            </td>
            <td class="text-end fw-bold text-dark align-middle">
                Rs. <span id="total-${i}">0.00</span>
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeRow(${i})" title="Remove item">
                    <i class="bi bi-x fs-5"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        calcGrandTotal();
    }

    function removeRow(i) {
        const row = document.getElementById(`row-${i}`);
        if (row) {
            row.remove();
            calcGrandTotal();
        }
    }

    function updatePrice(select, i) {
        const selectedOption = select.options[select.selectedIndex];
        const price = selectedOption?.dataset.price || 0;
        document.getElementById(`price-${i}`).value = parseFloat(price).toFixed(2);
        calcRow(i);
    }

    function calcRow(i) {
        const row = document.getElementById(`row-${i}`);
        if (!row) return;

        const qtyInput = row.querySelector(`[name="items[${i}][quantity]"]`);
        const priceInput = document.getElementById(`price-${i}`);

        const qty = parseFloat(qtyInput?.value) || 0;
        const price = parseFloat(priceInput?.value) || 0;
        const total = (qty * price).toFixed(2);

        const totalEl = document.getElementById(`total-${i}`);
        if (totalEl) totalEl.innerText = parseFloat(total).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        calcGrandTotal();
    }

    let currentGrandTotal = 0;

    function calcGrandTotal() {
        let total = 0;
        let totalUnits = 0;
        let itemCount = 0;

        document.querySelectorAll('#itemsBody tr').forEach(row => {
            itemCount++;
            const qtyInput = row.querySelector('.qty-input');
            const priceInput = row.querySelector('.price-input');
            const qty = parseFloat(qtyInput?.value) || 0;
            const price = parseFloat(priceInput?.value) || 0;
            total += (qty * price);
            totalUnits += qty;
        });

        currentGrandTotal = total;
        document.getElementById('grandTotal').innerText = total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summaryItemsCount').innerText = itemCount;
        document.getElementById('summaryUnitsCount').innerText = totalUnits;
        document.getElementById('itemCountLabel').innerText = itemCount;

        syncPaymentWithTotal();
    }

    function syncPaymentWithTotal() {
        const type = document.getElementById('paymentTypeSelect').value;
        const paidInput = document.getElementById('paidAmountInput');
        const dueGroup = document.getElementById('dueDateGroup');
        const dueDisplay = document.getElementById('remainingDueDisplay');
        const methodGroup = document.getElementById('paymentMethodGroup');

        if (type === 'paid') {
            paidInput.value = currentGrandTotal.toFixed(2);
            paidInput.readOnly = true;
            dueGroup.style.display = 'none';
            methodGroup.style.display = 'block';
            dueDisplay.innerText = 'Rs. 0.00';
        } else if (type === 'due') {
            paidInput.value = (0).toFixed(2);
            paidInput.readOnly = true;
            dueGroup.style.display = 'block';
            methodGroup.style.display = 'none';
            dueDisplay.innerText = 'Rs. ' + currentGrandTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            // Partial
            paidInput.readOnly = false;
            let paid = parseFloat(paidInput.value) || 0;
            if (paid > currentGrandTotal) {
                paid = currentGrandTotal;
                paidInput.value = paid.toFixed(2);
            }
            let due = Math.max(0, currentGrandTotal - paid);
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
        if (paid > currentGrandTotal) {
            paid = currentGrandTotal;
            paidInput.value = paid.toFixed(2);
        }
        let due = Math.max(0, currentGrandTotal - paid);
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

    // Start with one row
    addRow();
</script>
@endpush
