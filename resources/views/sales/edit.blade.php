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
                        <i class="bi bi-person"></i> Customer Information
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Customer Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" name="customer_name" id="custNameInput" list="customersDatalist" class="form-control"
                                       placeholder="Walk-in Customer"
                                       value="{{ old('customer_name', $sale->customer->name ?? 'Walk-in Customer') }}" autocomplete="off">
                                <datalist id="customersDatalist">
                                    @foreach($customers as $c)
                                        <option value="{{ $c->name }}" data-phone="{{ $c->phone }}">
                                    @endforeach
                                </datalist>
                            </div>
                        </div>

                        <div class="col-md-6">
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
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Discount (Rs.)</label>
                            <input type="number" step="0.01" min="0" name="discount" id="discountInput" class="form-control form-control-sm text-end fw-semibold text-danger"
                                   value="{{ old('discount', $sale->discount) }}" oninput="calcTotals()">
                        </div>
                        <hr class="my-2 text-muted opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold text-dark fs-6">Grand Total:</span>
                            <span class="fw-bold text-success fs-5">Rs. <span id="grandTotal">0.00</span></span>
                        </div>

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
            'product_id' => $it->product_id,
            'quantity'   => (int) $it->quantity,
            'price'      => (float) $it->price,
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

    function addRow(productId = '', quantity = 1, price = null) {
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
                <input type="number" name="items[${i}][quantity]" class="form-control form-control-sm qty-input" min="1" max="99999" value="${quantity}" oninput="calcRow(${i})" required>
            </td>
            <td>
                <input type="number" step="0.01" name="items[${i}][price]" class="form-control form-control-sm price-input" min="0" max="99999" value="${parseFloat(defaultPrice).toFixed(2)}" oninput="calcRow(${i})" required id="price-${i}">
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

        const qty = parseFloat(qtyInput?.value) || 0;
        const price = parseFloat(priceInput?.value) || 0;
        const total = (qty * price).toFixed(2);

        const totalEl = document.getElementById(`total-${i}`);
        if (totalEl) totalEl.innerText = parseFloat(total).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        calcTotals();
    }

    function calcTotals() {
        let subtotal = 0;
        let totalUnits = 0;
        let itemCount = 0;

        document.querySelectorAll('#itemsBody tr').forEach(row => {
            itemCount++;
            const qtyInput = row.querySelector('.qty-input');
            const priceInput = row.querySelector('.price-input');
            const qty = parseFloat(qtyInput?.value) || 0;
            const price = parseFloat(priceInput?.value) || 0;
            subtotal += (qty * price);
            totalUnits += qty;
        });

        const discount = parseFloat(document.getElementById('discountInput').value) || 0;
        const grandTotal = Math.max(0, subtotal - discount);
        const paidAmount = parseFloat(document.getElementById('paidAmountInput').value) || 0;
        const dueAmount = Math.max(0, grandTotal - paidAmount);

        document.getElementById('summarySubTotal').innerText = subtotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
            addRow(item.product_id, item.quantity, item.price);
        });
    } else {
        addRow();
    }

    // Auto-fill phone when selecting customer from datalist
    document.getElementById('custNameInput').addEventListener('input', function() {
        const val = this.value;
        const opt = document.querySelector(`#customersDatalist option[value="${val}"]`);
        if (opt && opt.dataset.phone) {
            document.getElementById('custPhoneInput').value = opt.dataset.phone;
        }
    });

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

