<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerRefusal extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['customer_id', 'country'];
}
