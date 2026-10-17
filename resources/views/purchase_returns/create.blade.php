@extends('layouts.app')
@section('title', 'Process Purchase Return')
@section('page-title', 'Process Purchase Return')

@push('styles')
<style>
    .return-card {
        border-radius: 12px;
        border: 1px solid #e9edf2;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .qty-stepper-btn {
        width: 32px;
        height: 32px;
        border: 1px solid #ced4da;
        background: #ffffff;
        border-radius: 6px;
        font-weight: bold;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .qty-stepper-btn:hover {
        background: #f1f4f8;
        border-color: #adb5bd;
    }
    .qty-stepper-input {
        width: 60px;
        height: 32px;
        text-align: center;
        font-weight: bold;
        border: 1px solid #ced4da;
        border-radius: 6px;
    }
    .search-result-item {
        cursor: pointer;
        padding: 10px 14px;
        border-bottom: 1px solid #f1f4f8;
        transition: background 0.15s ease;
    }
    .search-result-item:hover {
        background: #f8fafc;
    }
    .search-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 6px 16px rgba(0,0,0,0.1);
        z-index: 1050;
        max-height: 280px;
        overflow-y: auto;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <!-- Header bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('purchase-returns.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Returns
            </a>
            <div>
                <h4 class="fw-bold text-dark mb-0">
                    <i class="bi bi-arrow-return-left text-warning me-1"></i> Process Purchase Return
                </h4>
                <div class="text-muted small">Return products back to suppliers/vendors and deduct inventory stock.</div>
            </div>
        </div>
    </div>

    <!-- Step 1: Select / Search Purchase -->
    <div class="card return-card mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold text-dark mb-0">
                <span class="badge bg-primary me-2">1</span> Select Original Purchase Order
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-7 position-relative">
                    <label class="form-label small fw-semibold text-dark">
                        Search by PO Reference # or Supplier Name:
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" id="purchaseSearchInput" class="form-control" placeholder="e.g. PUR-20261015-XXXXX or supplier name" autocomplete="off">
                        <button type="button" id="btnSearchPurchase" class="btn btn-primary px-3">
                            <i class="bi bi-arrow-right-circle me-1"></i> Load Order
                        </button>
                    </div>

                    <!-- Live Dropdown Results -->
                    <div id="searchDropdown" class="search-dropdown d-none"></div>
                </div>

                <div class="col-md-5">
                    <div class="p-3 bg-light rounded-3 small text-muted border">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        Selecting a purchase order displays all purchased items, previous returns, and verifies available warehouse stock before deduction.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Purchase & Return Workspace (Hidden until order is loaded) -->
    <div id="returnWorkspace" class="d-none">

        <!-- Purchase Information Banner -->
        <div class="card return-card mb-4 border-primary">
            <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4 col-sm-6">
                        <small class="text-muted d-block">PO Reference Number</small>
                        <span class="fw-bold font-monospace fs-5 text-dark" id="displayRefNo">—</span>
                        <span class="badge bg-light text-secondary border px-2 mt-1 d-block" style="width:fit-content;" id="displayOrderDate">—</span>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <small class="text-muted d-block">Supplier / Vendor</small>
                        <span class="fw-bold fs-6 text-dark" id="displaySupplierName">—</span>
                    </div>
                    <div class="col-md-4 col-sm-12 text-md-end">
                        <small class="text-muted d-block">Original Order Total</small>
                        <span class="fw-bold fs-4 text-dark" id="displayOriginalTotal">Rs. 0</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Panel: Return Items Table -->
            <div class="col-lg-8">
                <div class="card return-card">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0">
                            <span class="badge bg-primary me-2">2</span> Select Items to Return
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="returnAllItems()">
                            <i class="bi bi-check-all me-1"></i> Return All Available
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="ps-3 py-3">Product</th>
                                        <th class="text-end">Cost Price</th>
                                        <th class="text-center">Purchased</th>
                                        <th class="text-center">Already Ret.</th>
                                        <th class="text-center">Returnable</th>
                                        <th class="text-center">In Stock</th>
                                        <th class="text-center" style="width: 170px;">Return Qty</th>
                                        <th class="text-end pe-3">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="returnItemsTableBody">
                                    <!-- Rendered dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Summary & Processing -->
            <div class="col-lg-4">
                <div class="card return-card mb-4" style="border: 2px solid #ffb899; background: #fffaf8;">
                    <div class="card-header bg-transparent py-3 border-bottom">
                        <h6 class="fw-bold text-dark mb-0">
                            <span class="badge bg-warning text-dark me-2">3</span> Return Summary
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-semibold text-secondary">Total Return Value:</span>
                            <span class="fw-bold fs-4 text-danger" id="summaryTotalReturn">Rs. 0.00</span>
                        </div>
                        <hr class="my-2">
                        <div class="p-2 rounded bg-white border small text-muted mb-3" id="stockExplainer">
                            Stock will be automatically deducted from your inventory upon confirmation.
                        </div>

                        <!-- Return Reason & Date -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Return Reason:</label>
                            <select id="returnReasonSelect" class="form-select form-select-sm">
                                <option value="Defective / Damaged Material">Defective / Damaged Material</option>
                                <option value="Wrong Item Delivered">Wrong Item Delivered</option>
                                <option value="Expired / Poor Quality Stock">Expired / Poor Quality Stock</option>
                                <option value="Excess Quantity / Over-delivered">Excess Quantity / Over-delivered</option>
                                <option value="Vendor Exchange / Discontinued">Vendor Exchange / Discontinued</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Return Date:</label>
                            <input type="date" id="returnDateInput" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark">Additional Notes (Optional):</label>
                            <textarea id="returnNotesInput" class="form-control form-control-sm" rows="2" placeholder="e.g. Return authorization reference or vendor remarks"></textarea>
                        </div>

                        <div id="returnErrorBox" class="text-danger small mb-3"></div>

                        <!-- Submit Button -->
                        <button type="button" id="btnSubmitReturn" class="btn btn-warning w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2" onclick="submitPurchaseReturn()">
                            <span id="submitReturnSpinner" class="spinner-border spinner-border-sm d-none"></span>
                            <i class="bi bi-box-arrow-left fs-5" id="submitReturnIcon"></i>
                            <span id="submitReturnText">Confirm &amp; Deduct Stock</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentPurchase = null;
    let returnItems = []; // Array of { purchase_item_id, product_id, product_name, price, available_qty, current_stock, return_qty }
    const csrfToken = '{{ csrf_token() }}';

    // Check if pre-selected purchase was passed
    @if(isset($selectedPurchase) && $selectedPurchase)
        document.addEventListener('DOMContentLoaded', function () {
            loadPurchaseById({{ $selectedPurchase->id }});
        });
    @endif

    // -------------------------------------------------------------
    // Live Purchase Search & Autocomplete
    // -------------------------------------------------------------
    const searchInput = document.getElementById('purchaseSearchInput');
    const searchDropdown = document.getElementById('searchDropdown');
    const btnSearch = document.getElementById('btnSearchPurchase');
    let searchDebounce = null;

    searchInput.addEventListener('input', function () {
        clearTimeout(searchDebounce);
        const term = this.value.trim();
        if (term.length < 2) {
            searchDropdown.classList.add('d-none');
            return;
        }

        searchDebounce = setTimeout(() => {
            fetch(`/purchase-returns/search-purchase?q=${encodeURIComponent(term)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success || !data.purchases || data.purchases.length === 0) {
                        searchDropdown.innerHTML = `<div class="p-3 text-muted small text-center">No orders found matching "${term}"</div>`;
                        searchDropdown.classList.remove('d-none');
                        return;
                    }

                    searchDropdown.innerHTML = '';
                    data.purchases.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'search-result-item d-flex justify-content-between align-items-center';
                        div.innerHTML = `
                            <div>
                                <span class="fw-bold font-monospace text-primary">${p.reference_no}</span>
                                <div class="small text-dark">${p.supplier_name}</div>
                            </div>
                            <div class="text-end">
                                <span class="fw-semibold text-dark">Rs. ${p.total.toLocaleString()}</span>
                                <small class="text-muted d-block">${p.date}</small>
                            </div>
                        `;
                        div.addEventListener('click', () => {
                            searchDropdown.classList.add('d-none');
                            searchInput.value = p.reference_no;
                            loadPurchaseById(p.id);
                        });
                        searchDropdown.appendChild(div);
                    });
                    searchDropdown.classList.remove('d-none');
                });
        }, 300);
    });

    btnSearch.addEventListener('click', function () {
        const term = searchInput.value.trim();
        if (!term) return;
        fetch(`/purchase-returns/search-purchase?q=${encodeURIComponent(term)}`)
            .then(res => res.json())
            .then(data => {
                if (data.purchases && data.purchases.length > 0) {
                    loadPurchaseById(data.purchases[0].id);
                } else {
                    if (typeof toastr !== 'undefined') toastr.error('No purchase order found with this reference.');
                    else alert('No purchase order found with this reference.');
                }
            });
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
            searchDropdown.classList.add('d-none');
        }
    });

    // -------------------------------------------------------------
    // Load Purchase Data
    // -------------------------------------------------------------
    function loadPurchaseById(id) {
        fetch(`/purchase-returns/search-purchase?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.purchase) {
                    alert('Error loading purchase order.');
                    return;
                }
                currentPurchase = data.purchase;
                renderPurchaseWorkspace();
            })
            .catch(err => {
                alert('Failed to fetch purchase details.');
            });
    }

    function renderPurchaseWorkspace() {
        if (!currentPurchase) return;

        // Display Header Banner
        document.getElementById('displayRefNo').innerText = currentPurchase.reference_no;
        document.getElementById('displayOrderDate').innerText = currentPurchase.date;
        document.getElementById('displaySupplierName').innerText = currentPurchase.supplier_name;
        document.getElementById('displayOriginalTotal').innerText = 'Rs. ' + currentPurchase.total.toLocaleString(undefined, {minimumFractionDigits: 2});

        // Initialize Return Items Array
        returnItems = currentPurchase.items.map(item => ({
            purchase_item_id: item.purchase_item_id,
            product_id: item.product_id,
            product_name: item.product_name,
            product_code: item.product_code,
            price: item.price,
            purchased_quantity: item.purchased_quantity,
            returned_quantity: item.returned_quantity,
            available_quantity: item.available_quantity,
            current_stock: item.current_stock,
            return_qty: 0,
        }));

        renderItemsTable();
        calculateTotalReturn();

        document.getElementById('returnWorkspace').classList.remove('d-none');
        document.getElementById('returnWorkspace').scrollIntoView({ behavior: 'smooth' });
    }

    // -------------------------------------------------------------
    // Render Items Table
    // -------------------------------------------------------------
    function renderItemsTable() {
        const tbody = document.getElementById('returnItemsTableBody');
        tbody.innerHTML = '';

        returnItems.forEach((item, index) => {
            const lineTotal = item.return_qty * item.price;
            const maxAllowed = Math.min(item.available_quantity, item.current_stock);
            const isFullyReturned = item.available_quantity === 0;
            const isOutOfStock = item.current_stock <= 0 && !isFullyReturned;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="ps-3">
                    <div class="fw-semibold text-dark">${item.product_name}</div>
                    ${item.product_code ? '<span class="badge bg-light text-dark font-monospace" style="font-size:0.7rem;">#' + item.product_code + '</span>' : ''}
                </td>
                <td class="text-end">Rs. ${item.price.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                <td class="text-center fw-semibold">${item.purchased_quantity}</td>
                <td class="text-center text-muted">${item.returned_quantity}</td>
                <td class="text-center">
                    <span class="badge ${isFullyReturned ? 'bg-secondary' : 'bg-primary'} px-2">
                        ${item.available_quantity} pcs
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge ${item.current_stock > 0 ? 'bg-success' : 'bg-danger'} px-2">
                        ${item.current_stock} pcs
                    </span>
                </td>
                <td class="text-center">
                    ${isFullyReturned ? '<span class="text-muted small">All returned</span>' : isOutOfStock ? '<span class="text-danger small">No stock in hand</span>' : `
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <button type="button" class="qty-stepper-btn" onclick="stepItemQty(${index}, -1)">&minus;</button>
                            <input type="number" min="0" max="${maxAllowed}" class="qty-stepper-input" value="${item.return_qty}" onchange="setLineQty(${index}, this.value)">
                            <button type="button" class="qty-stepper-btn" onclick="stepItemQty(${index}, 1)">&plus;</button>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1 ms-1" style="font-size:0.75rem;" onclick="setLineQty(${index}, ${maxAllowed})">Max</button>
                        </div>
                    `}
                </td>
                <td class="text-end pe-3 fw-bold ${lineTotal > 0 ? 'text-danger' : 'text-muted'}">
                    Rs. ${lineTotal.toLocaleString(undefined, {minimumFractionDigits: 2})}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function stepItemQty(index, delta) {
        const item = returnItems[index];
        const maxAllowed = Math.min(item.available_quantity, item.current_stock);
        const newQty = item.return_qty + delta;
        if (newQty < 0) return;
        if (newQty > maxAllowed) {
            if (typeof toastr !== 'undefined') toastr.warning(`Max allowed to return is ${maxAllowed} (Available: ${item.available_quantity}, In Stock: ${item.current_stock}).`);
            return;
        }
        item.return_qty = newQty;
        renderItemsTable();
        calculateTotalReturn();
    }

    function setLineQty(index, val) {
        const item = returnItems[index];
        const maxAllowed = Math.min(item.available_quantity, item.current_stock);
        let parsed = parseInt(val, 10);
        if (isNaN(parsed) || parsed < 0) parsed = 0;
        if (parsed > maxAllowed) {
            parsed = maxAllowed;
            if (typeof toastr !== 'undefined') toastr.warning(`Max allowed return quantity is ${maxAllowed}.`);
        }
        item.return_qty = parsed;
        renderItemsTable();
        calculateTotalReturn();
    }

    function returnAllItems() {
        returnItems.forEach(item => {
            item.return_qty = Math.min(item.available_quantity, item.current_stock);
        });
        renderItemsTable();
        calculateTotalReturn();
    }

    // -------------------------------------------------------------
    // Live Financial Calculation
    // -------------------------------------------------------------
    function calculateTotalReturn() {
        if (!currentPurchase) return;

        const totalReturn = returnItems.reduce((sum, item) => sum + (item.return_qty * item.price), 0);
        const totalUnits = returnItems.reduce((sum, item) => sum + item.return_qty, 0);

        document.getElementById('summaryTotalReturn').innerText = 'Rs. ' + totalReturn.toLocaleString(undefined, {minimumFractionDigits: 2});

        const explainer = document.getElementById('stockExplainer');
        if (totalUnits === 0) {
            explainer.innerText = 'Select items above to see return amounts.';
        } else {
            explainer.innerHTML = `<strong>Inventory Deduction:</strong> Exactly <strong>${totalUnits} pcs</strong> will be deducted from your warehouse stock upon confirmation.`;
        }
    }

    // -------------------------------------------------------------
    // Submit Return to Backend
    // -------------------------------------------------------------
    function submitPurchaseReturn() {
        const errorBox = document.getElementById('returnErrorBox');
        errorBox.innerText = '';

        if (!currentPurchase) {
            errorBox.innerText = 'Please select a purchase order first.';
            return;
        }

        const validItems = returnItems.filter(i => i.return_qty > 0);
        if (validItems.length === 0) {
            errorBox.innerText = 'Please specify at least 1 item to return.';
            return;
        }

        const totalReturn = returnItems.reduce((sum, item) => sum + (item.return_qty * item.price), 0);
        if (!confirm(`Are you sure you want to process this purchase return for Rs. ${totalReturn.toLocaleString()}? Inventory stock will be deducted immediately.`)) {
            return;
        }

        const payload = {
            purchase_id: currentPurchase.id,
            return_date: document.getElementById('returnDateInput').value,
            reason: document.getElementById('returnReasonSelect').value,
            notes: document.getElementById('returnNotesInput').value.trim() || null,
            items: validItems.map(i => ({
                purchase_item_id: i.purchase_item_id,
                quantity: i.return_qty,
            })),
        };

        const btn = document.getElementById('btnSubmitReturn');
        const spinner = document.getElementById('submitReturnSpinner');
        const icon = document.getElementById('submitReturnIcon');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');

        fetch('{{ route('purchase-returns.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Error occurred while processing return.');

            if (data.receipt_url) {
                window.open(data.receipt_url, '_blank');
            }

            if (typeof toastr !== 'undefined') {
                toastr.success(data.message || 'Purchase return recorded successfully!');
            }

            setTimeout(() => {
                window.location.href = '{{ route('purchase-returns.index') }}';
            }, 1000);
        })
        .catch(err => {
            errorBox.innerText = err.message;
            if (typeof toastr !== 'undefined') toastr.error(err.message);
        })
        .finally(() => {
            btn.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');
        });
    }
</script>
@endpush

