<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'supplier_name',
        'total',
        'paid_amount',
        'due_date',
        'payment_status',
        'notes',
        'purchase_date',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class)->latest();
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class)->orderBy('payment_date', 'asc');
    }

    public function getDueAmountAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->paid_amount);
    }

    public function getIsCreditAttribute(): bool
    {
        return (float) $this->paid_amount < (float) $this->total;
    }

    public function getTotalReturnedAttribute(): float
    {
        return (float) $this->returns()->sum('total_amount');
    }
}
