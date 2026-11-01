<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_name',
        'order_date',
        'status',
        'total_amount',
        'user_id',
    ];

    protected $casts = [
        'order_date'   => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate the next sequential PO number.
     */
    public static function generatePoNumber(): string
    {
        $last = static::lockForUpdate()->latest('id')->value('po_number');
        if ($last && preg_match('/PO-(\d+)$/', $last, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1;
        }
        return 'PO-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}

