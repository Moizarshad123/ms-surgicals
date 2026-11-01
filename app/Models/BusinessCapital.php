<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCapital extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'capital_date',
        'type',
        'source',
        'description',
        'user_id',
    ];

    protected $casts = [
        'capital_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
