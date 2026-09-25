@extends('layouts.app')
@section('title', 'Products')
@section('page-title', 'Products')

@push('styles')
<style>
    .kpi-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .kpi-icon {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
    .products-table-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    #productsTable th {
        font-weight: 600;
        font-size: 0.85rem;
        color: #556270;
        background: #fbfbfc;
        border-bottom: 2px solid #edf1f6;
    }
    #productsTable td {
        vertical-align: middle;
        font-size: 0.88rem;
    }
    .btn-theme-primary {
        background: #ff6e40;
        border-color: #ff6e40;
        color: #ffffff;
        font-weight: 500;
    }
    .btn-theme-primary:hover {
        background: #f25b2a;
        border-color: #f25b2a;
        color: #ffffff;
    }
</style>
@endpush

@section('content')
<!-- KPI Stat Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Total Products</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalProducts) }}</h4>
                </div>
                <div class="kpi-icon bg-primary-subtle text-primary" style="background: rgba(255,110,64,0.1); color: #ff6e40 !important;">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Low Stock Items</div>
                    <h4 class="fw-bold mt-1 mb-0 {{ $lowStockCount > 0 ? 'text-danger' : 'text-dark' }}">{{ number_format($lowStockCount) }}</h4>
                </div>
                <div class="kpi-icon bg-danger-subtle text-danger" style="background: rgba(234,68,68,0.1);">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Total Inventory Units</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalStockUnits) }}</h4>
                </div>
                <div class="kpi-icon bg-success-subtle text-success" style="background: rgba(5,177,113,0.1);">
                    <i class="bi bi-stack"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Categories</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalCategories) }}</h4>
                </div>
                <div class="kpi-icon bg-info-subtle text-info" style="background: rgba(37,194,227,0.1);">
                    <i class="bi bi-tags"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Products Table Card -->
<div class="card products-table-card">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-1">Products Catalog</h5>
            <div class="text-muted small">Manage items, inventory levels, barcodes, and pricing.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('categories.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-tags me-1"></i> Categories
            </a>
            <a href="{{ route('products.create') }}" class="btn btn-sm btn-theme-primary px-3">
                <i class="bi bi-plus-lg me-1"></i> Add New Product
            </a>
        </div>
    </div>

    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table align-middle w-100" id="productsTable">
                <thead>
                    <tr>
                        <th style="width: 60px;">Image</th>
                        <th>Product &amp; Code</th>
                        <th>Category</th>
                        <th>Purchase Price</th>
                        <th>Sale Price</th>
                        <th>Stock Level</th>
                        <th class="text-end" style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        const table = $('#productsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 20,
            ajax: {
                url: `{{ route('products.index') }}`,
            },
            columns: [
                { data: 'image', name: 'image', orderable: false, searchable: false },
                { data: 'name_with_code', name: 'name' },
                { data: 'category', name: 'category.name' },
                { data: 'purchase_price', name: 'purchase_price' },
                { data: 'sale_price', name: 'sale_price' },
                { data: 'stock', name: 'stock' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            order: [[1, 'asc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search products by name or code...",
                lengthMenu: "Show _MENU_ entries",
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>',
                    next: '<i class="bi bi-chevron-right"></i>'
                }
            }
        });

        // SweetAlert2 Delete Confirmation
        $(document).on('click', '.btn-delete-product', function () {
            const url = $(this).data('url');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Delete Product?',
                text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ea4444',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
</script>
@endpush