@extends('layouts.app')
@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@push('styles')
<style>
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

    .is-invalid-field {
        border-color: #dc3545 !important;
    }
    .field-error {
        color: #dc3545;
        font-size: 0.82rem;
        margin-top: 4px;
    }
    .required-star {
        color: #dc3545;
        margin-left: 2px;
    }

    /* Modern Dropzone & Image Box */
    .image-dropzone {
        border: 2px dashed #d8e0e9;
        border-radius: 12px;
        background: #fafbfc;
        padding: 20px 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }
    .image-dropzone:hover, .image-dropzone.dragover {
        border-color: #ff6e40;
        background: rgba(255, 110, 64, 0.03);
    }
    .dropzone-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: rgba(255, 110, 64, 0.1);
        color: #ff6e40;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin: 0 auto 10px;
    }
    #imagePreviewBox {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #eef1f6;
        background: #ffffff;
        position: relative;
    }
    #imagePreviewBox img {
        width: 100%;
        max-height: 250px;
        object-fit: contain;
        display: block;
        background: #fbfbfc;
        padding: 8px;
    }
    .preview-actions {
        position: absolute;
        top: 8px;
        right: 8px;
        z-index: 5;
    }

    /* Profit margin badge box */
    .profit-pill {
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e9edf2;
        padding: 12px 14px;
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
                <h5 class="fw-bold text-dark mb-0">Edit Product: {{ $product->name }}</h5>
                <div class="text-muted small">Update specifications, adjust pricing, stock quantities, or change photo.</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back to Catalog
            </a>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" id="productForm" novalidate>
    @csrf
    @method('PUT')
    <div class="row g-4">
        {{-- LEFT SIDE: Form Fields --}}
        <div class="col-lg-8">
            {{-- Section 1: Basic Info --}}
            <div class="card form-card mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="section-title mb-0">
                        <i class="bi bi-info-circle"></i> Basic Information
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Product Name<span class="required-star">*</span></label>
                            <input type="text" name="name" maxlength="255"
                                   class="form-control @error('name') is-invalid-field @enderror"
                                   value="{{ old('name', $product->name) }}" required autofocus>
                            @error('name')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Code / Barcode / SKU <span class="text-muted fw-normal small">(Optional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-upc-scan"></i></span>
                                <input type="text" name="code" maxlength="100"
                                       class="form-control @error('code') is-invalid-field @enderror"
                                       placeholder="e.g. SF-14001 (optional)"
                                       value="{{ old('code', $product->code) }}">
                            </div>
                            @error('code')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold text-dark">Category<span class="required-star">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-tags"></i></span>
                                <select name="category_id" class="form-select @error('category_id') is-invalid-field @enderror" required>
                                    <option value="">-- Choose Category --</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('category_id')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Pricing & Profit Margin --}}
            <div class="card form-card mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="section-title mb-0">
                        <i class="bi bi-currency-dollar"></i> Pricing &amp; Margin
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Purchase Cost (Cost Price)<span class="required-star">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">Rs.</span>
                                <input type="number" step="0.01" min="1" max="99999" name="purchase_price" id="purchasePriceInput"
                                       class="form-control no-negative max-5-digits @error('purchase_price') is-invalid-field @enderror"
                                       value="{{ old('purchase_price', $product->purchase_price) }}" required>
                            </div>
                            @error('purchase_price')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Retail Sale Price<span class="required-star">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">Rs.</span>
                                <input type="number" step="0.01" min="1" max="99999" name="sale_price" id="salePriceInput"
                                       class="form-control no-negative max-5-digits @error('sale_price') is-invalid-field @enderror"
                                       value="{{ old('sale_price', $product->sale_price) }}" required>
                            </div>
                            @error('sale_price')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Profit Margin Calculator Box --}}
                        <div class="col-12">
                            <div class="profit-pill d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-graph-up-arrow text-primary fs-5"></i>
                                    <div>
                                        <div class="small fw-semibold text-dark">Estimated Profit Margin</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Calculated based on sale vs purchase price.</div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span id="profitAmount" class="fw-bold text-success fs-6">Rs. 0.00</span>
                                    <span id="marginPercent" class="badge bg-success-subtle text-success ms-1">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 3: Inventory Stock --}}
            <div class="card form-card mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="section-title mb-0">
                        <i class="bi bi-stack"></i> Stock &amp; Inventory Alerts
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Current Stock (Units)<span class="required-star">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-boxes"></i></span>
                                <input type="number" step="1" min="1" max="99999" name="stock"
                                       class="form-control no-negative max-5-digits @error('stock') is-invalid-field @enderror"
                                       value="{{ old('stock', $product->stock) }}" required>
                            </div>
                            @error('stock')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Low Stock Alert Quantity<span class="required-star">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-bell"></i></span>
                                <input type="number" step="1" min="0" name="alert_quantity"
                                       class="form-control no-negative @error('alert_quantity') is-invalid-field @enderror"
                                       value="{{ old('alert_quantity', $product->alert_quantity) }}" required>
                            </div>
                            <div class="text-muted small mt-1">Alerts on POS and catalog when stock falls below this quantity.</div>
                            @error('alert_quantity')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Submit & Cancel Actions --}}
            <div class="d-flex align-items-center gap-2 mb-4">
                <button type="submit" class="btn btn-theme-primary px-4 py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-check2-circle fs-5"></i> Update Product
                </button>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary px-4 py-2">
                    Cancel
                </a>
            </div>
        </div>

        {{-- RIGHT SIDE: Product Image Upload Dropzone --}}
        <div class="col-lg-4">
            <div class="card form-card sticky-top" style="top: 20px;">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="section-title mb-0">
                        <i class="bi bi-image"></i> Product Image
                    </div>
                </div>
                <div class="card-body p-3">
                    <!-- Hidden actual file input -->
                    <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/jpg,image/webp,image/gif"
                           class="d-none @error('image') is-invalid-field @enderror">

                    <!-- Preview Container -->
                    <div id="imagePreviewBox" class="mb-3" style="{{ $product->image ? 'display:block;' : 'display:none;' }}">
                        <div class="preview-actions">
                            <button type="button" class="btn btn-sm btn-outline-danger bg-white shadow-sm py-1 px-2" id="clearImageBtn" title="Clear or remove image">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        </div>
                        <img id="previewImg" src="{{ $product->image ? asset('storage/' . $product->image) : '' }}" alt="Product Photo">
                        <div class="p-2 border-top bg-light text-center small text-muted text-truncate" id="fileNamePreview">
                            {{ $product->image ? 'Current uploaded image' : '' }}
                        </div>
                    </div>

                    <!-- Hidden checkbox to signal image removal on submit -->
                    <input type="hidden" name="remove_image" id="removeImageInput" value="0">

                    <!-- Interactive Drag-and-drop / Click Dropzone -->
                    <div class="image-dropzone" id="dropzoneArea" onclick="document.getElementById('imageInput').click();" style="{{ $product->image ? 'display:none;' : 'display:block;' }}">
                        <div class="dropzone-icon">
                            <i class="bi bi-cloud-arrow-up"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Change / Upload Photo</h6>
                        <p class="text-muted small mb-2">Click to browse or drag &amp; drop an image here</p>
                        <span class="badge bg-light text-secondary border">JPG, PNG, WEBP up to 5MB</span>
                    </div>

                    @if($product->image)
                        <div id="changePhotoBtnWrap" class="text-center mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('imageInput').click();">
                                <i class="bi bi-camera me-1"></i> Change Photo
                            </button>
                        </div>
                    @endif

                    @error('image')
                        <div class="field-error text-center mt-2">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    // -------------------------------------------------------------
    // Image Dropzone & Preview Handling
    // -------------------------------------------------------------
    const imageInput = document.getElementById('imageInput');
    const dropzoneArea = document.getElementById('dropzoneArea');
    const previewBox = document.getElementById('imagePreviewBox');
    const previewImg = document.getElementById('previewImg');
    const fileNamePreview = document.getElementById('fileNamePreview');
    const clearImageBtn = document.getElementById('clearImageBtn');
    const removeImageInput = document.getElementById('removeImageInput');
    const changePhotoBtnWrap = document.getElementById('changePhotoBtnWrap');

    function handleFile(file) {
        if (!file) return;
        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file (JPG, PNG, WEBP).');
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            fileNamePreview.textContent = `${file.name} (${(file.size / 1024).toFixed(1)} KB) - New`;
            previewBox.style.display = 'block';
            dropzoneArea.style.display = 'none';
            if (changePhotoBtnWrap) changePhotoBtnWrap.style.display = 'none';
            removeImageInput.value = '0';
        };
        reader.readAsDataURL(file);
    }

    imageInput.addEventListener('change', function (e) {
        handleFile(e.target.files[0]);
    });

    // Clear / Remove Image Button
    clearImageBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        imageInput.value = '';
        previewImg.src = '';
        fileNamePreview.textContent = '';
        previewBox.style.display = 'none';
        dropzoneArea.style.display = 'block';
        if (changePhotoBtnWrap) changePhotoBtnWrap.style.display = 'none';
        removeImageInput.value = '1'; // Flag to remove current image on server
    });

    // Drag and Drop Events
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzoneArea.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzoneArea.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzoneArea.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzoneArea.classList.remove('dragover');
        });
    });

    dropzoneArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            imageInput.files = files;
            handleFile(files[0]);
        }
    });

    // -------------------------------------------------------------
    // Profit Margin Calculator
    // -------------------------------------------------------------
    const purchaseInput = document.getElementById('purchasePriceInput');
    const saleInput = document.getElementById('salePriceInput');
    const profitAmountEl = document.getElementById('profitAmount');
    const marginPercentEl = document.getElementById('marginPercent');

    function updateMargin() {
        const purchase = parseFloat(purchaseInput.value) || 0;
        const sale = parseFloat(saleInput.value) || 0;
        const profit = sale - purchase;
        const margin = sale > 0 ? ((profit / sale) * 100).toFixed(1) : 0;

        profitAmountEl.textContent = 'Rs. ' + profit.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        marginPercentEl.textContent = margin + '%';

        if (profit < 0) {
            profitAmountEl.className = 'fw-bold text-danger fs-6';
            marginPercentEl.className = 'badge bg-danger-subtle text-danger ms-1';
        } else {
            profitAmountEl.className = 'fw-bold text-success fs-6';
            marginPercentEl.className = 'badge bg-success-subtle text-success ms-1';
        }
    }

    purchaseInput.addEventListener('input', updateMargin);
    saleInput.addEventListener('input', updateMargin);
    updateMargin();

    // -------------------------------------------------------------
    // Number Input Guards
    // -------------------------------------------------------------
    document.querySelectorAll('.no-negative').forEach(function (input) {
        input.addEventListener('input', function () {
            if (this.value !== '' && parseFloat(this.value) < 0) {
                this.value = 0;
            }
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === '-' || e.key === 'e' || e.key === 'E') {
                e.preventDefault();
            }
        });
        input.addEventListener('wheel', function () {
            this.blur();
        });
    });

    document.querySelectorAll('.max-5-digits').forEach(function (input) {
        input.addEventListener('input', function () {
            if (this.value === '') return;

            const isNegative = this.value.startsWith('-');
            const cleanValue = isNegative ? this.value.slice(1) : this.value;
            const parts = cleanValue.split('.');
            const integerPart = parts[0];

            if (integerPart.length > 5) {
                parts[0] = integerPart.slice(0, 5);
                this.value = (isNegative ? '-' : '') + parts.join('.');
            }

            if (parseFloat(this.value) > 99999) {
                this.value = 99999;
            }
        });
    });

    // -------------------------------------------------------------
    // Client-side Validation
    // -------------------------------------------------------------
    document.getElementById('productForm').addEventListener('submit', function (e) {
        let valid = true;
        this.querySelectorAll('[required]').forEach(function (field) {
            const existing = field.parentElement.querySelector('.client-error');
            if (existing) existing.remove();
            field.classList.remove('is-invalid-field');

            if (!field.value.trim()) {
                valid = false;
                field.classList.add('is-invalid-field');
                const msg = document.createElement('div');
                msg.className = 'field-error client-error';
                msg.textContent = 'This field is required.';
                field.parentElement.appendChild(msg);
            }
        });
        if (!valid) e.preventDefault();
    });
</script>
@endpush