@extends('layouts.app')
@section('title', 'Edit Order ' . $sale->invoice_no)
@section('page-title', 'Edit Order')

@push('styles')
<style>
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
                    <h5 class="fw-bold text-dark mb-0">Edit Sale Order</h5>
                    <span class="badge bg-light text-dark font-monospace border px-2 py-1">
                        {{ $sale->invoice_no }}
                    </span>
                </div>
                <div class="text-muted small">Update customer info, adjust items, quantities, or prices. Inventory will automatically re-balance.</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a target="_blank" href="{{ route('pos.invoice', $sale->id) }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-printer"></i> Print Invoice
            </a>
            <a href="{{ route('reports.sales') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back to Sales
            </a>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('sales.update', $sale->id) }}" id="saleEditForm" novalidate>
    @csrf
    @method('PUT')

    <div class="row g-4">
        {{-- LEFT COLUMN: Customer & Order Items --}}
        <div class="col-lg-8">
            {{-- Customer Information Card --}}
            <div class="card form-card mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="section-title mb-0">
                        <i class="bi bi-person"></i> Customer &amp; Order Details
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">
                                <i class="bi bi-calendar-event me-1 text-primary"></i> Order Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="order_date" id="orderDateInput" class="form-control fw-semibold"
                                   value="{{ old('order_date', $sale->created_at ? $sale->created_at->format('Y-m-d') : date('Y-m-d')) }}" required>
                            <div class="text-muted small mt-1" style="font-size: 0.75rem;">Change to previous or future date if needed</div>
                        </div>

                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold text-dark mb-0">Customer Name</label>
                                <span id="customerAdvanceBadge" class="badge bg-success-subtle text-success border border-success-subtle d-none" style="font-size: 0.72rem;">
                                    <i class="bi bi-wallet2 me-1"></i>Advance: Rs. <span id="customerAdvanceAmount">0.00</span>
                                </span>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" name="customer_name" id="custNameInput" list="customersDatalist" class="form-control"
                                       placeholder="Walk-in Customer"
                                       value="{{ old('customer_name', $sale->customer->name ?? 'Walk-in Customer') }}" autocomplete="off">
                                <datalist id="customersDatalist">
                                    @foreach($customers as $c)
                                        <option value="{{ $c->name }}" data-phone="{{ $c->phone }}" data-advance="{{ (float)$c->advance_balance }}">
                                    @endforeach
                                </datalist>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="customer_phone" id="custPhoneInput" class="form-control"
                                       placeholder="03xx-xxxxxxx"
                                       value="{{ old('customer_phone', $sale->customer->phone ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Order Items Table Card --}}
            <div class="card form-card mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div class="section-title mb-0">
                        <i class="bi bi-cart-check"></i> Order Items
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
                                    <th style="width: 100px;">Quantity</th>
                                    <th style="width: 130px;">Unit Price (Rs.)</th>
                                    <th style="width: 110px;">Discount (%)</th>
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

        {{-- RIGHT COLUMN: Summary & Payment Box --}}
        <div class="col-lg-4">
            <div class="card form-card sticky-top" style="top: 20px;">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="section-title mb-0">
                        <i class="bi bi-receipt"></i> Order Financial Summary
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="summary-card mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Total Units:</span>
                            <span class="fw-semibold text-dark" id="summaryUnitsCount">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Subtotal:</span>
                            <span class="fw-bold text-dark">Rs. <span id="summarySubTotal">0.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 text-danger" id="summaryDiscountRow" style="display:none;">
                            <span class="small fw-semibold">Item Discounts:</span>
                            <span class="fw-semibold">- Rs. <span id="summaryDiscount">0.00</span></span>
                        </div>
                        <hr class="my-2 text-muted opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark fs-6">Grand Total:</span>
                            <span class="fw-bold text-success fs-5">Rs. <span id="grandTotal">0.00</span></span>
                        </div>

                        @if($sale->advance_adjusted > 0)
                        <div class="d-flex justify-content-between align-items-center mb-2 text-success py-1 px-2 rounded-2" style="background: rgba(40,167,69,0.08);">
                            <span class="small fw-semibold"><i class="bi bi-wallet2 me-1"></i> Advance Payment Adjusted:</span>
                            <span class="fw-bold">- Rs. {{ number_format($sale->advance_adjusted, 2) }}</span>
                        </div>
                        @endif

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-dark mb-1">Paid Amount (Rs.)</label>
                            <input type="number" step="0.01" min="0" name="paid_amount" id="paidAmountInput" class="form-control form-control-sm text-end fw-bold text-primary"
                                   value="{{ old('paid_amount', $sale->paid_amount) }}" oninput="calcTotals()">
                        </div>

                        <div class="d-flex justify-content-between align-items-center p-2 rounded-2 mt-2" id="dueBox" style="background: #fdf4f4; border: 1px solid #f5c6cb;">
                            <span class="small fw-semibold text-danger">Remaining Balance Due:</span>
                            <span class="fw-bold fs-6 text-danger" id="dueAmountDisplay">Rs. 0.00</span>
                        </div>

                        <div class="mt-3" id="dueDateGroup">
                            <label class="form-label small text-muted mb-1">Payment Due Date (Optional)</label>
                            <input type="date" name="due_date" class="form-control form-control-sm"
                                   value="{{ old('due_date', $sale->due_date ? $sale->due_date->format('Y-m-d') : '') }}">
                        </div>

                        <div class="mt-2">
                            <label class="form-label small text-muted mb-1">Order Notes (Optional)</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Any instructions or installment agreement">{{ old('notes', $sale->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 small mb-3 py-2 px-3" style="background: rgba(37,194,227,0.1); color: #088198;">
                        <i class="bi bi-info-circle me-1"></i> Saving updates will re-adjust product inventory and customer account balances automatically.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-theme-primary py-2 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-check2-circle fs-5"></i> Update Order
                        </button>
                        <a href="{{ route('reports.sales') }}" class="btn btn-outline-secondary py-2">
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
    // Product catalog with current stock adjusted for this sale's current quantities
     const existingSaleItems = {!! json_encode($sale->items->map(function ($it) {
        return [
            'product_id'       => $it->product_id,
            'quantity'         => (int) $it->quantity,
            'price'            => (float) $it->price,
            'discount_percent' => (float) ($it->discount_percent ?? 0),
        ];
    })) !!};

    const products = {!! json_encode($products->map(function($p) use ($sale) {
        $existingQtyInThisSale = (int) $sale->items->where('product_id', $p->id)->sum('quantity');
        return [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'price' => (float) $p->sale_price,
            'available_stock' => (int) $p->stock + $existingQtyInThisSale,
        ];
    })) !!};

    let rowIndex = 0;

    function addRow(productId = '', quantity = 1, price = null, discountPercent = 0) {
        const tbody = document.getElementById('itemsBody');
        const i = rowIndex++;

        const options = products.map(p => {
            const isSelected = p.id == productId ? 'selected' : '';
            return `<option value="${p.id}" data-price="${p.price}" data-stock="${p.available_stock}" ${isSelected}>${p.name} (${p.code ? '#' + p.code : 'Avail: ' + p.available_stock})</option>`;
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
        calcRow(i);
    }

    function removeRow(i) {
        const row = document.getElementById(`row-${i}`);
        if (row) {
            row.remove();
            calcTotals();
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
        if (totalEl) totalEl.innerText = parseFloat(lineTotal).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        if (discNote) {
            if (discountPercent > 0) {
                const totalItemDiscount = (price * qty) - lineTotal;
                discNote.innerText = `(-Rs. ${totalItemDiscount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })})`;
            } else {
                discNote.innerText = '';
            }
        }

        calcTotals();
    }

    function calcTotals() {
        let rawSubtotal = 0;
        let totalDiscount = 0;
        let total = 0;
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

        const grandTotal = total;
        const paidAmount = parseFloat(document.getElementById('paidAmountInput').value) || 0;
        const dueAmount = Math.max(0, grandTotal - paidAmount);

        document.getElementById('summarySubTotal').innerText = rawSubtotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        const discRow = document.getElementById('summaryDiscountRow');
        if (discRow) {
            if (totalDiscount > 0) {
                discRow.style.display = 'flex';
                document.getElementById('summaryDiscount').innerText = totalDiscount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                discRow.style.display = 'none';
            }
        }

        document.getElementById('grandTotal').innerText = grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('dueAmountDisplay').innerText = 'Rs. ' + dueAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summaryUnitsCount').innerText = totalUnits;
        document.getElementById('itemCountLabel').innerText = itemCount;

        const dueBox = document.getElementById('dueBox');
        if (dueAmount > 0) {
            dueBox.className = 'd-flex justify-content-between align-items-center p-2 rounded-2 mt-2';
            dueBox.style.background = '#fdf4f4';
            dueBox.style.borderColor = '#f5c6cb';
        } else {
            dueBox.className = 'd-flex justify-content-between align-items-center p-2 rounded-2 mt-2';
            dueBox.style.background = '#f4fbf7';
            dueBox.style.borderColor = '#c3e6cb';
        }
    }

    // Preload existing items
    if (existingSaleItems.length > 0) {
        existingSaleItems.forEach(item => {
            addRow(item.product_id, item.quantity, item.price, item.discount_percent);
        });
    } else {
        addRow();
    }

    // Auto-fill phone and display advance when selecting customer from datalist
    function updateCustomerAdvanceBadge() {
        const val = document.getElementById('custNameInput').value;
        const opt = document.querySelector(`#customersDatalist option[value="${val}"]`);
        const badge = document.getElementById('customerAdvanceBadge');
        const amountSpan = document.getElementById('customerAdvanceAmount');
        if (opt && opt.dataset.phone) {
            document.getElementById('custPhoneInput').value = opt.dataset.phone;
        }
        if (opt && parseFloat(opt.dataset.advance) > 0) {
            amountSpan.innerText = parseFloat(opt.dataset.advance).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }
    document.getElementById('custNameInput').addEventListener('input', updateCustomerAdvanceBadge);
    updateCustomerAdvanceBadge();

    // Form submit validation
    document.getElementById('saleEditForm').addEventListener('submit', function (e) {
        const rows = document.querySelectorAll('#itemsBody tr');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Please add at least one product item to save this order.');
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

