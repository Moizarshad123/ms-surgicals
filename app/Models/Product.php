<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'category_id', 'image',
        'purchase_price', 'sale_price', 'stock', 'alert_quantity',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // Scope: products whose stock is at/below their alert quantity
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'alert_quantity');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock <= $this->alert_quantity;
    }
}
