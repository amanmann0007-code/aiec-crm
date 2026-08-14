<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['user_id', 'customer_id', 'title', 'message', 'is_read', 'remind_at'];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'remind_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayMessageAttribute(): string
    {
        if (!$this->customer_id) {
            return $this->message;
        }

        $customer = $this->relationLoaded('customer') ? $this->customer : $this->customer()->first();
        if (!$customer) {
            return $this->message;
        }

        $summary = $customer->activitySummary();

        if (preg_match('/^(.+?) tagged you on /i', $this->message, $matches)) {
            return "{$matches[1]} tagged you on {$summary}";
        }

        if (stripos($this->message, 'assigned to') !== false) {
            if (preg_match('/assigned to (.+)$/i', $this->message, $matches)) {
                return "New customer {$summary} assigned to {$matches[1]}";
            }

            return "New customer {$summary} assigned";
        }

        if (stripos($this->title, 'Follow-up') !== false || stripos($this->message, 'Follow-up') !== false) {
            return "Reminder: Follow-up today for {$summary}";
        }

        return $this->message;
    }
}
