<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Services\FollowUpService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $base = FollowUp::query()
            ->with(['customer:id,pid,name,phone,status', 'customer.remarks'])
            ->where('status', 'pending');

        if (in_array($user->role, ['counselor', 'telecaller'], true)) {
            $base->where('user_id', $user->id);
        }

        $today = now()->toDateString();

        $format = function ($items) {
            return $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => optional($item->customer)->name,
                    'pid' => optional($item->customer)->pid,
                    'phone' => optional($item->customer)->phone,
                    'status' => optional($item->customer)->status,
                    'follow_up_date' => Carbon::parse($item->follow_up_date)->format('Y-m-d'),
                    'last_remark' => optional(optional($item->customer)->remarks->last())->message,
                ];
            });
        };

        $overdueRaw = (clone $base)->whereDate('follow_up_date', '<', $today)->orderBy('follow_up_date')->get();
        $todayRaw = (clone $base)->whereDate('follow_up_date', '=', $today)->orderBy('follow_up_date')->get();
        $upcomingRaw = (clone $base)->whereDate('follow_up_date', '>', $today)->orderBy('follow_up_date')->get();

        return response()->json([
            'overdue' => $format(FollowUpService::dedupeForDisplay($overdueRaw, 'overdue')),
            'today' => $format(FollowUpService::dedupeForDisplay($todayRaw, 'today')),
            'upcoming' => $format(FollowUpService::dedupeForDisplay($upcomingRaw, 'upcoming')),
        ]);
    }
}
