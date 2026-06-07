<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'amount',
        'purpose',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRefund(): bool
    {
        return stripos($this->purpose, 'refund') !== false;
    }

    public function signedAmount(): float
    {
        $amount = (float) $this->amount;

        return $this->isRefund() ? -$amount : $amount;
    }
}
