<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Services\FollowUpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebFollowUpController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $noFollowUpStatuses = config('crm.statuses_no_follow_up', []);
        $base = FollowUp::with([
            'customer.processSteps' => function ($processQuery) {
                $processQuery->whereNotNull('completed_at')
                    ->orderByDesc('step_order')
                    ->orderByDesc('completed_at');
            },
        ])
            ->where('status', 'pending')
            ->whereNotNull('follow_up_date')
            ->whereHas('customer', function ($customerQuery) use ($noFollowUpStatuses) {
                $customerQuery->whereNotIn('status', $noFollowUpStatuses);
            });

        if (in_array($user->role, ['counselor', 'telecaller', 'agent'], true)) {
            $base->where(function ($query) use ($user) {
                if ($user->role === 'agent') {
                    $query->whereHas('customer', function ($customerQuery) use ($user) {
                        $customerQuery->where(function ($agentQuery) use ($user) {
                            $agentQuery->where('agent_id', $user->id)
                                ->orWhereHas('collaborators', function ($collaborationQuery) use ($user) {
                                    $collaborationQuery->where('users.id', $user->id);
                                });
                        });
                    });

                    return;
                }

                $query->where('user_id', $user->id)
                    ->orWhereHas('customer', function ($customerQuery) use ($user) {
                        if ($user->role === 'counselor') {
                            $customerQuery->where('assigned_counselor_id', $user->id);
                        } elseif ($user->role === 'telecaller') {
                            $customerQuery->where('telecaller_id', $user->id);
                        }
                    });
            });
        }

        $today = now()->toDateString();

        $map = function ($items) {
            return $items->map(function ($item) {
                return [
                    'follow_up' => $item,
                    'customer' => $item->customer,
                    'last_remark' => optional($item->customer->remarks()->latest('id')->first())->message,
                ];
            });
        };

        $overdueRaw = (clone $base)->whereDate('follow_up_date', '<', $today)->orderBy('follow_up_date')->get();
        $todayRaw = (clone $base)->whereDate('follow_up_date', '=', $today)->orderBy('follow_up_date')->get();
        $upcomingRaw = (clone $base)->whereDate('follow_up_date', '>', $today)->orderBy('follow_up_date')->get();

        $overdue = $map(FollowUpService::dedupeForDisplay($overdueRaw, 'overdue'));
        $todayList = $map(FollowUpService::dedupeForDisplay($todayRaw, 'today'));
        $upcoming = $map(FollowUpService::dedupeForDisplay($upcomingRaw, 'upcoming'));

        return view('follow-ups.index', compact('overdue', 'todayList', 'upcoming'));
    }
}
