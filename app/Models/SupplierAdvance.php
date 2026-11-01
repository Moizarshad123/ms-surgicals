<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_name',
        'advance_balance',
    ];

    protected $casts = [
        'advance_balance' => 'decimal:2',
    ];
}
