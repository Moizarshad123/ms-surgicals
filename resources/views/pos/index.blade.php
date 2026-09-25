@extends('layouts.app')
@section('title', 'Point of Sale')
@section('page-title', 'Point of Sale')

@push('styles')
<style>
    /* =========================================================
       POS VETRA THEME CUSTOM STYLES
       Primary: #ff6e40 | Success: #05b171 | Dark: #293134
       ========================================================= */

    :root {
        --pos-primary: #ff6e40;
        --pos-primary-hover: #f25b2a;
        --pos-primary-light: rgba(255, 110, 64, 0.08);
        --pos-success: #05b171;
        --pos-card-border: #e9edf2;
        --pos-bg-subtle: #f8fafc;
    }

    /* POS Header Toolbar */
    .pos-header-bar {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--pos-card-border);
        padding: 12px 18px;
        margin-bottom: 16px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .pos-header-title {
        font-weight: 700;
        color: #293134;
        font-size: 1.15rem;
        margin-bottom: 2px;
    }

    /* Catalog Left Panel */
    .catalog-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--pos-card-border);
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 185px);
        min-height: 540px;
    }
    .catalog-header {
        padding: 14px 16px 8px 16px;
        border-bottom: 1px solid #f1f4f8;
    }
    .search-input-group {
        position: relative;
    }
    .search-input-group .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #8c98a4;
        font-size: 1.05rem;
        pointer-events: none;
    }
    .search-input-group .clear-search-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #adb5bd;
        font-size: 1.1rem;
        cursor: pointer;
        padding: 0;
        display: none;
    }
    .search-input-group .form-control {
        padding-left: 42px;
        padding-right: 36px;
        height: 44px;
        border-radius: 10px;
        border: 1px solid #dfe5ec;
        font-size: 0.92rem;
        transition: all 0.2s ease;
    }
    .search-input-group .form-control:focus {
        border-color: var(--pos-primary);
        box-shadow: 0 0 0 3px rgba(255, 110, 64, 0.15);
    }

    /* Category Filter Pills */
    .category-pills-wrap {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 10px 16px;
        background: #fafbfc;
        border-bottom: 1px solid #edf1f6;
        scrollbar-width: thin;
    }
    .category-pills-wrap::-webkit-scrollbar {
        height: 4px;
    }
    .category-pills-wrap::-webkit-scrollbar-thumb {
        background: #d8e0e9;
        border-radius: 4px;
    }
    .category-pill {
        white-space: nowrap;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.82rem;
        font-weight: 500;
        color: #556270;
        background: #ffffff;
        border: 1px solid #e1e7ee;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
    }
    .category-pill:hover {
        border-color: var(--pos-primary);
        color: var(--pos-primary);
    }
    .category-pill.active {
        background: var(--pos-primary);
        border-color: var(--pos-primary);
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(255, 110, 64, 0.3);
    }

    /* Product Grid */
    .product-grid-container {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        scrollbar-width: thin;
    }
    .product-grid-container::-webkit-scrollbar {
        width: 6px;
    }
    .product-grid-container::-webkit-scrollbar-thumb {
        background: #dfe5ed;
        border-radius: 4px;
    }

    /* Product Card */
    .product-card {
        background: #ffffff;
        border: 1px solid #e8edf3;
        border-radius: 12px;
        padding: 14px;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        user-select: none;
        height: 100%;
    }
    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 18px rgba(0,0,0,0.06);
        border-color: var(--pos-primary);
    }
    .product-card:active {
        transform: scale(0.98);
    }
    .product-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        background: var(--pos-primary-light);
        color: var(--pos-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin: 0 auto 10px;
        transition: transform 0.2s ease;
    }
    .product-card:hover .product-icon-wrap {
        transform: scale(1.08);
    }
    .product-name {
        font-weight: 600;
        font-size: 0.88rem;
        color: #2b343b;
        line-height: 1.3;
        min-height: 2.3em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .product-meta {
        font-size: 0.74rem;
        color: #8c9aa6;
        margin-top: 3px;
    }
    .product-price {
        font-size: 1rem;
        font-weight: 700;
        color: var(--pos-primary);
        margin-top: 8px;
    }
    .stock-badge-tag {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 3px 7px;
        border-radius: 6px;
    }

    /* Cart Right Panel */
    .cart-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--pos-card-border);
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 185px);
        min-height: 540px;
    }
    .cart-header {
        padding: 14px 18px;
        border-bottom: 1px solid #edf1f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .cart-customer-bar {
        background: #fcfdfe;
        border-bottom: 1px solid #edf1f6;
        padding: 10px 18px;
    }
    .cart-items-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 12px 18px;
        scrollbar-width: thin;
    }
    .cart-items-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .cart-items-scroll::-webkit-scrollbar-thumb {
        background: #dfe5ed;
        border-radius: 4px;
    }

    /* Cart Item Row */
    .cart-item-row {
        background: #ffffff;
        border: 1px solid #eaf0f6;
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .cart-item-row:hover {
        background: #fafbfd;
        border-color: #dce5ee;
    }
    .cart-item-details {
        flex: 1;
        min-width: 0;
        padding-right: 10px;
    }
    .cart-item-title {
        font-size: 0.86rem;
        font-weight: 600;
        color: #293134;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cart-item-sub {
        font-size: 0.75rem;
        color: #8c9aa6;
    }
    .qty-stepper {
        display: flex;
        align-items: center;
        background: #f1f4f8;
        border-radius: 8px;
        padding: 2px;
    }
    .qty-stepper-btn {
        width: 26px;
        height: 26px;
        border: none;
        background: #ffffff;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: bold;
        color: #495057;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        transition: background 0.1s ease;
    }
    .qty-stepper-btn:hover {
        background: var(--pos-primary);
        color: #ffffff;
    }
    .qty-stepper-value {
        width: 34px;
        text-align: center;
        font-size: 0.85rem;
        font-weight: 600;
        color: #293134;
        border: none;
        background: transparent;
    }
    .cart-item-total {
        min-width: 80px;
        text-align: right;
        font-weight: 700;
        font-size: 0.92rem;
        color: #293134;
    }
    .btn-delete-item {
        background: none;
        border: none;
        color: #adb5bd;
        padding: 4px;
        margin-left: 6px;
        cursor: pointer;
        border-radius: 4px;
        transition: color 0.15s ease;
    }
    .btn-delete-item:hover {
        color: #ea4444;
    }

    /* Cart Summary Footer */
    .cart-footer {
        border-top: 1px solid #edf1f6;
        background: #fcfdfe;
        padding: 14px 18px;
        border-radius: 0 0 12px 12px;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.88rem;
        color: #556270;
        margin-bottom: 8px;
    }
    .grand-total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 8px;
        border-top: 1px dashed #dfe5ec;
        margin-top: 8px;
        margin-bottom: 14px;
    }
    .grand-total-label {
        font-weight: 700;
        font-size: 1.05rem;
        color: #293134;
    }
    .grand-total-amount {
        font-weight: 800;
        font-size: 1.35rem;
        color: var(--pos-primary);
    }
    .btn-checkout-pos {
        background: var(--pos-success);
        border: none;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.98rem;
        border-radius: 10px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(5, 177, 113, 0.25);
        transition: all 0.15s ease;
    }
    .btn-checkout-pos:hover:not(:disabled) {
        background: #049a62;
        color: #ffffff;
        box-shadow: 0 6px 16px rgba(5, 177, 113, 0.35);
        transform: translateY(-1px);
    }
    .btn-checkout-pos:disabled {
        background: #b2c0cc;
        box-shadow: none;
        cursor: not-allowed;
    }

    /* Empty Cart State */
    .empty-cart-view {
        text-align: center;
        padding: 45px 15px;
        color: #9aa7b5;
    }
    .empty-cart-icon {
        font-size: 3rem;
        color: #d1d9e2;
        margin-bottom: 12px;
    }

    /* Checkout Modal */
    .modal-payable-box {
        background: var(--pos-primary-light);
        border: 1px solid rgba(255, 110, 64, 0.2);
        border-radius: 10px;
        padding: 14px;
        text-align: center;
        margin-bottom: 18px;
    }
    .modal-payable-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        color: var(--pos-primary);
    }
    .modal-payable-amount {
        font-size: 1.8rem;
        font-weight: 800;
        color: #293134;
        margin-top: 2px;
    }
    .quick-cash-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-top: 8px;
    }
    .btn-quick-cash {
        background: #f1f4f8;
        border: 1px solid #dfe5ed;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.8rem;
        color: #495057;
        padding: 6px 4px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-quick-cash:hover {
        background: var(--pos-primary);
        border-color: var(--pos-primary);
        color: #ffffff;
    }
    .change-due-box {
        background: #f4fbf7;
        border: 1px solid #c7ecd9;
        border-radius: 8px;
        padding: 10px 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 12px;
    }
</style>
@endpush

@section('content')
<!-- POS Header Toolbar -->
<div class="pos-header-bar d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="d-flex align-items-center gap-3">
        <div>
            <div class="pos-header-title d-flex align-items-center gap-2">
                <i class="bi bi-cart4 text-primary fs-5"></i>
                Point of Sale (POS)
            </div>
            <div class="text-muted small">
                Cashier: <span class="fw-semibold text-dark">{{ auth()->user()->name ?? 'Cashier' }}</span> 
                &bull; <span id="posLiveClock" class="fw-semibold"></span>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <!-- Shortcut Help Tag -->
        <span class="badge bg-light text-secondary border px-3 py-2 d-none d-md-inline-block">
            <i class="bi bi-keyboard me-1"></i> <kbd class="bg-white text-dark border px-1">F2</kbd> Search &bull; <kbd class="bg-white text-dark border px-1">F4</kbd> Clear Cart
        </span>

        <!-- Quick Sidebar / Full-Width Toggle Button -->
        <button type="button" id="btnToggleSidebar" class="btn btn-outline-secondary btn-sm px-3 d-flex align-items-center gap-2" title="Toggle Sidebar">
            <i class="bi bi-layout-sidebar-inset"></i>
            <span class="d-none d-sm-inline" id="btnToggleSidebarText">Toggle Sidebar</span>
        </button>
    </div>
</div>

<div class="row g-3">
    <!-- LEFT PANEL: Product Catalog Workstation -->
    <div class="col-xl-8 col-lg-7">
        <div class="catalog-card">
            <!-- Search Header -->
            <div class="catalog-header">
                <div class="search-input-group">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="searchBox" class="form-control" placeholder="Search by product name or code (F2)..." autocomplete="off">
                    <button type="button" id="clearSearchBtn" class="clear-search-btn" title="Clear search">&times;</button>
                </div>
            </div>

            <!-- Category Pills Filter -->
            <div class="category-pills-wrap" id="categoryPillsWrap">
                <div class="category-pill active" data-category="all">
                    <i class="bi bi-grid-fill me-1"></i> All Products ({{ $products->count() }})
                </div>
                @foreach ($categories as $cat)
                    @php
                        $catProductCount = $products->where('category_id', $cat->id)->count();
                    @endphp
                    @if ($catProductCount > 0)
                        <div class="category-pill" data-category="{{ $cat->id }}">
                            {{ $cat->name }} ({{ $catProductCount }})
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Product Cards Grid -->
            <div class="product-grid-container" id="productGridContainer">
                <div class="row g-3" id="productGrid">
                    @forelse ($products as $product)
                        @php
                            $isLowStock = $product->is_low_stock || ($product->alert_quantity && $product->stock <= $product->alert_quantity);
                            $isWarningStock = !$isLowStock && $product->stock <= 5;
                        @endphp
                        <div class="col-xxl-3 col-xl-4 col-md-4 col-sm-6 product-item"
                            data-name="{{ strtolower($product->name) }}"
                            data-code="{{ strtolower($product->code ?? '') }}"
                            data-category="{{ $product->category_id ?? 0 }}">
                            <div class="product-card"
                                data-id="{{ $product->id }}"
                                data-product-name="{{ $product->name }}"
                                data-price="{{ $product->sale_price }}"
                                data-stock="{{ $product->stock }}">
                                
                                <!-- Stock Badge -->
                                @if ($isLowStock)
                                    <span class="stock-badge-tag bg-danger text-white">Low: {{ $product->stock }}</span>
                                @elseif ($isWarningStock)
                                    <span class="stock-badge-tag bg-warning text-dark">{{ $product->stock }} left</span>
                                @else
                                    <span class="stock-badge-tag bg-light text-secondary border">{{ $product->stock }} pcs</span>
                                @endif

                                <!-- Product Icon -->
                                <div class="product-icon-wrap overflow-hidden">
                                    @if ($product->image)
                                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;">
                                    @else
                                        <i class="bi bi-box-seam"></i>
                                    @endif
                                </div>

                                <!-- Details -->
                                <div class="text-center">
                                    <div class="product-name" title="{{ $product->name }}">{{ $product->name }}</div>
                                    <div class="product-meta">
                                        {{-- {{ $product->category->name ?? 'General' }} --}}
                                        @if(!empty($product->code))
                                         <span class="badge bg-light text-dark font-monospace" style="font-size:0.68rem;">#{{ $product->code }}</span>
                                        @endif
                                    </div>
                                    <div class="product-price">Rs. {{ number_format($product->sale_price) }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 py-5 text-center text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            No products available with stock > 0.
                        </div>
                    @endforelse
                </div>

                <!-- Empty Search Filter State -->
                <div id="noSearchResults" class="py-5 text-center text-muted d-none">
                    <i class="bi bi-search fs-1 d-block mb-2 text-secondary"></i>
                    <h6 class="fw-semibold text-dark">No products found</h6>
                    <p class="small">Try searching with a different product name, code, or select "All Products".</p>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="resetSearchAndFilters()">
                        Reset Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL: Cart & Order Workstation -->
    <div class="col-xl-4 col-lg-5">
        <div class="cart-card">
            <!-- Cart Header -->
            <div class="cart-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-bag-check-fill text-success fs-5"></i>
                    <span class="fw-bold text-dark">Order Items</span>
                    <span id="cartCountBadge" class="badge bg-primary rounded-pill">0</span>
                </div>
                <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0" id="btnClearCart" title="Empty Cart">
                    <i class="bi bi-trash3 me-1"></i> Clear
                </button>
            </div>

            <!-- Customer Quick Bar -->
            <div class="cart-customer-bar">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="small fw-semibold text-secondary">
                        <i class="bi bi-person me-1"></i> Customer Info
                    </span>
                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small text-primary" id="btnCustomerToggle">
                        Edit
                    </button>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="small fw-bold text-dark" id="displayCustomerName">Walk-in Customer</span>
                    <span class="small text-muted" id="displayCustomerPhone">—</span>
                </div>

                <!-- Quick Inline Customer Inputs (Hidden by default) -->
                <div id="customerInlineForm" class="mt-2 d-none">
                    <input type="text" id="cartCustomerName" class="form-control form-control-sm mb-1" placeholder="Customer Name (Walk-in)">
                    <input type="text" id="cartCustomerPhone" class="form-control form-control-sm" placeholder="Phone (e.g. 0300-1234567)">
                </div>
            </div>

            <!-- Cart Items Scrollable List -->
            <div class="cart-items-scroll" id="cartItemsList">
                <div id="emptyCartView" class="empty-cart-view">
                    <i class="bi bi-cart-x empty-cart-icon"></i>
                    <h6 class="fw-semibold text-secondary mb-1">Cart is empty</h6>
                    <p class="small text-muted mb-0">Click any product from the catalog on the left to add it to this order.</p>
                </div>
            </div>

            <!-- Cart Summary & Checkout Footer -->
            <div class="cart-footer">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="fw-semibold text-dark" id="subTotalDisplay">Rs. 0.0</span>
                </div>
                <div class="summary-row">
                    <span>Discount (Rs.)</span>
                    <div style="width: 110px;">
                        <input type="number" id="discountInput" class="form-control form-control-sm text-end" value="0" min="0" oninput="renderCart()">
                    </div>
                </div>

                <!-- Grand Total -->
                <div class="grand-total-row">
                    <span class="grand-total-label">Grand Total</span>
                    <span class="grand-total-amount" id="grandTotalDisplay">Rs. 0.0</span>
                </div>

                <!-- Checkout Button -->
                <button type="button" class="btn btn-checkout-pos w-100" id="btnOpenCheckoutModal" data-bs-toggle="modal" data-bs-target="#checkoutModal" disabled>
                    <i class="bi bi-check2-circle fs-5"></i>
                    <span id="checkoutBtnText">Confirm &amp; Generate Bill</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CHECKOUT MODAL -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 14px 14px 0 0;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="checkoutModalLabel">
                    <i class="bi bi-receipt text-primary"></i> Complete Sale &amp; Bill
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Highlight Total Payable -->
                <div class="modal-payable-box">
                    <div class="modal-payable-label">Total Amount Payable</div>
                    <div class="modal-payable-amount" id="modalPayableTotal">Rs. 0.0</div>
                </div>

                <!-- Customer Details -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold text-dark">Customer Name</label>
                        <input type="text" id="modalCustomerName" class="form-control" placeholder="Walk-in Customer">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold text-dark">Phone Number</label>
                        <input type="text" id="modalCustomerPhone" class="form-control" placeholder="03xx-xxxxxxx">
                    </div>
                </div>

                <!-- Amount Received & Quick Cash -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark d-flex justify-content-between">
                        <span>Amount Paid by Customer (Rs.)</span>
                        <span class="text-muted small">Auto-calculated</span>
                    </label>
                    <input type="number" id="paidAmountInput" class="form-control form-control-lg text-end fw-bold" min="0" oninput="calculateChange()">
                    
                    <!-- Quick Cash Buttons -->
                    <div class="quick-cash-grid">
                        <button type="button" class="btn-quick-cash" onclick="setQuickCash('exact')">Exact</button>
                        <button type="button" class="btn-quick-cash" onclick="setQuickCash(500)">Rs. 500</button>
                        <button type="button" class="btn-quick-cash" onclick="setQuickCash(1000)">Rs. 1,000</button>
                        <button type="button" class="btn-quick-cash" onclick="setQuickCash(5000)">Rs. 5,000</button>
                    </div>
                </div>

                <!-- Change Due / Balance -->
                <div class="change-due-box" id="changeDueBox">
                    <span class="small fw-semibold" id="changeDueLabel">Change Return:</span>
                    <span class="fw-bold fs-6 text-success" id="changeDueAmount">Rs. 0.0</span>
                </div>

                <!-- Error Message Box -->
                <div id="checkoutError" class="text-danger small mt-2"></div>
            </div>

            <div class="modal-footer border-top py-3 px-4" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4 fw-semibold" id="btnSubmitSale" onclick="submitSale()">
                    <span id="submitSpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                    <i class="bi bi-printer me-1" id="submitIcon"></i>
                    Generate Bill
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    /* =========================================================
       POS WORKSTATION JAVASCRIPT
       ========================================================= */

    let cart = []; // Array of { id, name, price, stock, quantity }
    let activeCategoryId = 'all';
    const csrfToken = '{{ csrf_token() }}';

    // Live Clock in POS Header
    function updateClock() {
        const now = new Date();
        const options = { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
        const clockEl = document.getElementById('posLiveClock');
        if (clockEl) {
            clockEl.innerText = now.toLocaleDateString('en-PK', options);
        }
    }
    setInterval(updateClock, 1000);
    updateClock();

    // -------------------------------------------------------------
    // Sidebar Toggle Controller
    // -------------------------------------------------------------
    const btnToggleSidebar = document.getElementById('btnToggleSidebar');
    if (btnToggleSidebar) {
        btnToggleSidebar.addEventListener('click', function () {
            const body = document.body;
            const menu = document.querySelector('.menu');

            // Toggle hidden-menu class on body
            body.classList.toggle('hidden-menu');

            // If on mobile/tablet responsive screen, also toggle .open on menu
            if (window.innerWidth <= 1200 && menu) {
                menu.classList.toggle('open');
            }

            const btnText = document.getElementById('btnToggleSidebarText');
            if (body.classList.contains('hidden-menu')) {
                btnText.innerText = 'Show Sidebar';
                btnToggleSidebar.classList.add('btn-primary');
                btnToggleSidebar.classList.remove('btn-outline-secondary');
            } else {
                btnText.innerText = 'Hide Sidebar';
                btnToggleSidebar.classList.remove('btn-primary');
                btnToggleSidebar.classList.add('btn-outline-secondary');
            }
        });
    }

    // -------------------------------------------------------------
    // Category Pill Filter
    // -------------------------------------------------------------
    document.querySelectorAll('.category-pill').forEach(pill => {
        pill.addEventListener('click', function () {
            document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            activeCategoryId = this.dataset.category;
            filterProducts();
        });
    });

    // -------------------------------------------------------------
    // Search & Barcode Scanner
    // -------------------------------------------------------------
    const searchBox = document.getElementById('searchBox');
    const clearSearchBtn = document.getElementById('clearSearchBtn');

    searchBox.addEventListener('input', function () {
        clearSearchBtn.style.display = this.value.length > 0 ? 'block' : 'none';
        filterProducts();
    });

    clearSearchBtn.addEventListener('click', function () {
        searchBox.value = '';
        this.style.display = 'none';
        filterProducts();
        searchBox.focus();
    });

    // Barcode scanner auto-enter support
    searchBox.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const term = this.value.trim().toLowerCase();
            if (!term) return;

            // Find matching product with exact code or single match
            const visibleItems = Array.from(document.querySelectorAll('.product-item')).filter(i => i.style.display !== 'none');
            const exactCodeMatch = visibleItems.find(i => i.dataset.code === term);

            if (exactCodeMatch) {
                const card = exactCodeMatch.querySelector('.product-card');
                addToCartFromCard(card);
                this.value = '';
                clearSearchBtn.style.display = 'none';
                filterProducts();
            } else if (visibleItems.length === 1) {
                const card = visibleItems[0].querySelector('.product-card');
                addToCartFromCard(card);
                this.value = '';
                clearSearchBtn.style.display = 'none';
                filterProducts();
            }
        }
    });

    function filterProducts() {
        const term = searchBox.value.toLowerCase().trim();
        let visibleCount = 0;

        document.querySelectorAll('.product-item').forEach(item => {
            const matchesCategory = (activeCategoryId === 'all') || (item.dataset.category === activeCategoryId);
            const matchesSearch = !term || item.dataset.name.includes(term) || item.dataset.code.includes(term);

            if (matchesCategory && matchesSearch) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        const noResults = document.getElementById('noSearchResults');
        noResults.classList.toggle('d-none', visibleCount > 0);
    }

    function resetSearchAndFilters() {
        searchBox.value = '';
        clearSearchBtn.style.display = 'none';
        activeCategoryId = 'all';
        document.querySelectorAll('.category-pill').forEach(p => {
            p.classList.toggle('active', p.dataset.category === 'all');
        });
        filterProducts();
    }

    // -------------------------------------------------------------
    // Product Selection & Cart Handling
    // -------------------------------------------------------------
    document.getElementById('productGrid').addEventListener('click', function (e) {
        const card = e.target.closest('.product-card');
        if (!card) return;
        addToCartFromCard(card);
    });

    function addToCartFromCard(card) {
        addToCart({
            id: parseInt(card.dataset.id, 10),
            name: card.dataset.productName,
            price: parseFloat(card.dataset.price),
            stock: parseInt(card.dataset.stock, 10),
        });
    }

    function addToCart(product) {
        const existing = cart.find(i => i.id === product.id);
        if (existing) {
            if (existing.quantity < product.stock) {
                existing.quantity++;
                if (typeof toastr !== 'undefined') {
                    toastr.success(`Added another ${product.name} (Qty: ${existing.quantity})`, '', { timeOut: 1200 });
                }
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.warning(`Only ${product.stock} pcs available in stock for ${product.name}.`);
                } else {
                    alert(`Maximum available stock reached for ${product.name}`);
                }
                return;
            }
        } else {
            if (product.stock < 1) {
                if (typeof toastr !== 'undefined') {
                    toastr.error(`${product.name} is currently out of stock.`);
                } else {
                    alert('Out of stock');
                }
                return;
            }
            cart.push({ ...product, quantity: 1 });
            if (typeof toastr !== 'undefined') {
                toastr.success(`${product.name} added to cart`, '', { timeOut: 1200 });
            }
        }
        renderCart();
    }

    function changeQty(id, delta) {
        const item = cart.find(i => i.id === id);
        if (!item) return;

        const newQty = item.quantity + delta;
        if (newQty <= 0) {
            removeItem(id);
            return;
        }

        if (newQty > item.stock) {
            if (typeof toastr !== 'undefined') {
                toastr.warning(`Only ${item.stock} pcs available in stock.`);
            }
            item.quantity = item.stock;
        } else {
            item.quantity = newQty;
        }
        renderCart();
    }

    function setDirectQty(id, val) {
        const item = cart.find(i => i.id === id);
        if (!item) return;

        const parsed = parseInt(val, 10);
        if (isNaN(parsed) || parsed <= 0) {
            removeItem(id);
            return;
        }

        if (parsed > item.stock) {
            if (typeof toastr !== 'undefined') {
                toastr.warning(`Only ${item.stock} pcs available in stock.`);
            }
            item.quantity = item.stock;
        } else {
            item.quantity = parsed;
        }
        renderCart();
    }

    function removeItem(id) {
        cart = cart.filter(i => i.id !== id);
        renderCart();
    }

    document.getElementById('btnClearCart').addEventListener('click', function () {
        if (cart.length === 0) return;
        if (confirm('Are you sure you want to clear all items in the cart?')) {
            cart = [];
            renderCart();
            if (typeof toastr !== 'undefined') {
                toastr.info('Cart cleared');
            }
        }
    });

    // -------------------------------------------------------------
    // Render Cart DOM
    // -------------------------------------------------------------
    function renderCart() {
        const cartList = document.getElementById('cartItemsList');
        const emptyView = document.getElementById('emptyCartView');
        const checkoutBtn = document.getElementById('btnOpenCheckoutModal');
        const countBadge = document.getElementById('cartCountBadge');

        const totalItemsCount = cart.reduce((sum, i) => sum + i.quantity, 0);
        countBadge.innerText = totalItemsCount;

        if (cart.length === 0) {
            cartList.innerHTML = '';
            cartList.appendChild(emptyView);
            checkoutBtn.disabled = true;
            document.getElementById('checkoutBtnText').innerText = 'Confirm & Generate Bill';
        } else {
            checkoutBtn.disabled = false;
            cartList.innerHTML = '';

            cart.forEach(item => {
                const lineTotal = item.price * item.quantity;
                const row = document.createElement('div');
                row.className = 'cart-item-row';
                row.innerHTML = `
                    <div class="cart-item-details">
                        <div class="cart-item-title" title="${item.name}">${item.name}</div>
                        <div class="cart-item-sub">Rs. ${item.price.toLocaleString()} each</div>
                    </div>
                    <div class="qty-stepper">
                        <button type="button" class="qty-stepper-btn" onclick="changeQty(${item.id}, -1)">&minus;</button>
                        <input type="text" class="qty-stepper-value" value="${item.quantity}" onchange="setDirectQty(${item.id}, this.value)">
                        <button type="button" class="qty-stepper-btn" onclick="changeQty(${item.id}, 1)">&plus;</button>
                    </div>
                    <div class="cart-item-total">
                        Rs. ${lineTotal.toLocaleString()}
                    </div>
                    <button type="button" class="btn-delete-item" onclick="removeItem(${item.id})" title="Remove item">
                        <i class="bi bi-x-circle fs-6"></i>
                    </button>
                `;
                cartList.appendChild(row);
            });
        }

        const subTotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
        const discount = parseFloat(document.getElementById('discountInput').value) || 0;
        const total = Math.max(subTotal - discount, 0);

        document.getElementById('subTotalDisplay').innerText = 'Rs. ' + subTotal.toLocaleString();
        document.getElementById('grandTotalDisplay').innerText = 'Rs. ' + total.toLocaleString();

        if (cart.length > 0) {
            document.getElementById('checkoutBtnText').innerText = `Pay & Bill (Rs. ${total.toLocaleString()})`;
        }

        // Keep checkout modal synced
        document.getElementById('modalPayableTotal').innerText = 'Rs. ' + total.toLocaleString();
        calculateChange();
    }

    // -------------------------------------------------------------
    // Customer Inline Toggle
    // -------------------------------------------------------------
    const btnCustomerToggle = document.getElementById('btnCustomerToggle');
    const customerInlineForm = document.getElementById('customerInlineForm');
    const cartCustomerName = document.getElementById('cartCustomerName');
    const cartCustomerPhone = document.getElementById('cartCustomerPhone');

    btnCustomerToggle.addEventListener('click', function () {
        customerInlineForm.classList.toggle('d-none');
        if (!customerInlineForm.classList.contains('d-none')) {
            cartCustomerName.focus();
        }
    });

    cartCustomerName.addEventListener('input', function () {
        const val = this.value.trim();
        document.getElementById('displayCustomerName').innerText = val || 'Walk-in Customer';
        document.getElementById('modalCustomerName').value = val;
    });

    cartCustomerPhone.addEventListener('input', function () {
        const val = this.value.trim();
        document.getElementById('displayCustomerPhone').innerText = val || '—';
        document.getElementById('modalCustomerPhone').value = val;
    });

    // -------------------------------------------------------------
    // Checkout Modal Logic
    // -------------------------------------------------------------
    document.getElementById('checkoutModal').addEventListener('show.bs.modal', function () {
        const subTotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
        const discount = parseFloat(document.getElementById('discountInput').value) || 0;
        const total = Math.max(subTotal - discount, 0);

        // Pre-fill modal fields
        document.getElementById('modalCustomerName').value = cartCustomerName.value || 'Walk-in Customer';
        document.getElementById('modalCustomerPhone').value = cartCustomerPhone.value || '';
        document.getElementById('paidAmountInput').value = total;
        document.getElementById('modalPayableTotal').innerText = 'Rs. ' + total.toLocaleString();
        calculateChange();
    });

    function setQuickCash(amount) {
        const subTotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
        const discount = parseFloat(document.getElementById('discountInput').value) || 0;
        const total = Math.max(subTotal - discount, 0);

        if (amount === 'exact') {
            document.getElementById('paidAmountInput').value = total;
        } else {
            document.getElementById('paidAmountInput').value = amount;
        }
        calculateChange();
    }

    function calculateChange() {
        const subTotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
        const discount = parseFloat(document.getElementById('discountInput').value) || 0;
        const total = Math.max(subTotal - discount, 0);
        const paid = parseFloat(document.getElementById('paidAmountInput').value) || 0;

        const changeAmountEl = document.getElementById('changeDueAmount');
        const changeLabelEl = document.getElementById('changeDueLabel');
        const changeBox = document.getElementById('changeDueBox');

        if (paid >= total) {
            const change = paid - total;
            changeLabelEl.innerText = 'Change to Return:';
            changeLabelEl.className = 'small fw-semibold text-success';
            changeAmountEl.innerText = 'Rs. ' + change.toLocaleString();
            changeAmountEl.className = 'fw-bold fs-6 text-success';
            changeBox.style.background = '#f4fbf7';
            changeBox.style.borderColor = '#c7ecd9';
        } else {
            const balance = total - paid;
            changeLabelEl.innerText = 'Remaining Balance:';
            changeLabelEl.className = 'small fw-semibold text-danger';
            changeAmountEl.innerText = 'Rs. ' + balance.toLocaleString();
            changeAmountEl.className = 'fw-bold fs-6 text-danger';
            changeBox.style.background = '#fdf4f4';
            changeBox.style.borderColor = '#f5c6cb';
        }
    }

    // -------------------------------------------------------------
    // Submit Sale to Backend
    // -------------------------------------------------------------
    function submitSale() {
        const errorBox = document.getElementById('checkoutError');
        errorBox.innerText = '';

        if (cart.length === 0) {
            errorBox.innerText = 'Cart is empty. Please add items to proceed.';
            return;
        }

        const customerName = document.getElementById('modalCustomerName').value.trim() || 'Walk-in Customer';
        const customerPhone = document.getElementById('modalCustomerPhone').value.trim();
        const discount = parseFloat(document.getElementById('discountInput').value) || 0;
        const paidAmount = parseFloat(document.getElementById('paidAmountInput').value) || 0;

        const payload = {
            customer_name: customerName,
            customer_phone: customerPhone,
            discount: discount,
            paid_amount: paidAmount,
            items: cart.map(i => ({ product_id: i.id, quantity: i.quantity })),
        };

        const btnSubmit = document.getElementById('btnSubmitSale');
        const spinner = document.getElementById('submitSpinner');
        const icon = document.getElementById('submitIcon');

        btnSubmit.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');

        fetch('{{ route('pos.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(async (res) => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Error occurred while saving sale.');

            // Open thermal invoice in a new tab
            if (data.invoice_url) {
                window.open(data.invoice_url, '_blank');
            }

            // Reset cart and inputs
            cart = [];
            document.getElementById('discountInput').value = 0;
            cartCustomerName.value = '';
            cartCustomerPhone.value = '';
            document.getElementById('displayCustomerName').innerText = 'Walk-in Customer';
            document.getElementById('displayCustomerPhone').innerText = '—';
            renderCart();

            // Hide modal
            const modalEl = document.getElementById('checkoutModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            }

            if (typeof toastr !== 'undefined') {
                toastr.success('Sale completed successfully! Receipt generated.');
            }
        })
        .catch(err => {
            errorBox.innerText = err.message;
            if (typeof toastr !== 'undefined') {
                toastr.error(err.message);
            }
        })
        .finally(() => {
            btnSubmit.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');
        });
    }

    // -------------------------------------------------------------
    // Keyboard Shortcuts (F2: Search, F4: Clear)
    // -------------------------------------------------------------
    window.addEventListener('keydown', function (e) {
        if (e.key === 'F2') {
            e.preventDefault();
            searchBox.focus();
            searchBox.select();
        } else if (e.key === 'F4') {
            e.preventDefault();
            document.getElementById('btnClearCart').click();
        }
    });

    // Initial render
    renderCart();
</script>
@endpush
