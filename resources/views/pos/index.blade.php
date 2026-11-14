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

    /* ---- Custom Customer Dropdown ---- */
    .cust-picker-wrap {
        position: relative;
    }
    .cust-picker-trigger {
        display: flex;
        align-items: center;
        gap: 7px;
        background: #fff;
        border: 1.5px solid #d9e2ef;
        border-radius: 9px;
        padding: 6px 10px;
        cursor: pointer;
        transition: border-color .15s;
        min-height: 36px;
        user-select: none;
    }
    .cust-picker-trigger:hover,
    .cust-picker-trigger.open { border-color: var(--pos-primary); }
    .cust-picker-trigger .cust-avatar {
        width: 26px; height: 26px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ff6e40 0%, #f25b2a 100%);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 11px;
        flex-shrink: 0;
        letter-spacing: 0;
    }
    .cust-picker-trigger .cust-avatar.walkin {
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
    }
    .cust-picker-trigger .cust-info { flex: 1; min-width: 0; }
    .cust-picker-trigger .cust-name {
        font-size: 0.82rem; font-weight: 600; color: #1e293b;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .cust-picker-trigger .cust-phone {
        font-size: 0.72rem; color: #64748b;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .cust-picker-trigger .cust-chevron {
        font-size: 0.75rem; color: #94a3b8; flex-shrink: 0;
        transition: transform .2s;
    }
    .cust-picker-trigger.open .cust-chevron { transform: rotate(180deg); }

    .cust-picker-panel {
        display: none;
        position: absolute;
        left: 0; right: 0;
        top: calc(100% + 4px);
        z-index: 1050;
        background: #fff;
        border: 1.5px solid #d9e2ef;
        border-radius: 11px;
        box-shadow: 0 8px 28px rgba(0,0,0,0.13);
        overflow: hidden;
    }
    .cust-picker-panel.show { display: block; }

    .cust-search-wrap {
        padding: 8px 10px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
        display: flex; align-items: center; gap: 7px;
    }
    .cust-search-wrap i { color: #94a3b8; font-size: 0.85rem; flex-shrink: 0; }
    .cust-search-input {
        border: none; background: transparent; outline: none;
        font-size: 0.82rem; flex: 1; color: #1e293b;
    }
    .cust-search-input::placeholder { color: #94a3b8; }
    .cust-search-wrap .cust-clear-search {
        background: none; border: none; padding: 0;
        color: #94a3b8; font-size: 0.8rem; cursor: pointer; display: none;
    }

    .cust-list {
        max-height: 220px;
        overflow-y: auto;
        scrollbar-width: thin;
    }
    .cust-list::-webkit-scrollbar { width: 5px; }
    .cust-list::-webkit-scrollbar-thumb { background: #dfe5ed; border-radius: 3px; }

    .cust-list-item {
        display: flex; align-items: center; gap: 9px;
        padding: 7px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f8fafc;
        transition: background .1s;
    }
    .cust-list-item:last-child { border-bottom: none; }
    .cust-list-item:hover, .cust-list-item.highlighted { background: #fff4f1; }
    .cust-list-item.selected { background: #fff4f1; }
    .cust-list-item .item-avatar {
        width: 28px; height: 28px; border-radius: 50%;
        background: linear-gradient(135deg, #ff6e40 0%, #f25b2a 100%);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 11px; flex-shrink: 0;
    }
    .cust-list-item .item-avatar.walkin {
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
    }
    .cust-list-item .item-avatar.add-new {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        font-size: 15px;
    }
    .cust-list-item .item-body { flex: 1; min-width: 0; }
    .cust-list-item .item-name {
        font-size: 0.82rem; font-weight: 600; color: #1e293b;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .cust-list-item .item-name .highlight { background: #ffd5c8; border-radius: 2px; padding: 0 1px; }
    .cust-list-item .item-phone {
        font-size: 0.71rem; color: #64748b;
    }
    .cust-list-item .item-badges { display: flex; gap: 4px; flex-shrink: 0; flex-wrap: wrap; justify-content: flex-end; }
    .cust-list-item .adv-badge {
        font-size: 0.65rem; padding: 1px 5px; border-radius: 4px;
        background: #dcfce7; color: #16a34a; font-weight: 600;
    }
    .cust-list-item .due-badge {
        font-size: 0.65rem; padding: 1px 5px; border-radius: 4px;
        background: #fee2e2; color: #dc2626; font-weight: 600;
    }
    .cust-list-item.add-new-item .item-name { color: #2563eb; }
    .cust-no-results {
        padding: 16px 12px; text-align: center;
        font-size: 0.8rem; color: #94a3b8;
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
        padding: 9px 11px;
        margin-bottom: 8px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .cart-item-row:hover {
        background: #fafbfd;
        border-color: #dce5ee;
    }
    .cart-item-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .cart-item-title {
        font-size: 0.86rem;
        font-weight: 600;
        color: #293134;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
    }
    .cart-item-controls {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
    }
    .cart-discount-wrapper {
        display: inline-flex;
        align-items: center;
        background: #fdf6f6;
        border: 1px solid #f8d7da;
        border-radius: 7px;
        padding: 2px 4px;
        width: 66px;
        gap: 1px;
    }
    .cart-discount-input {
        width: 44px;
        border: none;
        background: transparent;
        font-size: 0.82rem;
        font-weight: 600;
        text-align: right;
        color: #dc3545;
        padding: 0;
        outline: none;
    }
    .cart-discount-input:focus {
        background: #ffffff;
    }
    .cart-discount-suffix {
        font-size: 0.72rem;
        font-weight: 600;
        color: #dc3545;
    }
    .cart-price-tag {
        font-size: 0.78rem;
        color: #495057;
        background: #f1f4f8;
        padding: 3px 6px;
        border-radius: 6px;
        white-space: nowrap;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        transition: background 0.1s ease;
    }
    .cart-price-tag:hover {
        background: #e2e8f0;
        color: #0d6efd;
    }
    .qty-stepper {
        display: flex;
        align-items: center;
        background: #f1f4f8;
        border-radius: 8px;
        padding: 2px;
    }
    .qty-stepper-btn {
        width: 24px;
        height: 24px;
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
        width: 30px;
        text-align: center;
        font-size: 0.82rem;
        font-weight: 600;
        color: #293134;
        border: none;
        background: transparent;
    }
    .cart-item-total {
        min-width: 75px;
        text-align: right;
    }
    .cart-item-final-amt {
        font-weight: 700;
        font-size: 0.88rem;
        color: #293134;
        line-height: 1.1;
    }
    .cart-item-disc-amt {
        font-size: 0.72rem;
        line-height: 1.1;
    }
    .btn-delete-item {
        background: none;
        border: none;
        color: #adb5bd;
        padding: 2px 4px;
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
    .btn-sale-type {
        border: none;
        background: transparent;
        color: #64748b;
        transition: all 0.2s ease;
    }
    .btn-sale-type.active {
        background: #ffffff !important;
        color: #ff6e40 !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
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
                <span id="posLiveClock" class="fw-semibold"></span>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        {{-- <!-- Shortcut Help Tag -->
        <span class="badge bg-light text-secondary border px-3 py-2 d-none d-md-inline-block">
            <i class="bi bi-keyboard me-1"></i> <kbd class="bg-white text-dark border px-1">F2</kbd> Search &bull; <kbd class="bg-white text-dark border px-1">F4</kbd> Clear Cart
        </span>

        <!-- Sales Return Shortcut Button -->
        <a href="{{ route('sales-returns.create') }}" class="btn btn-outline-warning btn-sm px-3 d-flex align-items-center gap-1" title="Sales Return">
            <i class="bi bi-arrow-counterclockwise"></i>
            <span class="d-none d-sm-inline">Sales Return</span>
        </a> --}}

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
                {{-- @foreach ($categories as $cat)
                    @php
                        $catProductCount = $products->where('category_id', $cat->id)->count();
                    @endphp
                    @if ($catProductCount > 0)
                        <div class="category-pill" data-category="{{ $cat->id }}">
                            {{ $cat->name }} ({{ $catProductCount }})
                        </div>
                    @endif
                @endforeach --}}
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
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-secondary">
                        <i class="bi bi-person-circle me-1"></i> Customer
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <span id="cartCustomerAdvanceBadge" class="badge bg-success-subtle text-success border border-success-subtle d-none" style="font-size:0.68rem;"></span>
                        <span id="cartCustomerDueBadge" class="badge bg-danger-subtle text-danger border border-danger-subtle d-none" style="font-size:0.68rem;"></span>
                    </div>
                </div>

                {{-- Hidden native select kept for JS compatibility --}}
                <select id="cartCustomerSelect" class="d-none">
                    <option value="" data-name="Walk-in Customer" data-phone="" data-advance="0" data-due="0">Walk-in Customer</option>
                    <option value="__new__">__new__</option>
                    @php
                        $validCustomers = $customers->filter(fn($c) => strtolower(trim($c->name)) !== 'walk-in customer');
                    @endphp
                    @foreach($validCustomers as $c)
                        <option value="{{ $c->id }}"
                                data-name="{{ $c->name }}"
                                data-phone="{{ $c->phone ?? '' }}"
                                data-advance="{{ (float)$c->advance_balance }}"
                                data-due="{{ (float)$c->total_due }}">
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>

                {{-- Custom Searchable Picker --}}
                <div class="cust-picker-wrap" id="custPickerWrap">
                    {{-- Trigger button --}}
                    <div class="cust-picker-trigger" id="custPickerTrigger" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false">
                        <div class="cust-avatar walkin" id="custTriggerAvatar">W</div>
                        <div class="cust-info">
                            <div class="cust-name" id="custTriggerName">Walk-in Customer</div>
                            <div class="cust-phone" id="custTriggerPhone">No phone</div>
                        </div>
                        <i class="bi bi-chevron-down cust-chevron"></i>
                    </div>

                    {{-- Dropdown panel --}}
                    <div class="cust-picker-panel" id="custPickerPanel" role="listbox">
                        {{-- Search bar --}}
                        <div class="cust-search-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" class="cust-search-input" id="custSearchInput"
                                   placeholder="Search by name or phone..." autocomplete="off">
                            <button class="cust-clear-search" id="custClearSearch" type="button" title="Clear">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        </div>

                        {{-- List --}}
                        <div class="cust-list" id="custList" role="listbox">
                            {{-- Populated by JS --}}
                        </div>
                    </div>
                </div>

                {{-- Hidden display helpers used by JS --}}
                <span id="displayCustomerName" class="d-none">Walk-in Customer</span>
                <span id="displayCustomerPhone" class="d-none">—</span>

                {{-- New Customer inline form --}}
                <div id="customerInlineForm" class="mt-2 p-2 bg-light rounded-2 border d-none">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="small fw-bold text-dark"><i class="bi bi-person-plus text-primary me-1"></i>New Customer</span>
                        <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" id="btnCancelNewCustomer">&times; Cancel</button>
                    </div>
                    <input type="text" id="cartCustomerName" list="customersDatalist" class="form-control form-control-sm mb-1" placeholder="Customer Name *">
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
                    <span class="fw-semibold text-dark" id="subTotalDisplay">Rs. 0.00</span>
                </div>
                <div class="summary-row text-danger" id="summaryDiscountRow" style="display:none;">
                    <span class="fw-semibold">Item Discounts</span>
                    <span class="fw-semibold" id="summaryDiscountDisplay">- Rs. 0.00</span>
                </div>

                <!-- Grand Total -->
                <div class="grand-total-row">
                    <span class="grand-total-label">Grand Total</span>
                    <span class="grand-total-amount" id="grandTotalDisplay">Rs. 0.00</span>
                </div>

                <!-- Checkout Button -->
                <button type="button" class="btn btn-checkout-pos w-100" id="btnOpenCheckoutModal" disabled>
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
                <button type="button" class="btn-close" id="btnCloseModalX" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Sale Mode Toggle: Cash vs Credit (Installments) -->
                <div class="d-flex p-1 bg-light rounded-3 mb-3 border">
                    <button type="button" class="btn btn-sm flex-fill fw-semibold py-2 rounded-2 btn-sale-type active" id="btnTypeCash" onclick="setSaleType('cash')">
                        <i class="bi bi-cash me-1"></i> Full Cash Payment
                    </button>
                    <button type="button" class="btn btn-sm flex-fill fw-semibold py-2 rounded-2 btn-sale-type text-muted" id="btnTypeCredit" onclick="setSaleType('credit')">
                        <i class="bi bi-wallet2 me-1"></i> Credit / Installments (Udhaar)
                    </button>
                </div>

                <!-- Highlight Total Payable & Advance Adjustment -->
                <div class="modal-payable-box mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="modal-payable-label">Original Invoice Total:</span>
                        <span class="modal-payable-amount fs-5" id="modalPayableTotal">Rs. 0.0</span>
                    </div>
                    <div id="modalAdvanceDeductionRow" class="d-none justify-content-between align-items-center text-success mt-2 pt-2 border-top" style="border-color: rgba(255,255,255,0.2) !important;">
                        <span class="small fw-semibold text-white-50"><i class="bi bi-wallet2 me-1"></i> Advance Payment:</span>
                        <span class="fw-bold text-white fs-6" id="modalAdvanceDeductionAmount">- Rs. 0.0</span>
                    </div>
                    <div id="modalNetPayableRow" class="d-none justify-content-between align-items-center mt-1 pt-1 border-top" style="border-color: rgba(255,255,255,0.2) !important;">
                        <span class="small fw-bold text-white">Remaining Payable Amount:</span>
                        <span class="fw-bold text-white fs-5" id="modalNetPayableAmount">Rs. 0.0</span>
                    </div>
                </div>

                <!-- Order Date (Custom previous/current/future date option) -->
                <div class="mb-3 p-2 rounded-2 bg-light border">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label small fw-semibold text-dark mb-0">
                            <i class="bi bi-calendar-event me-1 text-primary"></i> Sale Order Date
                        </label>
                        <span class="badge bg-white text-muted border small" id="orderDateBadge">Today</span>
                    </div>
                    <input type="date" id="modalOrderDate" class="form-control form-control-sm mt-1 fw-semibold" value="{{ date('Y-m-d') }}" onchange="onOrderDateChange()">
                    <div class="text-muted small mt-1" style="font-size: 0.73rem;">
                        Allows recording sales for previous dates or future dates.
                    </div>
                </div>

                <!-- Customer Details -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold text-dark">
                            Customer Name <span id="reqCustName" class="text-danger d-none">*</span>
                        </label>
                        <input type="text" id="modalCustomerName" list="customersDatalist" class="form-control" placeholder="Walk-in Customer" oninput="onCustomerInput()" autocomplete="off">
                        <datalist id="customersDatalist">
                            @foreach($customers as $c)
                                <option value="{{ data_get($c, 'name') }}" data-phone="{{ data_get($c, 'phone') }}" data-id="{{ data_get($c, 'id') }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold text-dark">
                            Phone Number <span id="reqCustPhone" class="text-danger d-none">*</span>
                        </label>
                        <input type="text" id="modalCustomerPhone" class="form-control" placeholder="03xx-xxxxxxx" oninput="onCustomerInput()">
                    </div>
                    <div id="customerExistingAlert" class="col-12 d-none">
                        <div class="alert alert-info py-2 px-3 small mb-0 d-flex flex-wrap justify-content-between align-items-center gap-2 rounded-2">
                            <span><i class="bi bi-person-check me-1"></i> Existing Customer Matched</span>
                            <div class="d-flex align-items-center gap-2">
                                <span id="custAdvanceBadge" class="badge bg-success text-white fw-bold d-none"></span>
                                <span id="custPrevDueBadge" class="fw-bold"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Amount Received & Quick Cash -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark d-flex justify-content-between">
                        <span id="paidAmountLabel">Amount Paid by Customer (Rs.)</span>
                        <span class="text-muted small" id="paidAmountHelp">Auto-calculated</span>
                    </label>
                    <input type="number" id="paidAmountInput" class="form-control form-control-lg text-end fw-bold" min="0" oninput="calculateChange()">
                    
                    <!-- Quick Cash Buttons -->
                    <div class="quick-cash-grid" id="quickCashContainer">
                        <button type="button" class="btn-quick-cash" onclick="setQuickCash('exact')">Exact Bill</button>
                        <button type="button" class="btn-quick-cash" onclick="setQuickCash(500)">Rs. 500</button>
                        <button type="button" class="btn-quick-cash" onclick="setQuickCash(1000)">Rs. 1,000</button>
                        <button type="button" class="btn-quick-cash" onclick="setQuickCash(5000)">Rs. 5,000</button>
                    </div>
                </div>

                <!-- Change Due / Remaining Balance Box -->
                <div class="change-due-box" id="changeDueBox">
                    <span class="small fw-semibold" id="changeDueLabel">Change Return:</span>
                    <span class="fw-bold fs-6 text-success" id="changeDueAmount">Rs. 0.0</span>
                </div>

                <!-- Credit / Installment Details (Visible when Credit selected) -->
                <div id="creditFieldsSection" class="mt-3 p-3 rounded-3 d-none" style="background:#fffaf8; border:1px dashed #ffb899;">
                    <div>
                        <label class="form-label small fw-semibold text-dark">
                            <i class="bi bi-chat-left-text me-1 text-primary"></i> Agreement / Installment Note (Optional)
                        </label>
                        <input type="text" id="modalSaleNotes" class="form-control form-control-sm" placeholder="e.g. Flexible installment payments">
                    </div>
                </div>

                <!-- Error Message Box -->
                <div id="checkoutError" class="text-danger small mt-2"></div>
            </div>

            <div class="modal-footer border-top py-3 px-4" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                <button type="button" class="btn btn-secondary px-3" id="btnCancelModal">Cancel</button>
                <button type="button" class="btn btn-success px-4 fw-semibold" id="btnSubmitSale" onclick="submitSale()">
                    <span id="submitSpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                    <i class="bi bi-printer me-1" id="submitIcon"></i>
                    <span id="submitSaleBtnText">Generate Bill</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT ITEM PRICE MODAL -->
<div class="modal fade" id="editPriceModal" tabindex="-1" aria-labelledby="editPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4" style="background:#fafbfc; border-radius: 14px 14px 0 0;">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 mb-0" id="editPriceModalLabel">
                    <i class="bi bi-tag text-primary"></i> Change Item Price
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1">Product</label>
                    <div class="fw-bold text-dark text-truncate" id="modalEditProductName">—</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark mb-1">Unit Price (Rs.)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light fw-bold text-muted">Rs.</span>
                        <input type="number" step="0.01" min="0" max="99999" id="modalEditItemPrice" class="form-control form-control-lg fw-bold text-primary" placeholder="0.00" oninput="updateModalDiscountPreview()">
                    </div>
                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">Enter the base price for this item.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark mb-1">Discount (%)</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" max="100" id="modalEditItemDiscount" class="form-control form-control-lg fw-bold text-danger text-end" placeholder="0" oninput="updateModalDiscountPreview()">
                        <span class="input-group-text bg-light fw-bold text-muted">%</span>
                    </div>
                    <div class="text-muted small mt-1" style="font-size: 0.75rem;" id="modalEditDiscountPreview">Discount per unit: Rs. 0.00 | Net Price: Rs. 0.00</div>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="updateMasterProductPriceCheck">
                    <label class="form-check-label small text-dark" for="updateMasterProductPriceCheck" style="font-size: 0.8rem;">
                        Also update price in product catalog
                    </label>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-3" style="background:#fafbfc; border-radius: 0 0 14px 14px;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary fw-semibold px-3" id="btnSaveCartPrice" onclick="saveCartPrice()">
                    <i class="bi bi-check-lg me-1"></i> Update Price
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>

    function getCheckoutModal() {
        const el = document.getElementById('checkoutModal');
        return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    }
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
            cart.push({ ...product, quantity: 1, discount_percent: 0 });
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

    function setDiscount(id, val) {
        const item = cart.find(i => i.id === id);
        if (!item) return;

        let disc = parseFloat(val);
        if (isNaN(disc) || disc < 0) disc = 0;
        if (disc > 100) disc = 100;
        item.discount_percent = disc;

        // Step 1: Calculate discount per unit = price * (discountPercent / 100)
        const discountPerUnit = item.price * (item.discount_percent / 100);
        // Step 2: Calculate discounted unit price = price - discountPerUnit
        const discountedPrice = Math.max(0, item.price - discountPerUnit);
        // Step 3: Calculate final subtotal = discountedPrice * qty
        const lineTotal = discountedPrice * item.quantity;
        const lineRawTotal = item.price * item.quantity;
        const lineDiscountAmount = Math.max(0, lineRawTotal - lineTotal);

        const totalEl = document.getElementById(`cart-item-total-${id}`);
        if (totalEl) {
            totalEl.innerHTML = `
                <div class="cart-item-final-amt">Rs. ${lineTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                ${item.discount_percent > 0 ? `<div class="cart-item-disc-amt text-danger small">(-Rs. ${lineDiscountAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})})</div>` : ''}
            `;
        }

        updateCartTotalsOnly();
    }

    function updateCartTotalsOnly() {
        let rawSubtotal = 0;
        let totalDiscount = 0;
        let total = 0;

        cart.forEach(item => {
            const qty = item.quantity;
            const price = item.price;
            const discountPercent = item.discount_percent || 0;

            const discountPerUnit = price * (discountPercent / 100);
            const discountedPrice = Math.max(0, price - discountPerUnit);
            const lineTotal = discountedPrice * qty;
            const lineRaw = price * qty;

            rawSubtotal += lineRaw;
            totalDiscount += (lineRaw - lineTotal);
            total += lineTotal;
        });

        const subTotalDisplay = document.getElementById('subTotalDisplay');
        if (subTotalDisplay) {
            subTotalDisplay.innerText = 'Rs. ' + rawSubtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        const discRow = document.getElementById('summaryDiscountRow');
        const discDisplay = document.getElementById('summaryDiscountDisplay');
        if (discRow && discDisplay) {
            if (totalDiscount > 0) {
                discRow.style.display = 'flex';
                discDisplay.innerText = '- Rs. ' + totalDiscount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            } else {
                discRow.style.display = 'none';
            }
        }

        const grandTotalDisplay = document.getElementById('grandTotalDisplay');
        if (grandTotalDisplay) {
            grandTotalDisplay.innerText = 'Rs. ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        const checkoutBtnText = document.getElementById('checkoutBtnText');
        if (checkoutBtnText && cart.length > 0) {
            checkoutBtnText.innerText = `Pay & Bill (Rs. ${total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})})`;
        }

        updateCheckoutTotals();
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
        const checkoutBtn = document.getElementById('btnOpenCheckoutModal');
        const countBadge = document.getElementById('cartCountBadge');

        const totalItemsCount = cart.reduce((sum, i) => sum + i.quantity, 0);
        countBadge.innerText = totalItemsCount;

        if (cart.length === 0) {
            cartList.innerHTML = `
                <div id="emptyCartView" class="empty-cart-view">
                    <i class="bi bi-cart-x empty-cart-icon"></i>
                    <h6 class="fw-semibold text-secondary mb-1">Cart is empty</h6>
                    <p class="small text-muted mb-0">Click any product from the catalog on the left to add it to this order.</p>
                </div>
            `;
            checkoutBtn.disabled = true;
            document.getElementById('checkoutBtnText').innerText = 'Confirm & Generate Bill';
        } else {
            checkoutBtn.disabled = false;
            cartList.innerHTML = '';

            cart.forEach(item => {
                const discountPercent = item.discount_percent || 0;
                const discountPerUnit = item.price * (discountPercent / 100);
                const discountedPrice = Math.max(0, item.price - discountPerUnit);
                const lineTotal = discountedPrice * item.quantity;
                const lineRaw = item.price * item.quantity;
                const lineDiscountAmount = Math.max(0, lineRaw - lineTotal);

                const row = document.createElement('div');
                row.className = 'cart-item-row';
                row.id = `cart-row-${item.id}`;
                row.innerHTML = `
                    <div class="cart-item-top">
                        <div class="cart-item-title" title="${item.name}">${item.name}</div>
                        <button type="button" class="btn-delete-item" onclick="removeItem(${item.id})" title="Remove item">
                            <i class="bi bi-x-circle fs-6"></i>
                        </button>
                    </div>
                    <div class="cart-item-controls">
                        <div class="qty-stepper" title="Quantity">
                            <button type="button" class="qty-stepper-btn" onclick="changeQty(${item.id}, -1)">&minus;</button>
                            <input type="text" class="qty-stepper-value" value="${item.quantity}" onchange="setDirectQty(${item.id}, this.value)">
                            <button type="button" class="qty-stepper-btn" onclick="changeQty(${item.id}, 1)">&plus;</button>
                        </div>
                        <div class="cart-discount-wrapper" title="Discount (%)">
                            <input type="number" step="0.01" min="0" max="100" class="cart-discount-input" value="${item.discount_percent || 0}" oninput="setDiscount(${item.id}, this.value)" placeholder="0" id="cart-disc-${item.id}">
                            <span class="cart-discount-suffix">%</span>
                        </div>
                        <div class="cart-price-tag" onclick="openEditPriceModal(${item.id})" title="Click to edit price / discount">
                            Rs. ${item.price.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})}
                            <i class="bi bi-pencil-square ms-1 text-primary" style="font-size:0.75rem;"></i>
                        </div>
                        <div class="cart-item-total" id="cart-item-total-${item.id}">
                            <div class="cart-item-final-amt">Rs. ${lineTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            ${discountPercent > 0 ? `<div class="cart-item-disc-amt text-danger small">(-Rs. ${lineDiscountAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})})</div>` : ''}
                        </div>
                    </div>
                `;
                cartList.appendChild(row);
            });
        }

        updateCartTotalsOnly();
        calculateChange();
    }

    // -------------------------------------------------------------
    // Edit Cart Item Price Modal & Handler
    // -------------------------------------------------------------
    let editingItemId = null;

    function openEditPriceModal(id) {
        const item = cart.find(i => i.id === id);
        if (!item) return;
        editingItemId = id;

        document.getElementById('modalEditProductName').innerText = item.name;
        document.getElementById('modalEditItemPrice').value = item.price;
        document.getElementById('modalEditItemDiscount').value = item.discount_percent || 0;
        document.getElementById('updateMasterProductPriceCheck').checked = false;

        updateModalDiscountPreview();

        const modalEl = document.getElementById('editPriceModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.show();

        setTimeout(() => {
            const input = document.getElementById('modalEditItemPrice');
            input.focus();
            input.select();
        }, 300);
    }

    function updateModalDiscountPreview() {
        const price = parseFloat(document.getElementById('modalEditItemPrice').value) || 0;
        const disc = parseFloat(document.getElementById('modalEditItemDiscount').value) || 0;
        const discPerUnit = price * (disc / 100);
        const netPrice = Math.max(0, price - discPerUnit);
        const previewEl = document.getElementById('modalEditDiscountPreview');
        if (previewEl) {
            previewEl.innerText = `Discount per unit: Rs. ${discPerUnit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} | Net Price: Rs. ${netPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }
    }

    function saveCartPrice() {
        if (!editingItemId) return;
        const item = cart.find(i => i.id === editingItemId);
        if (!item) return;

        const priceVal = document.getElementById('modalEditItemPrice').value;
        const newPrice = parseFloat(priceVal);

        const discVal = document.getElementById('modalEditItemDiscount').value;
        let newDiscount = parseFloat(discVal);
        if (isNaN(newDiscount) || newDiscount < 0) newDiscount = 0;
        if (newDiscount > 100) newDiscount = 100;

        if (isNaN(newPrice) || newPrice < 0) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Please enter a valid price (0 or higher).');
            } else {
                alert('Please enter a valid price.');
            }
            return;
        }

        item.price = newPrice;
        item.discount_percent = newDiscount;

        const updateMaster = document.getElementById('updateMasterProductPriceCheck').checked;
        if (updateMaster) {
            fetch(`/pos/products/${item.id}/update-price`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ sale_price: newPrice })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    const card = document.querySelector(`.product-card[data-id="${item.id}"]`);
                    if (card) {
                        card.dataset.price = newPrice;
                        const priceBadge = card.querySelector('.price-badge');
                        if (priceBadge) priceBadge.innerText = 'Rs. ' + newPrice.toLocaleString();
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.info('Standard catalog price updated.');
                    }
                }
            })
            .catch(e => console.error(e));
        }

        renderCart();

        const modalEl = document.getElementById('editPriceModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();

        if (typeof toastr !== 'undefined') {
            toastr.success(`Item updated: Rs. ${newPrice.toLocaleString()} (Disc: ${newDiscount}%)`);
        }
    }

    document.getElementById('modalEditItemPrice').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveCartPrice();
        }
    });

    document.getElementById('modalEditItemDiscount').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveCartPrice();
        }
    });

    // ================================================================
    // CUSTOM CUSTOMER PICKER  (searchable dropdown)
    // ================================================================
    const cartCustomerSelect   = document.getElementById('cartCustomerSelect');
    const cartCustomerName     = document.getElementById('cartCustomerName');
    const cartCustomerPhone    = document.getElementById('cartCustomerPhone');
    const customerInlineForm   = document.getElementById('customerInlineForm');
    const displayCustomerName  = document.getElementById('displayCustomerName');
    const displayCustomerPhone = document.getElementById('displayCustomerPhone');
    const cartCustomerAdvBadge = document.getElementById('cartCustomerAdvanceBadge');
    const cartCustomerDueBadge = document.getElementById('cartCustomerDueBadge');

    // Picker elements
    const custPickerTrigger  = document.getElementById('custPickerTrigger');
    const custPickerPanel    = document.getElementById('custPickerPanel');
    const custSearchInput    = document.getElementById('custSearchInput');
    const custClearSearch    = document.getElementById('custClearSearch');
    const custList           = document.getElementById('custList');
    const custTriggerAvatar  = document.getElementById('custTriggerAvatar');
    const custTriggerName    = document.getElementById('custTriggerName');
    const custTriggerPhone   = document.getElementById('custTriggerPhone');
    const btnCancelNewCustomer = document.getElementById('btnCancelNewCustomer');

    let currentSaleType = 'cash';
    let matchedCustomerId = null;
    let matchedCustomerAdvance = 0;
    let currentAdvanceApplied = 0;
    let currentNetPayable = 0;
    const existingCustomers = @json($customers);

    // Helper: initials from name
    function getInitials(name) {
        if (!name || name.toLowerCase() === 'walk-in customer') return 'W';
        const parts = name.trim().split(' ');
        return parts.length >= 2
            ? (parts[0][0] + parts[1][0]).toUpperCase()
            : parts[0][0].toUpperCase();
    }

    // Helper: highlight matching text
    function highlightText(text, query) {
        if (!query) return escapeHtml(text);
        const idx = text.toLowerCase().indexOf(query.toLowerCase());
        if (idx === -1) return escapeHtml(text);
        return escapeHtml(text.substring(0, idx))
            + '<span class="highlight">' + escapeHtml(text.substring(idx, idx + query.length)) + '</span>'
            + escapeHtml(text.substring(idx + query.length));
    }

    function escapeHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Build list items
    function buildCustList(query) {
        query = (query || '').trim();
        const q = query.toLowerCase();

        let filtered = existingCustomers.filter(c => {
            if (!c.name || c.name.toLowerCase() === 'walk-in customer') return false;
            if (!q) return true;
            return c.name.toLowerCase().includes(q) || (c.phone && c.phone.includes(q));
        });

        custList.innerHTML = '';

        // Walk-in Customer (always first, only when no search query)
        if (!q) {
            const walkEl = buildListItem({
                id: '', name: 'Walk-in Customer', phone: '', advance: 0, due: 0
            }, '', true);
            custList.appendChild(walkEl);
        }

        // Registered customers
        if (filtered.length > 0) {
            filtered.forEach(c => {
                custList.appendChild(buildListItem(c, query, false));
            });
        } else if (q) {
            const noRes = document.createElement('div');
            noRes.className = 'cust-no-results';
            noRes.innerHTML = `<i class="bi bi-search me-1"></i>No customer found for "<b>${escapeHtml(query)}</b>"`;
            custList.appendChild(noRes);
        }

        // Add New Customer - always at bottom
        const addEl = document.createElement('div');
        addEl.className = 'cust-list-item add-new-item';
        addEl.dataset.action = 'new';
        addEl.innerHTML = `
            <div class="item-avatar add-new"><i class="bi bi-plus-lg"></i></div>
            <div class="item-body">
                <div class="item-name"><i class="bi bi-person-plus me-1"></i>Add New Customer</div>
                <div class="item-phone">Register a new customer</div>
            </div>`;
        addEl.addEventListener('click', openNewCustomerForm);
        custList.appendChild(addEl);
    }

    function buildListItem(c, query, isWalkin) {
        const el = document.createElement('div');
        el.className = 'cust-list-item' + (isWalkin ? '' : '');
        el.dataset.id = c.id || '';

        const advance = parseFloat(c.advance_balance) || 0;
        const due = parseFloat(c.total_due) || 0;
        const avatarClass = isWalkin ? 'item-avatar walkin' : 'item-avatar';
        const initials = isWalkin ? 'W' : getInitials(c.name);
        const phoneTxt = c.phone ? c.phone : '<span class="text-muted" style="font-style:italic;">No phone</span>';
        const nameHtml = query ? highlightText(c.name, query) : escapeHtml(c.name);
        const phoneHtml = (query && c.phone) ? highlightText(c.phone, query) : (c.phone ? escapeHtml(c.phone) : '<span style="font-style:italic;color:#94a3b8;">No phone</span>');

        let badges = '';
        if (advance > 0) badges += `<span class="adv-badge">Adv ${advance.toLocaleString()}</span>`;
        if (due > 0) badges += `<span class="due-badge">Due ${due.toLocaleString()}</span>`;

        el.innerHTML = `
            <div class="${avatarClass}">${escapeHtml(initials)}</div>
            <div class="item-body">
                <div class="item-name">${nameHtml}</div>
                <div class="item-phone"><i class="bi bi-telephone" style="font-size:0.68rem;"></i> ${phoneHtml}</div>
            </div>
            ${badges ? `<div class="item-badges">${badges}</div>` : ''}`;

        el.addEventListener('click', () => {
            if (isWalkin) {
                selectCustPicker(null);
            } else {
                selectCustPicker(c);
            }
            closePicker();
        });

        return el;
    }

    function openPicker() {
        custPickerPanel.classList.add('show');
        custPickerTrigger.classList.add('open');
        custPickerTrigger.setAttribute('aria-expanded', 'true');
        buildCustList('');
        custSearchInput.value = '';
        custClearSearch.style.display = 'none';
        setTimeout(() => custSearchInput.focus(), 50);
    }

    function closePicker() {
        custPickerPanel.classList.remove('show');
        custPickerTrigger.classList.remove('open');
        custPickerTrigger.setAttribute('aria-expanded', 'false');
    }

    // Toggle picker on trigger click
    custPickerTrigger.addEventListener('click', function(e) {
        if (custPickerPanel.classList.contains('show')) {
            closePicker();
        } else {
            openPicker();
        }
    });

    // Keyboard accessibility on trigger
    custPickerTrigger.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openPicker(); }
        if (e.key === 'Escape') closePicker();
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!document.getElementById('custPickerWrap').contains(e.target)) {
            closePicker();
        }
    });

    // Live search
    custSearchInput.addEventListener('input', function() {
        const q = this.value;
        custClearSearch.style.display = q ? 'block' : 'none';
        buildCustList(q);
    });

    custClearSearch.addEventListener('click', function() {
        custSearchInput.value = '';
        this.style.display = 'none';
        buildCustList('');
        custSearchInput.focus();
    });

    // Keyboard nav inside list
    custSearchInput.addEventListener('keydown', function(e) {
        const items = custList.querySelectorAll('.cust-list-item');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length) items[0].focus();
        } else if (e.key === 'Escape') {
            closePicker();
            custPickerTrigger.focus();
        }
    });

    // Update trigger display
    function updateTriggerDisplay(c) {
        if (!c || !c.id) {
            custTriggerAvatar.className = 'cust-avatar walkin';
            custTriggerAvatar.textContent = 'W';
            custTriggerName.textContent = 'Walk-in Customer';
            custTriggerPhone.textContent = 'No phone registered';
        } else {
            const initials = getInitials(c.name);
            custTriggerAvatar.className = 'cust-avatar';
            custTriggerAvatar.textContent = initials;
            custTriggerName.textContent = c.name;
            custTriggerPhone.textContent = c.phone || 'No phone';
        }
    }

    // Select a customer via picker
    function selectCustPicker(c) {
        if (c) {
            setCustomerFromModel(c);
            if (cartCustomerSelect) cartCustomerSelect.value = c.id;
        } else {
            setCustomerFromModel(null);
            if (cartCustomerSelect) cartCustomerSelect.value = '';
        }
        updateTriggerDisplay(c || null);
    }

    // Open new customer form
    function openNewCustomerForm() {
        closePicker();
        if (customerInlineForm) {
            customerInlineForm.classList.remove('d-none');
            if (cartCustomerName) { cartCustomerName.value = ''; cartCustomerName.focus(); }
            if (cartCustomerPhone) cartCustomerPhone.value = '';
        }
        matchedCustomerId = null;
        matchedCustomerAdvance = 0;
        if (cartCustomerAdvBadge) cartCustomerAdvBadge.classList.add('d-none');
        if (cartCustomerDueBadge) cartCustomerDueBadge.classList.add('d-none');
        const mName = document.getElementById('modalCustomerName');
        const mPhone = document.getElementById('modalCustomerPhone');
        if (mName) mName.value = '';
        if (mPhone) mPhone.value = '';
        onCustomerInput();
    }

    if (btnCancelNewCustomer) {
        btnCancelNewCustomer.addEventListener('click', function() {
            customerInlineForm.classList.add('d-none');
            selectCustPicker(null);
        });
    }

    if (cartCustomerName) {
        cartCustomerName.addEventListener('input', function () {
            const val = this.value.trim();
            if (displayCustomerName) displayCustomerName.innerText = val || 'Walk-in Customer';
            const mName = document.getElementById('modalCustomerName');
            if (mName) mName.value = val;

            if (val) {
                const match = existingCustomers.find(c => c.name.toLowerCase() === val.toLowerCase());
                if (match) {
                    setCustomerFromModel(match);
                    updateTriggerDisplay(match);
                    return;
                }
            }
            matchedCustomerId = null;
            matchedCustomerAdvance = 0;
            if (cartCustomerAdvBadge) cartCustomerAdvBadge.classList.add('d-none');
            if (cartCustomerDueBadge) cartCustomerDueBadge.classList.add('d-none');
            onCustomerInput();
        });
    }

    if (cartCustomerPhone) {
        cartCustomerPhone.addEventListener('input', function () {
            const val = this.value.trim();
            if (displayCustomerPhone) displayCustomerPhone.innerText = val || '—';
            const mPhone = document.getElementById('modalCustomerPhone');
            if (mPhone) mPhone.value = val;

            if (val && !matchedCustomerId) {
                const match = existingCustomers.find(c => c.phone && c.phone.trim() === val);
                if (match) {
                    setCustomerFromModel(match);
                    updateTriggerDisplay(match);
                }
            }
        });
    }

    function setCustomerFromModel(c) {
        if (!c || !c.id) {
            matchedCustomerId = null;
            matchedCustomerAdvance = 0;
            if (displayCustomerName) displayCustomerName.innerText = 'Walk-in Customer';
            if (displayCustomerPhone) displayCustomerPhone.innerText = '—';
            if (cartCustomerName) cartCustomerName.value = '';
            if (cartCustomerPhone) cartCustomerPhone.value = '';
            const mName = document.getElementById('modalCustomerName');
            const mPhone = document.getElementById('modalCustomerPhone');
            if (mName) mName.value = 'Walk-in Customer';
            if (mPhone) mPhone.value = '';
            if (cartCustomerAdvBadge) cartCustomerAdvBadge.classList.add('d-none');
            if (cartCustomerDueBadge) cartCustomerDueBadge.classList.add('d-none');
            updateTriggerDisplay(null);
            onCustomerInput();
            return;
        }

        matchedCustomerId = c.id;
        matchedCustomerAdvance = parseFloat(c.advance_balance) || 0;
        const due = parseFloat(c.total_due) || 0;

        if (displayCustomerName) displayCustomerName.innerText = c.name;
        if (displayCustomerPhone) displayCustomerPhone.innerText = c.phone || '—';
        if (cartCustomerName) cartCustomerName.value = c.name;
        if (cartCustomerPhone) cartCustomerPhone.value = c.phone || '';
        const mName = document.getElementById('modalCustomerName');
        const mPhone = document.getElementById('modalCustomerPhone');
        if (mName) mName.value = c.name;
        if (mPhone) mPhone.value = c.phone || '';

        if (cartCustomerAdvBadge) {
            if (matchedCustomerAdvance > 0) {
                cartCustomerAdvBadge.innerText = 'Adv: Rs. ' + matchedCustomerAdvance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                cartCustomerAdvBadge.classList.remove('d-none');
            } else {
                cartCustomerAdvBadge.classList.add('d-none');
            }
        }
        if (cartCustomerDueBadge) {
            if (due > 0) {
                cartCustomerDueBadge.innerText = 'Due: Rs. ' + due.toLocaleString();
                cartCustomerDueBadge.classList.remove('d-none');
            } else {
                cartCustomerDueBadge.classList.add('d-none');
            }
        }

        updateTriggerDisplay(c);
        onCustomerInput();
    }

    // Legacy toggle button (kept as "Add New" shortcut)
    const btnCustomerToggle = document.getElementById('btnCustomerToggle');
    if (btnCustomerToggle) {
        btnCustomerToggle.addEventListener('click', openNewCustomerForm);
    }

    function updateCheckoutTotals() {
        let total = 0;
        let rawSubtotal = 0;
        let totalDiscount = 0;

        cart.forEach(item => {
            const qty = item.quantity;
            const price = item.price;
            const discountPercent = item.discount_percent || 0;

            const discountPerUnit = price * (discountPercent / 100);
            const discountedPrice = Math.max(0, price - discountPerUnit);
            const lineTotal = discountedPrice * qty;
            const lineRaw = price * qty;

            rawSubtotal += lineRaw;
            totalDiscount += (lineRaw - lineTotal);
            total += lineTotal;
        });

        currentAdvanceApplied = Math.min(matchedCustomerAdvance, total);
        currentNetPayable = Math.max(0, total - currentAdvanceApplied);

        document.getElementById('modalPayableTotal').innerText = 'Rs. ' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const advanceRow = document.getElementById('modalAdvanceDeductionRow');
        const netPayableRow = document.getElementById('modalNetPayableRow');

        if (currentAdvanceApplied > 0) {
            if (advanceRow) {
                advanceRow.classList.remove('d-none');
                advanceRow.classList.add('d-flex');
                document.getElementById('modalAdvanceDeductionAmount').innerText = '- Rs. ' + currentAdvanceApplied.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            if (netPayableRow) {
                netPayableRow.classList.remove('d-none');
                netPayableRow.classList.add('d-flex');
                document.getElementById('modalNetPayableAmount').innerText = 'Rs. ' + currentNetPayable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        } else {
            if (advanceRow) {
                advanceRow.classList.add('d-none');
                advanceRow.classList.remove('d-flex');
            }
            if (netPayableRow) {
                netPayableRow.classList.add('d-none');
                netPayableRow.classList.remove('d-flex');
            }
        }

        const paidInput = document.getElementById('paidAmountInput');
        if (currentSaleType === 'cash') {
            paidInput.value = currentNetPayable.toFixed(2);
        }
        calculateChange();
    }

    function setSaleType(type) {
        currentSaleType = type;

        const btnCash = document.getElementById('btnTypeCash');
        const btnCredit = document.getElementById('btnTypeCredit');
        const paidLabel = document.getElementById('paidAmountLabel');
        const paidHelp = document.getElementById('paidAmountHelp');
        const paidInput = document.getElementById('paidAmountInput');
        const quickCash = document.getElementById('quickCashContainer');
        const creditSection = document.getElementById('creditFieldsSection');
        const reqName = document.getElementById('reqCustName');
        const reqPhone = document.getElementById('reqCustPhone');
        const btnSubmitText = document.getElementById('submitSaleBtnText');

        if (type === 'cash') {
            btnCash.classList.add('active');
            btnCash.classList.remove('text-muted');
            btnCredit.classList.remove('active');
            btnCredit.classList.add('text-muted');

            paidLabel.innerText = 'Amount Paid by Customer (Rs.)';
            paidHelp.innerText = currentAdvanceApplied > 0 ? 'Remaining Net Payable' : 'Exact bill';
            paidInput.value = currentNetPayable.toFixed(2);
            quickCash.classList.remove('d-none');
            creditSection.classList.add('d-none');
            reqName.classList.add('d-none');
            reqPhone.classList.add('d-none');
            btnSubmitText.innerText = 'Generate Bill';
        } else {
            btnCredit.classList.add('active');
            btnCredit.classList.remove('text-muted');
            btnCash.classList.remove('active');
            btnCash.classList.add('text-muted');

            paidLabel.innerText = 'Advance / Down Payment (Rs.)';
            paidHelp.innerText = 'e.g. Rs. 25,000 or Rs. 0';
            // Default advance: if user already entered an amount keep it, else default to empty for credit
            if (parseFloat(paidInput.value) >= currentNetPayable) {
                paidInput.value = '';
            }
            quickCash.classList.add('d-none');
            creditSection.classList.remove('d-none');
            reqName.classList.remove('d-none');
            reqPhone.classList.remove('d-none');
            btnSubmitText.innerText = 'Confirm Credit Sale & Bill';
        }
        calculateChange();
    }

    function onCustomerInput() {
        const nameInputEl = document.getElementById('modalCustomerName');
        const phoneInputEl = document.getElementById('modalCustomerPhone');
        const inputName = nameInputEl ? nameInputEl.value.trim() : '';
        const inputPhone = phoneInputEl ? phoneInputEl.value.trim() : '';
        const alertBox = document.getElementById('customerExistingAlert');
        const dueBadge = document.getElementById('custPrevDueBadge');
        const advBadge = document.getElementById('custAdvanceBadge');

        let matched = null;
        if (matchedCustomerId) {
            matched = existingCustomers.find(c => c.id == matchedCustomerId);
        }
        if (!matched && inputName && inputName.toLowerCase() !== 'walk-in customer') {
            matched = existingCustomers.find(c => c.name.toLowerCase() === inputName.toLowerCase());
        }
        if (!matched && inputPhone) {
            matched = existingCustomers.find(c => c.phone && c.phone.trim() === inputPhone);
        }

        if (matched) {
            matchedCustomerId = matched.id;
            matchedCustomerAdvance = parseFloat(matched.advance_balance) || 0;
            if (matched.phone && phoneInputEl && !phoneInputEl.value) {
                phoneInputEl.value = matched.phone;
            }
            if (cartCustomerSelect && cartCustomerSelect.value != matched.id) {
                cartCustomerSelect.value = matched.id;
            }
            if (displayCustomerPhone) {
                displayCustomerPhone.innerText = matched.phone || (phoneInputEl ? phoneInputEl.value : '') || '—';
            }
            if (cartCustomerName && !cartCustomerName.value) {
                cartCustomerName.value = matched.name;
            }
            if (cartCustomerPhone && !cartCustomerPhone.value && matched.phone) {
                cartCustomerPhone.value = matched.phone;
            }
            if (displayCustomerName) {
                displayCustomerName.innerText = matched.name;
            }
            if (alertBox) alertBox.classList.remove('d-none');

            if (advBadge) {
                if (matchedCustomerAdvance > 0) {
                    advBadge.innerText = 'Advance: Rs. ' + matchedCustomerAdvance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    advBadge.classList.remove('d-none');
                } else {
                    advBadge.classList.add('d-none');
                }
            }

            const prevDue = parseFloat(matched.total_due) || 0;
            if (dueBadge) {
                if (prevDue > 0) {
                    dueBadge.innerText = 'Prev Due: Rs. ' + prevDue.toLocaleString();
                    dueBadge.className = 'fw-bold text-danger';
                } else {
                    dueBadge.innerText = 'No Previous Due';
                    dueBadge.className = 'fw-bold text-success';
                }
            }

            if (cartCustomerAdvBadge) {
                if (matchedCustomerAdvance > 0) {
                    cartCustomerAdvBadge.innerText = 'Adv: Rs. ' + matchedCustomerAdvance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    cartCustomerAdvBadge.classList.remove('d-none');
                } else {
                    cartCustomerAdvBadge.classList.add('d-none');
                }
            }
            if (cartCustomerDueBadge) {
                if (prevDue > 0) {
                    cartCustomerDueBadge.innerText = 'Due: Rs. ' + prevDue.toLocaleString();
                    cartCustomerDueBadge.classList.remove('d-none');
                } else {
                    cartCustomerDueBadge.classList.add('d-none');
                }
            }
        } else {
            matchedCustomerId = null;
            matchedCustomerAdvance = 0;
            if (alertBox) alertBox.classList.add('d-none');
            if (advBadge) advBadge.classList.add('d-none');
            if (cartCustomerAdvBadge) cartCustomerAdvBadge.classList.add('d-none');
            if (cartCustomerDueBadge) cartCustomerDueBadge.classList.add('d-none');
        }

        updateCheckoutTotals();
    }

    function onOrderDateChange() {
        const dateInput = document.getElementById('modalOrderDate');
        const badge = document.getElementById('orderDateBadge');
        if (!dateInput || !badge) return;

        const selected = dateInput.value;
        const today = new Date().toISOString().split('T')[0];

        if (!selected || selected === today) {
            badge.innerText = 'Today';
            badge.className = 'badge bg-white text-muted border small';
        } else if (selected < today) {
            badge.innerText = 'Previous Date';
            badge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle small';
        } else {
            badge.innerText = 'Future Date';
            badge.className = 'badge bg-info-subtle text-info border border-info-subtle small';
        }
    }

    function prepareCheckoutModal() {
        document.getElementById('modalCustomerName').value     = cartCustomerName.value || 'Walk-in Customer';
        document.getElementById('modalCustomerPhone').value    = cartCustomerPhone.value || '';
        document.getElementById('checkoutError').innerText     = '';

        // Reset or initialize order date to today
        const orderDateEl = document.getElementById('modalOrderDate');
        if (orderDateEl && !orderDateEl.value) {
            orderDateEl.value = new Date().toISOString().split('T')[0];
        }
        onOrderDateChange();

        onCustomerInput();
        setSaleType('cash'); // default to cash
    }

    document.getElementById('btnOpenCheckoutModal').addEventListener('click', function () {
        if (cart.length === 0) return;
        prepareCheckoutModal();
        getCheckoutModal().show();
    });

    function setQuickCash(amount) {
        if (amount === 'exact') {
            document.getElementById('paidAmountInput').value = currentNetPayable.toFixed(2);
        } else {
            document.getElementById('paidAmountInput').value = amount;
        }
        calculateChange();
    }

    function calculateChange() {
        const paid  = parseFloat(document.getElementById('paidAmountInput').value) || 0;

        const changeAmountEl = document.getElementById('changeDueAmount');
        const changeLabelEl  = document.getElementById('changeDueLabel');
        const changeBox      = document.getElementById('changeDueBox');
        const creditSection  = document.getElementById('creditFieldsSection');

        if (paid >= currentNetPayable) {
            const change = paid - currentNetPayable;
            changeLabelEl.innerText = 'Change to Return:';
            changeLabelEl.className = 'small fw-semibold text-success';
            changeAmountEl.innerText = 'Rs. ' + change.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            changeAmountEl.className = 'fw-bold fs-6 text-success';
            changeBox.style.background = '#f4fbf7';
            changeBox.style.borderColor = '#c7ecd9';

            if (currentSaleType === 'cash') {
                creditSection.classList.add('d-none');
            }
        } else {
            const balance = currentNetPayable - paid;
            changeLabelEl.innerText = currentSaleType === 'credit' ? 'Remaining Balance (Udhaar):' : 'Remaining Balance:';
            changeLabelEl.className = 'small fw-semibold text-danger';
            changeAmountEl.innerText = 'Rs. ' + balance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            changeAmountEl.className = 'fw-bold fs-6 text-danger';
            changeBox.style.background = '#fdf4f4';
            changeBox.style.borderColor = '#f5c6cb';

            // If user enters less than net payable, automatically show credit details
            creditSection.classList.remove('d-none');
            document.getElementById('reqCustName').classList.remove('d-none');
            document.getElementById('reqCustPhone').classList.remove('d-none');
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

        let total = 0;
        cart.forEach(item => {
            const discountPercent = item.discount_percent || 0;
            const discountPerUnit = item.price * (discountPercent / 100);
            const discountedPrice = Math.max(0, item.price - discountPerUnit);
            total += (discountedPrice * item.quantity);
        });
        total = Math.max(0, total);

        const paidAmount = parseFloat(document.getElementById('paidAmountInput').value) || 0;

        const customerName = document.getElementById('modalCustomerName').value.trim();
        const customerPhone = document.getElementById('modalCustomerPhone').value.trim();
        const notes = document.getElementById('modalSaleNotes').value.trim();

        const isCreditSale = (currentSaleType === 'credit') || (paidAmount < currentNetPayable);

        // Validation for Credit / Installment Sales
        if (isCreditSale) {
            if (!customerName || customerName.toLowerCase() === 'walk-in customer') {
                errorBox.innerText = 'Customer Name is required for credit/installment sales to track who owes the balance.';
                document.getElementById('modalCustomerName').focus();
                return;
            }
            if (!customerPhone) {
                errorBox.innerText = 'Customer Phone Number is required for credit sales so you can contact them for installments.';
                document.getElementById('modalCustomerPhone').focus();
                return;
            }
        }

        const orderDate = document.getElementById('modalOrderDate') ? document.getElementById('modalOrderDate').value : null;

        const payload = {
            order_date: orderDate,
            customer_id: matchedCustomerId,
            customer_name: customerName || 'Walk-in Customer',
            customer_phone: customerPhone || null,
            paid_amount: paidAmount,
            due_date: null,
            notes: notes || null,
            payment_method: 'cash',
            items: cart.map(i => ({
                product_id: i.id,
                quantity: i.quantity,
                price: i.price,
                discount_percent: parseFloat(i.discount_percent) || 0
            })),
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

            // Reset cart and customer picker
            cart = [];
            const discInputEl = document.getElementById('discountInput');
            if (discInputEl) discInputEl.value = 0;
            if (cartCustomerSelect) cartCustomerSelect.value = '';
            if (customerInlineForm) customerInlineForm.classList.add('d-none');
            setCustomerFromModel(null);
            currentAdvanceApplied = 0;
            currentNetPayable = 0;

            if (data.customer && typeof existingCustomers !== 'undefined' && Array.isArray(existingCustomers)) {
                const cIdx = existingCustomers.findIndex(c => c.id == data.customer.id);
                if (cIdx !== -1) {
                    existingCustomers[cIdx].advance_balance = data.customer.advance_balance;
                    existingCustomers[cIdx].total_due = data.customer.total_due;
                    if (data.customer.phone) existingCustomers[cIdx].phone = data.customer.phone;
                    // Update hidden select option
                    if (cartCustomerSelect) {
                        const opt = cartCustomerSelect.querySelector(`option[value="${data.customer.id}"]`);
                        if (opt) {
                            opt.dataset.advance = data.customer.advance_balance;
                            opt.dataset.due = data.customer.total_due;
                            if (data.customer.phone) opt.dataset.phone = data.customer.phone;
                        }
                    }
                } else if (data.customer.name && data.customer.name.toLowerCase() !== 'walk-in customer') {
                    // Brand new customer — add to in-memory array so picker can find them
                    existingCustomers.push(data.customer);
                    // Add to hidden select for compatibility
                    if (cartCustomerSelect) {
                        const opt = document.createElement('option');
                        opt.value = data.customer.id;
                        opt.dataset.name = data.customer.name;
                        opt.dataset.phone = data.customer.phone || '';
                        opt.dataset.advance = data.customer.advance_balance || 0;
                        opt.dataset.due = data.customer.total_due || 0;
                        opt.textContent = data.customer.name;
                        cartCustomerSelect.appendChild(opt);
                    }
                }
            } else if (data.customer_id && typeof existingCustomers !== 'undefined' && Array.isArray(existingCustomers)) {
                const cIdx = existingCustomers.findIndex(c => c.id == data.customer_id);
                if (cIdx !== -1) {
                    existingCustomers[cIdx].advance_balance = data.customer_advance_balance;
                }
            }
            renderCart();

            // Hide modal
            const modalInstance = getCheckoutModal();
            if (modalInstance) {
                modalInstance.hide();
            }

            if (typeof toastr !== 'undefined') {
                if (data.is_credit) {
                    toastr.success(`Credit Sale created! Advance: Rs. ${paidAmount.toLocaleString()} | Remaining Due: Rs. ${data.due_amount.toLocaleString()}`, 'Credit Sale Recorded', { timeOut: 4000 });
                } else {
                    toastr.success('Sale completed successfully! Bill generated.', 'Success', { timeOut: 2000 });
                }
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
        })
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

    ['btnCloseModalX', 'btnCancelModal'].forEach(id => {
        document.getElementById(id).addEventListener('click', () => {
            getCheckoutModal().hide();
        });
    });

    // Initial render
    renderCart();
</script>
@endpush
