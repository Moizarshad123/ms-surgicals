<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'transaction_date',
        'transaction_time',
        'type',
        'amount',
        'person_name',
        'reference_no',
        'category',
        'description',
        'user_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->type === 'credit';
    }

    public function getIsDebitAttribute(): bool
    {
        return $this->type === 'debit';
    }
}

