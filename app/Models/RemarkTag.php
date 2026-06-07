<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemarkTag extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['remark_id', 'tagged_user_id'];
}
