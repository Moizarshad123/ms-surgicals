@extends('layouts.app')
@section('title', 'Process Sales Return')
@section('page-title', 'Process Sales Return')

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
            <a href="{{ route('sales-returns.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Returns
            </a>
            <div>
                <h4 class="fw-bold text-dark mb-0">
                    <i class="bi bi-arrow-counterclockwise text-warning me-1"></i> Process Sales Return
                </h4>
                <div class="text-muted small">Return products, restock inventory, and refund cash or adjust customer credit.</div>
            </div>
        </div>
    </div>

    <!-- Step 1: Select / Search Invoice -->
    <div class="card return-card mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold text-dark mb-0">
                <span class="badge bg-primary me-2">1</span> Select Original Sale / Invoice
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-7 position-relative">
                    <label class="form-label small fw-semibold text-dark">
                        Search by Invoice Number or Customer Phone/Name:
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" id="invoiceSearchInput" class="form-control" placeholder="e.g. INV-20261015-XXXXX or 03xx-xxxxxxx" autocomplete="off">
                        <button type="button" id="btnSearchInvoice" class="btn btn-primary px-3">
                            <i class="bi bi-arrow-right-circle me-1"></i> Load Invoice
                        </button>
                    </div>

                    <!-- Live Dropdown Results -->
                    <div id="searchDropdown" class="search-dropdown d-none"></div>
                </div>

                <div class="col-md-5">
                    <div class="p-3 bg-light rounded-3 small text-muted border">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        Entering an invoice loads all purchased items, previous returns, and customer balance for automatic settlement.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Sale & Return Workspace (Hidden until sale is loaded) -->
    <div id="returnWorkspace" class="d-none">

        <!-- Sale Information Banner -->
        <div class="card return-card mb-4 border-primary">
            <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3 col-sm-6">
                        <small class="text-muted d-block">Invoice Number</small>
                        <span class="fw-bold font-monospace fs-5 text-dark" id="displayInvoiceNo">—</span>
                        <span class="badge bg-light text-secondary border px-2 mt-1 d-block" style="width:fit-content;" id="displaySaleDate">—</span>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <small class="text-muted d-block">Customer Details</small>
                        <span class="fw-bold fs-6 text-dark" id="displayCustomerName">—</span>
                        <div class="small text-muted"><i class="bi bi-telephone me-1"></i><span id="displayCustomerPhone">—</span></div>
                    </div>
                    <div class="col-md-2 col-sm-4 text-center">
                        <small class="text-muted d-block">Original Total</small>
                        <span class="fw-bold fs-5 text-dark" id="displayOriginalTotal">Rs. 0</span>
                    </div>
                    <div class="col-md-2 col-sm-4 text-center">
                        <small class="text-muted d-block">Paid Amount</small>
                        <span class="fw-bold fs-5 text-success" id="displayPaidAmount">Rs. 0</span>
                    </div>
                    <div class="col-md-2 col-sm-4 text-center">
                        <small class="text-muted d-block">Remaining Due (Udhaar)</small>
                        <span class="fw-bold fs-5 text-danger" id="displayDueAmount">Rs. 0</span>
                        <span class="badge mt-1" id="displayPaymentStatusBadge"></span>
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
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-center">Sold Qty</th>
                                        <th class="text-center">Already Ret.</th>
                                        <th class="text-center">Available</th>
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

            <!-- Right Panel: Settlement Breakdown & Submit -->
            <div class="col-lg-4">
                <div class="card return-card mb-4" style="border: 2px solid #ffb899; background: #fffaf8;">
                    <div class="card-header bg-transparent py-3 border-bottom">
                        <h6 class="fw-bold text-dark mb-0">
                            <span class="badge bg-warning text-dark me-2">3</span> Settlement Breakdown
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-semibold text-secondary">Total Return Value:</span>
                            <span class="fw-bold fs-5 text-danger" id="summaryTotalReturn">Rs. 0.00</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted">Deducted from Udhaar / Due:</span>
                            <span class="fw-bold text-success fs-6" id="summaryDeductedFromDue">- Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small text-muted">Cash Refund to Customer:</span>
                            <span class="fw-bold text-primary fs-6" id="summaryCashRefund">Rs. 0.00</span>
                        </div>
                        <div class="p-2 rounded bg-white border small text-muted mb-3" id="settlementExplainer">
                            Select items to view financial adjustments.
                        </div>

                        <!-- Return Reason & Date -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Return Reason:</label>
                            <select id="returnReasonSelect" class="form-select form-select-sm">
                                <option value="Defective / Damaged Item">Defective / Damaged Item</option>
                                <option value="Wrong Item / Size Delivered">Wrong Item / Size Delivered</option>
                                <option value="Customer Change of Mind / Exchange">Customer Change of Mind / Exchange</option>
                                <option value="Expired / Faulty Material">Expired / Faulty Material</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Return Date:</label>
                            <input type="date" id="returnDateInput" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark">Additional Notes (Optional):</label>
                            <textarea id="returnNotesInput" class="form-control form-control-sm" rows="2" placeholder="e.g. Returned with original packaging"></textarea>
                        </div>

                        <div id="returnErrorBox" class="text-danger small mb-3"></div>

                        <!-- Submit Button -->
                        <button type="button" id="btnSubmitReturn" class="btn btn-warning w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2" onclick="submitSalesReturn()">
                            <span id="submitReturnSpinner" class="spinner-border spinner-border-sm d-none"></span>
                            <i class="bi bi-check2-circle fs-5" id="submitReturnIcon"></i>
                            <span id="submitReturnText">Confirm &amp; Process Return</span>
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
    let currentSale = null;
    let returnItems = []; // Array of { sale_item_id, product_id, product_name, price, available_qty, return_qty }
    const csrfToken = '{{ csrf_token() }}';

    // Check if pre-selected sale was passed
    @if(isset($selectedSale) && $selectedSale)
        document.addEventListener('DOMContentLoaded', function () {
            loadSaleById({{ $selectedSale->id }});
        });
    @endif

    // -------------------------------------------------------------
    // Live Invoice Search & Autocomplete
    // -------------------------------------------------------------
    const searchInput = document.getElementById('invoiceSearchInput');
    const searchDropdown = document.getElementById('searchDropdown');
    const btnSearch = document.getElementById('btnSearchInvoice');
    let searchDebounce = null;

    searchInput.addEventListener('input', function () {
        clearTimeout(searchDebounce);
        const term = this.value.trim();
        if (term.length < 2) {
            searchDropdown.classList.add('d-none');
            return;
        }

        searchDebounce = setTimeout(() => {
            fetch(`/sales-returns/search-sale?q=${encodeURIComponent(term)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success || !data.sales || data.sales.length === 0) {
                        searchDropdown.innerHTML = `<div class="p-3 text-muted small text-center">No sales found matching "${term}"</div>`;
                        searchDropdown.classList.remove('d-none');
                        return;
                    }

                    searchDropdown.innerHTML = '';
                    data.sales.forEach(s => {
                        const div = document.createElement('div');
                        div.className = 'search-result-item d-flex justify-content-between align-items-center';
                        div.innerHTML = `
                            <div>
                                <span class="fw-bold font-monospace text-primary">${s.invoice_no}</span>
                                <div class="small text-dark">${s.customer_name} (${s.customer_phone})</div>
                            </div>
                            <div class="text-end">
                                <span class="fw-semibold text-dark">Rs. ${s.total.toLocaleString()}</span>
                                <small class="text-muted d-block">${s.date}</small>
                            </div>
                        `;
                        div.addEventListener('click', () => {
                            searchDropdown.classList.add('d-none');
                            searchInput.value = s.invoice_no;
                            loadSaleById(s.id);
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
        fetch(`/sales-returns/search-sale?q=${encodeURIComponent(term)}`)
            .then(res => res.json())
            .then(data => {
                if (data.sales && data.sales.length > 0) {
                    loadSaleById(data.sales[0].id);
                } else {
                    if (typeof toastr !== 'undefined') toastr.error('No sale found with this invoice number.');
                    else alert('No sale found with this invoice number.');
                }
            });
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
            searchDropdown.classList.add('d-none');
        }
    });

    // -------------------------------------------------------------
    // Load Sale Data
    // -------------------------------------------------------------
    function loadSaleById(id) {
        fetch(`/sales-returns/search-sale?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.sale) {
                    alert('Error loading sale data.');
                    return;
                }
                currentSale = data.sale;
                renderSaleWorkspace();
            })
            .catch(err => {
                alert('Failed to fetch sale details.');
            });
    }

    function renderSaleWorkspace() {
        if (!currentSale) return;

        // Display Header Banner
        document.getElementById('displayInvoiceNo').innerText = currentSale.invoice_no;
        document.getElementById('displaySaleDate').innerText = currentSale.date;
        document.getElementById('displayCustomerName').innerText = currentSale.customer_name;
        document.getElementById('displayCustomerPhone').innerText = currentSale.customer_phone;
        document.getElementById('displayOriginalTotal').innerText = 'Rs. ' + currentSale.total.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('displayPaidAmount').innerText = 'Rs. ' + currentSale.paid_amount.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('displayDueAmount').innerText = 'Rs. ' + currentSale.due_amount.toLocaleString(undefined, {minimumFractionDigits: 2});

        const statusBadge = document.getElementById('displayPaymentStatusBadge');
        if (currentSale.due_amount <= 0 || currentSale.payment_status === 'paid') {
            statusBadge.className = 'badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2';
            statusBadge.innerText = 'Fully Paid';
        } else if (currentSale.payment_status === 'partial') {
            statusBadge.className = 'badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 text-dark';
            statusBadge.innerText = 'Partial Credit';
        } else {
            statusBadge.className = 'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2';
            statusBadge.innerText = 'Unpaid Credit';
        }

        // Initialize Return Items Array
        returnItems = currentSale.items.map(item => ({
            sale_item_id: item.sale_item_id,
            product_id: item.product_id,
            product_name: item.product_name,
            product_code: item.product_code,
            price: item.price,
            sold_quantity: item.sold_quantity,
            returned_quantity: item.returned_quantity,
            available_quantity: item.available_quantity,
            return_qty: 0,
        }));

        renderItemsTable();
        calculateSettlement();

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
            const isFullyReturned = item.available_quantity === 0;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="ps-3">
                    <div class="fw-semibold text-dark">${item.product_name}</div>
                    ${item.product_code ? '<span class="badge bg-light text-dark font-monospace" style="font-size:0.7rem;">#' + item.product_code + '</span>' : ''}
                </td>
                <td class="text-end">Rs. ${item.price.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                <td class="text-center fw-semibold">${item.sold_quantity}</td>
                <td class="text-center text-muted">${item.returned_quantity}</td>
                <td class="text-center">
                    <span class="badge ${isFullyReturned ? 'bg-secondary' : 'bg-primary'} px-2">
                        ${item.available_quantity} pcs
                    </span>
                </td>
                <td class="text-center">
                    ${isFullyReturned ? '<span class="text-muted small">All returned</span>' : `
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <button type="button" class="qty-stepper-btn" onclick="stepItemQty(${index}, -1)">&minus;</button>
                            <input type="number" min="0" max="${item.available_quantity}" class="qty-stepper-input" value="${item.return_qty}" onchange="setLineQty(${index}, this.value)">
                            <button type="button" class="qty-stepper-btn" onclick="stepItemQty(${index}, 1)">&plus;</button>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1 ms-1" style="font-size:0.75rem;" onclick="setLineQty(${index}, ${item.available_quantity})">Max</button>
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
        const newQty = item.return_qty + delta;
        if (newQty < 0) return;
        if (newQty > item.available_quantity) {
            if (typeof toastr !== 'undefined') toastr.warning(`Only ${item.available_quantity} available to return.`);
            return;
        }
        item.return_qty = newQty;
        renderItemsTable();
        calculateSettlement();
    }

    function setLineQty(index, val) {
        const item = returnItems[index];
        let parsed = parseInt(val, 10);
        if (isNaN(parsed) || parsed < 0) parsed = 0;
        if (parsed > item.available_quantity) {
            parsed = item.available_quantity;
            if (typeof toastr !== 'undefined') toastr.warning(`Max available return quantity is ${item.available_quantity}.`);
        }
        item.return_qty = parsed;
        renderItemsTable();
        calculateSettlement();
    }

    function returnAllItems() {
        returnItems.forEach(item => {
            item.return_qty = item.available_quantity;
        });
        renderItemsTable();
        calculateSettlement();
    }

    // -------------------------------------------------------------
    // Live Financial Settlement Calculation
    // -------------------------------------------------------------
    function calculateSettlement() {
        if (!currentSale) return;

        const totalReturn = returnItems.reduce((sum, item) => sum + (item.return_qty * item.price), 0);
        const currentDue = currentSale.due_amount;

        let deductedFromDue = 0;
        let cashRefund = 0;

        if (currentDue > 0) {
            deductedFromDue = Math.min(currentDue, totalReturn);
            cashRefund = Math.max(0, totalReturn - deductedFromDue);
        } else {
            deductedFromDue = 0;
            cashRefund = totalReturn;
        }

        document.getElementById('summaryTotalReturn').innerText = 'Rs. ' + totalReturn.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('summaryDeductedFromDue').innerText = '- Rs. ' + deductedFromDue.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('summaryCashRefund').innerText = 'Rs. ' + cashRefund.toLocaleString(undefined, {minimumFractionDigits: 2});

        const explainer = document.getElementById('settlementExplainer');
        if (totalReturn === 0) {
            explainer.innerText = 'Select items above to see return amounts.';
        } else if (currentDue > 0 && cashRefund > 0) {
            explainer.innerHTML = `<strong>Hybrid Settlement:</strong> Rs. ${deductedFromDue.toLocaleString()} will be subtracted to clear remaining customer Udhaar/due. The remaining Rs. ${cashRefund.toLocaleString()} will be refunded to the customer in cash.`;
        } else if (currentDue > 0) {
            explainer.innerHTML = `<strong>Udhaar Adjustment:</strong> Rs. ${deductedFromDue.toLocaleString()} will be deducted from the customer's pending due balance. No cash refund required.`;
        } else {
            explainer.innerHTML = `<strong>Cash Refund:</strong> This order was fully paid. Rs. ${cashRefund.toLocaleString()} will be refunded to the customer in cash.`;
        }
    }

    // -------------------------------------------------------------
    // Submit Return to Backend
    // -------------------------------------------------------------
    function submitSalesReturn() {
        const errorBox = document.getElementById('returnErrorBox');
        errorBox.innerText = '';

        if (!currentSale) {
            errorBox.innerText = 'Please select a sale invoice first.';
            return;
        }

        const validItems = returnItems.filter(i => i.return_qty > 0);
        if (validItems.length === 0) {
            errorBox.innerText = 'Please specify at least 1 item to return.';
            return;
        }

        const totalReturn = returnItems.reduce((sum, item) => sum + (item.return_qty * item.price), 0);
        if (!confirm(`Are you sure you want to process this return for Rs. ${totalReturn.toLocaleString()}? Stock will be restocked immediately.`)) {
            return;
        }

        const payload = {
            sale_id: currentSale.id,
            return_date: document.getElementById('returnDateInput').value,
            reason: document.getElementById('returnReasonSelect').value,
            notes: document.getElementById('returnNotesInput').value.trim() || null,
            items: validItems.map(i => ({
                sale_item_id: i.sale_item_id,
                quantity: i.return_qty,
            })),
        };

        const btn = document.getElementById('btnSubmitReturn');
        const spinner = document.getElementById('submitReturnSpinner');
        const icon = document.getElementById('submitReturnIcon');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');

        fetch('{{ route('sales-returns.store') }}', {
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
                toastr.success(data.message || 'Sales return recorded successfully!');
            }

            setTimeout(() => {
                window.location.href = '{{ route('sales-returns.index') }}';
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

