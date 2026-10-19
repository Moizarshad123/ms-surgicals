<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no',
        'customer_id',
        'user_id',
        'sub_total',
        'discount',
        'total',
        'paid_amount',
        'due_date',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'sub_total' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class)->orderBy('payment_date', 'asc');
    }

    public function getDueAmountAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->paid_amount);
    }

    public function getIsCreditAttribute(): bool
    {
        return (float) $this->paid_amount < (float) $this->total;
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class)->latest();
    }

    public function getTotalReturnedAttribute(): float
    {
        return (float) $this->returns()->sum('total_amount');
    }

    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function getReturnedQuantityAttribute(): int
    {
        return (int) $this->items->sum(function ($item) {
            return $item->relationLoaded('returnItems')
                ? $item->returnItems->sum('quantity')
                : $item->returned_quantity;
        });
    }

    public function getNetQuantityAttribute(): int
    {
        return max(0, $this->total_quantity - $this->returned_quantity);
    }
}
