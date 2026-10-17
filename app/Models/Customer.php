<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone'];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function getTotalDueAttribute(): float
    {
        return (float) $this->sales()->sum(DB::raw('total - paid_amount'));
    }
}
