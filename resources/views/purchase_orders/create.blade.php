@extends('layouts.app')
@section('title', 'New Purchase Order')
@section('page-title', 'New Purchase Order')

@push('styles')
<link href="{{ asset('admin/vendor/tom-select/tom-select.bootstrap5.min.css') }}" rel="stylesheet">
<style>
    /* ── Tom-Select ── */
    .ts-wrapper { display: block; width: 100%; }
    .ts-wrapper.form-select, .ts-wrapper.form-select-sm {
        padding: 0 !important; border: 0 !important; background: none !important;
        box-shadow: none !important; height: auto !important; min-height: 0 !important;
    }
    .ts-wrapper.single .ts-control {
        min-height: 31px; padding: 0.25rem 1.75rem 0.25rem 0.5rem;
        font-size: 0.875rem; line-height: 1.5; border-radius: 0.375rem;
        background: #fff; flex-wrap: nowrap; align-items: center;
    }
    .ts-wrapper.focus .ts-control { border-color: #ff6e40; box-shadow: 0 0 0 3px rgba(255,110,64,0.15); }
    .ts-dropdown {
        z-index: 9999 !important;
        font-size: 0.875rem;
        background: #fff !important;
        min-width: 320px;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    .ts-dropdown .ts-dropdown-content { max-height: 260px; overflow-y: auto; background: #fff; }
    .ts-dropdown .option { padding: 8px 12px !important; white-space: normal; background: #fff; }
    .ts-dropdown .option.active { background: #fff1ec !important; color: #ff6e40; }

    /* ── Layout helpers ── */
    .form-control:focus, .form-select:focus {
        border-color: #ff6e40; box-shadow: 0 0 0 3px rgba(255,110,64,0.15);
    }
    .form-card { border-radius: 12px; border: 1px solid #eef1f6; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
    .section-title {
        font-size: 0.8rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.5px; color: #ff6e40; margin-bottom: 0;
        display: flex; align-items: center; gap: 6px;
    }

    /* ── Products table ── */
    #poItemsTable th {
        font-weight: 600; font-size: 0.82rem; color: #556270;
        background: #fbfbfc; border-bottom: 2px solid #edf1f6;
    }
    #poItemsTable td { vertical-align: middle; font-size: 0.88rem; }

    .amount-cell {
        background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px;
        padding: 0.35rem 0.6rem; font-size: 0.88rem; font-weight: 600;
        color: #333; text-align: right; display: block; min-height: 33px;
        line-height: 1.8;
    }
    .btn-remove-row {
        background: #fff0f0; border: 1px solid #f5c6c6; color: #dc3545;
        border-radius: 6px; width: 30px; height: 30px;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer; padding: 0; transition: all .15s;
    }
    .btn-remove-row:hover { background: #dc3545; color: #fff; border-color: #dc3545; }

    /* ── Summary card ── */
    .summary-card { border-radius: 12px; background: #fafbfc; border: 1px solid #e9edf2; padding: 16px; }
    .grand-total-box {
        background: linear-gradient(135deg, #ff6e40 0%, #f25b2a 100%);
        border-radius: 10px; color: #fff; padding: 14px 18px; text-align: center;
    }
    .grand-total-box .gt-label { font-size: 0.78rem; opacity: .85; text-transform: uppercase; letter-spacing: .5px; }
    .grand-total-box .gt-value { font-size: 1.6rem; font-weight: 800; margin-top: 2px; }

    .btn-theme-primary { background: #ff6e40; border-color: #ff6e40; color: #fff; font-weight: 500; }
    .btn-theme-primary:hover { background: #f25b2a; border-color: #f25b2a; color: #fff; box-shadow: 0 4px 12px rgba(255,110,64,.25); }

    /* spinner hidden */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; appearance: textfield; }
</style>
@endpush

@section('content')

{{-- Page Header --}}
<div class="card form-card mb-4">
    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 p-2 text-white d-flex align-items-center justify-content-center" style="background: #ff6e40; width: 44px; height: 44px;">
                <i class="bi bi-clipboard2-check fs-4"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0">New Purchase Order</h5>
                <div class="text-muted small">Select a supplier, choose products, and create a purchase order.</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-muted border fw-normal" style="font-size:.8rem;">PO # Auto Generated</span>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

@if (isset($errors) && $errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('purchase-orders.store') }}" id="poForm" novalidate>
@csrf

<div class="row g-4">
    {{-- LEFT: Supplier + Items --}}
    <div class="col-lg-8">

        {{-- Supplier & Date --}}
        <div class="card form-card mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <div class="section-title"><i class="bi bi-building"></i> Supplier & Order Information</div>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Supplier Name <span class="text-danger">*</span></label>
                        <select name="supplier_name" id="supplierSelect" class="form-select" required>
                            <option value="">Search or type supplier...</option>
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup }}" {{ old('supplier_name') === $sup ? 'selected' : '' }}>{{ $sup }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">PO Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" name="order_date" class="form-control"
                                   value="{{ old('order_date', date('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Products Table --}}
        <div class="card form-card mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div class="section-title"><i class="bi bi-boxes"></i> Order Items</div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="addRowBtn">
                    <i class="bi bi-plus-circle me-1"></i> Add Product Row
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="poItemsTable">
                        <thead>
                            <tr>
                                <th style="width:36px;" class="text-center ps-3">#</th>
                                <th style="min-width:200px;">Product</th>
                                <th style="width:105px;">Type</th>
                                <th style="width:105px;">Qty</th>
                                <th style="width:130px;">Rate (Rs.)</th>
                                <th style="width:130px;" class="text-end">Amount (Rs.)</th>
                                <th style="width:42px;"></th>
                            </tr>
                        </thead>
                        <tbody id="poItemsBody">
                            {{-- Rows added by JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- RIGHT: Summary --}}
    <div class="col-lg-4">
        <div class="card form-card sticky-top" style="top:20px;">
            <div class="card-header bg-white border-bottom py-3">
                <div class="section-title"><i class="bi bi-receipt"></i> Order Summary</div>
            </div>
            <div class="card-body p-3">
                <div class="summary-card mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Total Products:</span>
                        <span class="fw-semibold text-dark" id="summaryItemCount">0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Total Qty:</span>
                        <span class="fw-semibold text-dark" id="summaryQtyCount">0</span>
                    </div>
                </div>

                <div class="grand-total-box mb-3">
                    <div class="gt-label">Grand Total (PKR)</div>
                    <div class="gt-value" id="grandTotal">0.00</div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-theme-primary">
                        <i class="bi bi-check2-circle me-1"></i> Create Purchase Order
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

</form>
@endsection

@push('scripts')
<script src="{{ asset('admin/vendor/tom-select/tom-select.complete.min.js') }}"></script>
<script>
// Product catalogue
const poProducts = {!! json_encode($products->map(function($p) {
    return ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'price' => (float) $p->purchase_price];
})->values()) !!};

const poProductTypes = ['CTN', 'BOX', 'PC'];
let poRowIndex = 0;

// Supplier Tom Select (allow creating new)
new TomSelect('#supplierSelect', {
    create: true,
    sortField: { field: 'text', direction: 'asc' },
    placeholder: 'Search or type supplier name...',
    createLabel: 'Add supplier: ',
    maxOptions: 300,
    dropdownParent: 'body',
    copyClassesToDropdown: false,
});

document.getElementById('addRowBtn').addEventListener('click', function() { addPoRow(); });

function addPoRow(prefill) {
    poRowIndex++;
    const idx   = poRowIndex;
    const tbody = document.getElementById('poItemsBody');
    const rowNo = tbody.querySelectorAll('tr').length + 1;

    const typeOpts = poProductTypes.map(function(t) {
        const sel = prefill && prefill.type === t ? 'selected' : (!prefill && t === 'PC' ? 'selected' : '');
        return '<option value="' + t + '" ' + sel + '>' + t + '</option>';
    }).join('');

    const prodOpts = poProducts.map(function(p) {
        const sel = prefill && prefill.productId == p.id ? 'selected' : '';
        return '<option value="' + p.id + '" data-price="' + p.price + '" data-code="' + p.code + '" ' + sel + '>' + p.name + '</option>';
    }).join('');

    const tr = document.createElement('tr');
    tr.id = 'po-row-' + idx;
    tr.dataset.idx = idx;
    tr.innerHTML =
        '<td class="text-center text-muted ps-3 row-num" style="font-size:.8rem;font-weight:700;">' + rowNo + '</td>' +
        '<td>' +
            '<select name="items[' + idx + '][product_id]" class="form-select form-select-sm po-product-sel" id="pops-' + idx + '" required>' +
                '<option value="">Search product...</option>' + prodOpts +
            '</select>' +
            '<div class="text-muted mt-1 po-hint" id="po-hint-' + idx + '" style="font-size:.72rem;height:12px;"></div>' +
        '</td>' +
        '<td><select name="items[' + idx + '][product_type]" class="form-select form-select-sm">' + typeOpts + '</select></td>' +
        '<td><input type="number" name="items[' + idx + '][quantity]" class="form-control form-control-sm po-qty" value="' + (prefill ? prefill.qty : 1) + '" min="0.01" step="0.01" required></td>' +
        '<td><input type="number" name="items[' + idx + '][unit_price]" class="form-control form-control-sm po-price" value="' + (prefill ? prefill.price : '') + '" min="0" step="0.01" placeholder="0.00" required></td>' +
        '<td class="text-end"><span class="amount-cell" id="po-amt-' + idx + '">—</span></td>' +
        '<td class="text-center"><button type="button" class="btn-remove-row" onclick="removePoRow(' + idx + ')"><i class="bi bi-trash3-fill" style="font-size:.75rem;"></i></button></td>';

    tbody.appendChild(tr);

    // Tom Select on product
    var ts = new TomSelect('#pops-' + idx, {
        sortField: { field: 'text', direction: 'asc' },
        placeholder: 'Search product...',
        maxOptions: 600,
        dropdownParent: 'body',
        copyClassesToDropdown: false,
        onChange: function(val) {
            var opt = this.options[val];
            if (opt) {
                var hint = document.getElementById('po-hint-' + idx);
                if (hint) hint.textContent = opt.$el ? 'Code: ' + (opt.$el.dataset.code || '') : '';
                var pi = tr.querySelector('.po-price');
                if (pi && (!pi.value || parseFloat(pi.value) === 0)) {
                    pi.value = opt.$el ? (opt.$el.dataset.price || '') : '';
                }
                calcPoRow(idx);
            }
        }
    });
    if (prefill && prefill.productId) ts.setValue(String(prefill.productId), true);

    tr.querySelector('.po-qty').addEventListener('input', function() { calcPoRow(idx); });
    tr.querySelector('.po-price').addEventListener('input', function() { calcPoRow(idx); });

    calcPoRow(idx);
    renumberPoRows();
}

function removePoRow(idx) {
    var rows = document.getElementById('poItemsBody').querySelectorAll('tr');
    if (rows.length <= 1) { alert('At least one product row is required.'); return; }
    var row = document.getElementById('po-row-' + idx);
    if (row) row.remove();
    renumberPoRows();
    calcPoGrandTotal();
}

function renumberPoRows() {
    document.querySelectorAll('#poItemsBody tr').forEach(function(tr, i) {
        var c = tr.querySelector('.row-num'); if (c) c.textContent = i + 1;
    });
}

function calcPoRow(idx) {
    var tr = document.getElementById('po-row-' + idx); if (!tr) return;
    var qty   = parseFloat(tr.querySelector('.po-qty').value)   || 0;
    var price = parseFloat(tr.querySelector('.po-price').value) || 0;
    var amt   = Math.round(qty * price * 100) / 100;
    var el = document.getElementById('po-amt-' + idx);
    if (el) el.textContent = amt > 0 ? formatNum(amt) : '—';
    calcPoGrandTotal();
}

function calcPoGrandTotal() {
    var total = 0, items = 0, totalQty = 0;
    document.querySelectorAll('#poItemsBody tr').forEach(function(tr) {
        var qty   = parseFloat(tr.querySelector('.po-qty')?.value)   || 0;
        var price = parseFloat(tr.querySelector('.po-price')?.value) || 0;
        total += qty * price;
        if (qty > 0 && price >= 0) items++;
        totalQty += qty;
    });
    total = Math.round(total * 100) / 100;
    document.getElementById('grandTotal').textContent = formatNum(total);
    document.getElementById('summaryItemCount').textContent = items;
    document.getElementById('summaryQtyCount').textContent = formatNum(totalQty);
}

function formatNum(n) {
    return parseFloat(n).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function resetForm() {
    if (!confirm('Reset the form? All entered data will be lost.')) return;
    document.getElementById('poItemsBody').innerHTML = '';
    poRowIndex = 0;
    addPoRow();
}

// Restore old input on validation failure or start with one empty row
@if(old('items'))
    @foreach(old('items') as $item)
    addPoRow({ productId: '{{ $item["product_id"] ?? "" }}', type: '{{ $item["product_type"] ?? "PC" }}', qty: '{{ $item["quantity"] ?? 1 }}', price: '{{ $item["unit_price"] ?? "" }}' });
    @endforeach
@else
    addPoRow();
@endif
</script>
@endpush
