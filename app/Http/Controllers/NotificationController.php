<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with('customer')
            ->where('user_id', Auth::id())
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json([
            'unread_count' => Notification::where('user_id', Auth::id())->where('is_read', false)->count(),
            'last_id' => (int) Notification::where('user_id', Auth::id())->max('id'),
            'notifications' => $notifications->map(function ($n) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => $n->display_message,
                    'is_read' => (bool) $n->is_read,
                    'created_at' => $n->created_at->diffForHumans(),
                    'customer_id' => $n->customer_id,
                    'url' => $n->customer_id ? route('customers.show', $n->customer_id) : null,
                    'read_url' => route('notifications.read', $n),
                ];
            }),
        ]);
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
        Notification::where('user_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }
}
