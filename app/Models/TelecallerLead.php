<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelecallerLead extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'country', 'visa_type', 'status', 'telecaller_id'];
}
