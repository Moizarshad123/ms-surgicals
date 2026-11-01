@extends('layouts.app')
@section('title', 'Edit Purchase ' . $purchase->reference_no)
@section('page-title', 'Edit Purchase')

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
                <i class="bi bi-pencil-square fs-4"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold text-dark mb-0">Edit Purchase Order</h5>
                    <span class="badge bg-light text-dark font-monospace border px-2 py-1">
                        {{ $purchase->reference_no }}
                    </span>
                </div>
                <div class="text-muted small">Update supplier details, modify purchased items, quantities, or prices. Inventory will automatically re-balance.</div>
            </div>
        </div>
        <div>
            <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back to Purchases
            </a>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('purchases.update', $purchase->id) }}" id="purchaseForm" novalidate>
    @csrf
    @method('PUT')

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
                                       value="{{ old('supplier_name', $purchase->supplier_name) }}" autofocus>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Purchase Date <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" name="purchase_date" class="form-control"
                                       value="{{ old('purchase_date', \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d')) }}" required>
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
                        <div class="d-flex justify-content-between align-items-center mb-2 pt-2 border-top">
                            <span class="text-muted small">Paid Amount:</span>
                            <span class="fw-semibold text-success">Rs. {{ number_format($purchase->paid_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small fw-semibold text-danger">Balance Due:</span>
                            <span class="fw-bold text-danger" id="remainingDueDisplay">Rs. {{ number_format($purchase->due_amount, 2) }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark mb-1">Due Date</label>
                        <input type="date" name="due_date" class="form-control form-control-sm" value="{{ $purchase->due_date ? $purchase->due_date->format('Y-m-d') : '' }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark mb-1">Notes / Terms</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Optional supplier credit notes...">{{ $purchase->notes }}</textarea>
                    </div>

                    <div class="alert alert-info border-0 small mb-3 py-2 px-3" style="background: rgba(37,194,227,0.1); color: #088198;">
                        <i class="bi bi-info-circle me-1"></i> Updating this purchase will adjust inventory stock counts automatically.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-theme-primary py-2 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-check2-circle fs-5"></i> Update Purchase
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
    const existingPurchaseItems = {!! json_encode($purchase->items->map(function ($it) {
        return [
            'product_id'       => $it->product_id,
            'quantity'         => (int) $it->quantity,
            'price'            => (float) $it->price,
            'discount_percent' => (float) $it->discount_percent,
        ];
    })) !!};

    const paidAmount = {{ (float) $purchase->paid_amount }};

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

    function initSearchSelect(el, placeholder = 'Search...') {
        return new TomSelect(el, {
            placeholder: placeholder,
            maxOptions: null,          // show all matches
            copyClassesToDropdown: false, // avoids form-select styles breaking the dropdown
            dropdownParent: 'body',    // prevents clipping inside .table-responsive
        });
    }

    function addRow(productId = '', quantity = 1, price = null, discountPercent = 0) {
        const tbody = document.getElementById('itemsBody');
        const i = rowIndex++;

        const options = products.map(p => {
            const isSelected = p.id == productId ? 'selected' : '';
            return `<option value="${p.id}" data-price="${p.price}" ${isSelected}>${p.name} (${p.code ? '#' + p.code : 'Stock: ' + p.stock})</option>`;
        }).join('');

        const defaultPrice = price !== null ? price : (products.find(p => p.id == productId)?.price || 0);

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
                <input type="number" name="items[${i}][quantity]" class="form-control form-control-sm qty-input text-center" min="1" max="99999" value="${quantity}" oninput="calcRow(${i})" required>
            </td>
            <td>
                <input type="number" step="0.01" name="items[${i}][price]" class="form-control form-control-sm price-input text-end" min="0" max="99999" value="${parseFloat(defaultPrice).toFixed(2)}" oninput="calcRow(${i})" required id="price-${i}">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" name="items[${i}][discount_percent]" class="form-control form-control-sm discount-input text-end" min="0" max="100" value="${parseFloat(discountPercent || 0)}" placeholder="0" oninput="calcRow(${i})" id="discount-${i}">
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
        calcRow(i);
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

        // Step 1: discount per unit = price * (discountPercent / 100)
        const discountPerUnit = price * (discountPercent / 100);
        // Step 2: discounted unit price = price - discountPerUnit
        const discountedPrice = Math.max(0, price - discountPerUnit);
        // Step 3: final subtotal = discountedPrice * qty
        const lineTotal = discountedPrice * qty;

        const totalEl = document.getElementById(`total-${i}`);
        if (totalEl) totalEl.innerText = lineTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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

        document.getElementById('grandTotal').innerText = total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summaryItemsCount').innerText = itemCount;
        document.getElementById('summaryUnitsCount').innerText = totalUnits;
        document.getElementById('itemCountLabel').innerText = itemCount;

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

        const remainingDue = Math.max(0, total - paidAmount);
        const dueEl = document.getElementById('remainingDueDisplay');
        if (dueEl) {
            dueEl.innerText = 'Rs. ' + remainingDue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    // Preload existing items
    if (existingPurchaseItems.length > 0) {
        existingPurchaseItems.forEach(item => {
            addRow(item.product_id, item.quantity, item.price, item.discount_percent);
        });
    } else {
        addRow();
    }

    // Form submission validation
    document.getElementById('purchaseForm').addEventListener('submit', function (e) {
        const rows = document.querySelectorAll('#itemsBody tr');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Please add at least one product item to save this purchase.');
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
            alert('Please ensure all selected items have a product, quantity (> 0), and price.');
        }
    });
</script>
@endpush