<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'account_title',
        'account_number',
        'branch',
        'opening_balance',
        'status',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
    ];

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class)->orderBy('transaction_date', 'desc')->orderBy('transaction_time', 'desc');
    }

    public function getCurrentBalanceAttribute(): float
    {
        $credits = (float) $this->transactions()->where('type', 'credit')->sum('amount');
        $debits = (float) $this->transactions()->where('type', 'debit')->sum('amount');

        return (float) $this->opening_balance + $credits - $debits;
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->transactions()->where('type', 'credit')->sum('amount');
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->transactions()->where('type', 'debit')->sum('amount');
    }
}

