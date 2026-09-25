@php
    // Simple, self-contained low-stock check shown on every page.
    $__lowStockProducts = \App\Models\Product::lowStock()->orderBy('stock')->get();
@endphp

@if ($__lowStockProducts->count())
    <div class="low-stock-banner d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>
            <strong>Low Stock Alert:</strong>
            {{ $__lowStockProducts->count() }} product(s) at or below alert quantity —
            <span>
                @foreach ($__lowStockProducts->take(5) as $p)
                    {{ $p->name }} ({{ $p->stock }}){{ !$loop->last ? ',' : '' }}
                @endforeach
                @if ($__lowStockProducts->count() > 5)
                    and {{ $__lowStockProducts->count() - 5 }} more...
                @endif
            </span>
            <a href="{{ route('products.index') }}" class="ms-1 fw-semibold">View Products &rarr;</a>
        </div>
    </div>
@endif
