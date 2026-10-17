<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = ['reference_no', 'supplier_name', 'total', 'purchase_date'];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class)->latest();
    }

    public function getTotalReturnedAttribute(): float
    {
        return (float) $this->returns()->sum('total_amount');
    }
}
