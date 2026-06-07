<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'dob',
        'gender',
        'marital_status',
        'father_spouse_name',
        'residence_country',
        'qualification',
        'qualification_year',
        'gap_years',
        'score',
        'visa_type',
        'country',
        'english_test',
        'test_type',
        'listening',
        'reading',
        'writing',
        'speaking',
        'overall',
        'test_expiry',
        'previous_refusal',
        'refusal_countries',
        'converted_customer_id',
        'converted_by',
        'converted_at',
    ];

    protected $casts = [
        'refusal_countries' => 'array',
        'converted_at' => 'datetime',
    ];

    public function convertedCustomer()
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    public function convertedBy()
    {
        return $this->belongsTo(User::class, 'converted_by');
    }
}
