<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'user_id', 'follow_up_date', 'status'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
