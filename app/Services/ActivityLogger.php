<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\User;

class ActivityLogger
{
    public static function log($userId, $actionType, $description, $customerId = null)
    {
        $log = ActivityLog::create([
            'user_id' => $userId,
            'action_type' => $actionType,
            'description' => $description,
            'customer_id' => $customerId,
        ]);

        if (!config('services.google_chat.webhook') || $actionType === 'REMARK') {
            return;
        }

        try {
            if ($customerId) {
                $log->setRelation('customer', Customer::find($customerId));
            }

            $actor = $userId ? optional(User::find($userId))->name : null;
            $prefix = $actor ? "{$actor}: " : '';

            GoogleChatNotifier::send($prefix . $log->display_description, 'activity_logs');
        } catch (\Throwable $e) {
            // Never block CRM flow if chat notification fails.
        }
    }

    public static function forCustomer($userId, $actionType, Customer $customer, string $verb): void
    {
        self::log($userId, $actionType, ucfirst($verb) . ' on ' . $customer->activitySummary(), $customer->id);
    }
}
