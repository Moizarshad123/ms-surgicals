<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'price',
        'discount_percent',
        'discount_amount',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function getDiscountPerUnitAttribute(): float
    {
        return $this->quantity > 0 ? round((float) $this->discount_amount / $this->quantity, 4) : 0.0;
    }

    public function getDiscountedPriceAttribute(): float
    {
        return max(0, round((float) $this->price - $this->discount_per_unit, 2));
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returnItems()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function getReturnedQuantityAttribute(): int
    {
        return (int) $this->returnItems()->sum('quantity');
    }

    public function getAvailableReturnQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity);
    }
}
