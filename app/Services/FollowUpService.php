<?php

namespace App\Services;

use App\Models\FollowUp;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FollowUpService
{
    /**
     * One pending follow-up per customer: keep the date nearest to today in each bucket.
     */
    public static function dedupeForDisplay(Collection $items, string $bucket): Collection
    {
        return $items->groupBy('customer_id')->map(function (Collection $group) use ($bucket) {
            if ($bucket === 'overdue') {
                return $group->sortByDesc('follow_up_date')->first();
            }
            if ($bucket === 'upcoming') {
                return $group->sortBy('follow_up_date')->first();
            }

            return $group->sortBy('follow_up_date')->first();
        })->values()->sortBy('follow_up_date');
    }

    /**
     * Replace pending follow-ups for a customer with a single next date.
     */
    public static function setNextForCustomer(int $customerId, string $date, int $userId): void
    {
        FollowUp::where('customer_id', $customerId)
            ->where('status', 'pending')
            ->update(['status' => 'done']);

        FollowUp::create([
            'customer_id' => $customerId,
            'user_id' => $userId,
            'follow_up_date' => Carbon::parse($date)->startOfDay(),
            'status' => 'pending',
        ]);
    }
}
