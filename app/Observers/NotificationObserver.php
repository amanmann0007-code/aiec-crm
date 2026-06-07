<?php

namespace App\Observers;

use App\Models\Notification;
use App\Services\GoogleChatNotifier;

class NotificationObserver
{
    public function created(Notification $notification): void
    {
        $notification->loadMissing(['customer', 'user']);

        $recipient = optional($notification->user)->name ?: 'User #' . $notification->user_id;

        GoogleChatNotifier::send(
            "Bell notification for {$recipient}: {$notification->title} - {$notification->display_message}",
            $this->googleChatType($notification)
        );
    }

    private function googleChatType(Notification $notification): string
    {
        if (strcasecmp($notification->title, 'New case assigned') === 0) {
            return 'bell_new_case_assigned';
        }

        if (strcasecmp($notification->title, 'Customer updated') === 0) {
            return 'bell_customer_updated';
        }

        if (strcasecmp($notification->title, 'You were tagged in a remark') === 0) {
            return 'bell_remark_tagged';
        }

        if (stripos($notification->title, 'Follow-up') !== false) {
            return 'bell_follow_up_reminder';
        }

        return 'bell_other';
    }
}
