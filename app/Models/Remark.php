<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Remark extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'user_id',
        'message',
        'status_update',
        'follow_up_date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function taggedUsers()
    {
        return $this->belongsToMany(User::class, 'remark_tags', 'remark_id', 'tagged_user_id');
    }
}
