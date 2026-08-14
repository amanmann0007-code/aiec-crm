<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $visibleNotifications = $this->visibleNotifications();
        $notifications = Notification::with('customer')
            ->whereKey($visibleNotifications->pluck('id'))
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json([
            'unread_count' => (clone $visibleNotifications)->where('is_read', false)->count(),
            'last_id' => (int) (clone $visibleNotifications)->max('id'),
            'notifications' => $notifications->map(function ($n) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => $n->display_message,
                    'is_read' => (bool) $n->is_read,
                    'created_at' => $n->created_at->diffForHumans(),
                    'reminder_due' => $n->remind_at !== null,
                    'customer_id' => $n->customer_id,
                    'url' => $n->customer_id ? route('customers.show', $n->customer_id) : null,
                    'read_url' => route('notifications.read', $n),
                ];
            }),
        ]);
    }

    private function visibleNotifications()
    {
        return Notification::where('user_id', Auth::id())
            ->where(function ($query) {
                $query->whereNull('remind_at')
                    ->orWhere('remind_at', '<=', now());
            });
    }

    public function markRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }

    public function markAllRead()
    {
        $this->visibleNotifications()->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }
}
