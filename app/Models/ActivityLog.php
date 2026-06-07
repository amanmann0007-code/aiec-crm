<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['user_id', 'action_type', 'description', 'customer_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getDisplayDescriptionAttribute(): string
    {
        if (!$this->customer_id) {
            return $this->description;
        }

        $customer = $this->relationLoaded('customer') ? $this->customer : $this->customer()->first();
        if (!$customer) {
            return $this->description;
        }

        $summary = $customer->activitySummary();

        switch ($this->action_type) {
            case 'REMARK':
                return "Added remark on {$summary}";
            case 'ADD_CUSTOMER':
                return "Added customer {$summary}";
            case 'ADD_LEAD':
                return "Added lead {$summary}";
            case 'UPLOAD_DOCUMENT':
                if (preg_match('/^Uploaded (.+) on /', $this->description, $matches)) {
                    return "Uploaded {$matches[1]} on {$summary}";
                }
                return "Uploaded document on {$summary}";
            case 'DELETE_DOCUMENT':
                if (preg_match('/^Deleted (.+)$/', $this->description, $matches)) {
                    return "Deleted {$matches[1]} from {$summary}";
                }
                return "Deleted document from {$summary}";
            default:
                return $this->description;
        }
    }
}
