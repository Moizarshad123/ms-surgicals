@extends('layouts.app')
@section('title', 'Categories')
@section('page-title', 'Categories')

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
    .category-card {
        border-radius: 12px;
        border: 1px solid #eef1f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
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
    .search-group {
        position: relative;
    }
    .search-group .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #8c98a4;
        font-size: 0.95rem;
        pointer-events: none;
    }
    .search-group input {
        padding-left: 36px;
        border-radius: 8px;
        border: 1px solid #dfe5ec;
        font-size: 0.88rem;
    }
    .search-group input:focus {
        border-color: #ff6e40;
        box-shadow: 0 0 0 3px rgba(255, 110, 64, 0.15);
    }
    #categoriesTable th {
        font-weight: 600;
        font-size: 0.85rem;
        color: #556270;
        background: #fbfbfc;
        border-bottom: 2px solid #edf1f6;
    }
    #categoriesTable td {
        vertical-align: middle;
        font-size: 0.88rem;
    }
    .category-icon-avatar {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: rgba(255, 110, 64, 0.1);
        color: #ff6e40;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }
</style>
@endpush

@section('content')
<!-- KPI Stat Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Total Categories</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalCategories) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(255,110,64,0.1); color: #ff6e40;">
                    <i class="bi bi-tags"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-md-4">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-muted small fw-semibold">Total Products in System</div>
                    <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalProducts) }}</h4>
                </div>
                <div class="kpi-icon" style="background: rgba(5,177,113,0.1); color: #05b171;">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- LEFT: Add Category Form -->
    <div class="col-lg-4 col-md-5">
        <div class="card category-card">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle text-primary"></i> Add New Category
                </h6>
                <div class="text-muted small">Organize products into identifiable groups.</div>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('categories.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                               placeholder="e.g. Surgical Scissors, Forceps..." value="{{ old('name') }}" required autofocus>
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-theme-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-check2-circle fs-5"></i> Save Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- RIGHT: Categories List with Search & Actions -->
    <div class="col-lg-8 col-md-7">
        <div class="card category-card">
            <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-folder2-open text-primary"></i> Categories List
                    </h6>
                    <div class="text-muted small">
                        Showing <span id="visibleCategoryCount">{{ $categories->count() }}</span> categories
                    </div>
                </div>

                <!-- Live Search Box -->
                <div class="search-group" style="width: 240px;">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="categorySearchInput" class="form-control form-control-sm" placeholder="Search categories...">
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="categoriesTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Category Name</th>
                                <th>Total Products</th>
                                <th class="text-end" style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categoryTableBody">
                            @forelse ($categories as $index => $cat)
                                <tr class="category-row" data-name="{{ strtolower($cat->name) }}">
                                    <td class="text-muted small">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="category-icon-avatar">
                                                <i class="bi bi-tag"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark category-name-text">{{ $cat->name }}</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">Added {{ $cat->created_at ? $cat->created_at->format('d M Y') : '—' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border px-2 py-1">
                                            <i class="bi bi-box me-1"></i> {{ $cat->products_count }} {{ Str::plural('item', $cat->products_count) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-category"
                                                    data-id="{{ $cat->id }}"
                                                    data-name="{{ $cat->name }}"
                                                    data-url="{{ route('categories.update', $cat) }}"
                                                    title="Edit Category">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-category"
                                                    data-url="{{ route('categories.destroy', $cat) }}"
                                                    data-name="{{ $cat->name }}"
                                                    data-count="{{ $cat->products_count }}"
                                                    title="Delete Category">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr id="noCategoriesRow">
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                                        No categories added yet. Create your first category using the form on the left.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- No Search Results Found Row -->
                <div id="noSearchMatch" class="py-4 text-center text-muted d-none">
                    <i class="bi bi-search fs-3 d-block mb-2 text-secondary"></i>
                    <p class="mb-0 small">No categories found matching your search.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- EDIT CATEGORY MODAL -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-bottom py-3 px-3" style="background:#fafbfc; border-radius: 12px 12px 0 0;">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="editCategoryModalLabel">
                    <i class="bi bi-pencil-square text-primary"></i> Edit Category
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCategoryForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Category Name <span class="text-danger">*</span></label>
                        <input type="text" id="editCategoryName" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3" style="background:#fafbfc; border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-theme-primary px-3">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        // -------------------------------------------------------------
        // Live Search Filter for Categories
        // -------------------------------------------------------------
        $('#categorySearchInput').on('input', function () {
            const query = $(this).val().toLowerCase().trim();
            let visibleCount = 0;

            $('.category-row').each(function () {
                const name = $(this).data('name');
                if (!query || name.includes(query)) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });

            $('#visibleCategoryCount').text(visibleCount);
            if (visibleCount === 0 && $('.category-row').length > 0) {
                $('#noSearchMatch').removeClass('d-none');
            } else {
                $('#noSearchMatch').addClass('d-none');
            }
        });

        // -------------------------------------------------------------
        // Open Edit Category Modal
        // -------------------------------------------------------------
        $('.btn-edit-category').on('click', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const url = $(this).data('url');

            $('#editCategoryForm').attr('action', url);
            $('#editCategoryName').val(name);

            const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            modal.show();
        });

        // -------------------------------------------------------------
        // Delete Category Confirmation with SweetAlert2
        // -------------------------------------------------------------
        $('.btn-delete-category').on('click', function () {
            const url = $(this).data('url');
            const name = $(this).data('name');
            const productCount = parseInt($(this).data('count'), 10) || 0;

            if (productCount > 0) {
                Swal.fire({
                    title: 'Cannot Delete Category',
                    text: `Category "${name}" contains ${productCount} products. Please reassign or delete the products first.`,
                    icon: 'warning',
                    confirmButtonColor: '#ff6e40'
                });
                return;
            }

            Swal.fire({
                title: 'Delete Category?',
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
