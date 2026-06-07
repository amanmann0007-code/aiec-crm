<?php

namespace App\Jobs;

use App\Models\FollowUp;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFollowUpReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $followUps = FollowUp::with('customer')
            ->where('status', 'pending')
            ->whereNotNull('follow_up_date')
            ->where('follow_up_date', '<=', now())
            ->whereHas('customer', function ($customerQuery) {
                $customerQuery->whereNotIn('status', config('crm.statuses_no_follow_up', []));
            })
            ->get();

        foreach ($followUps as $followUp) {
            Notification::create([
                'user_id' => $followUp->user_id,
                'customer_id' => $followUp->customer_id,
                'title' => 'Follow-up reminder',
                'message' => 'Reminder: Follow-up today for ' . $followUp->customer->activitySummary(),
            ]);

        }
    }
}
