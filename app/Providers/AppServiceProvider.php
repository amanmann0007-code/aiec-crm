<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\CustomerEntry;
use App\Models\FollowUp;
use App\Models\Notification;
use App\Observers\NotificationObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // MySQL utf8mb4: indexed VARCHAR(255) exceeds 1000-byte key limit
        Schema::defaultStringLength(191);
        Notification::observe(NotificationObserver::class);

        View::composer('layouts.app', function ($view) {
            $newCasesCount = 0;
            $visitingClientsCount = 0;
            $tabEntriesCount = 0;
            $overdueFollowUpsCount = 0;
            $todayFollowUpsCount = 0;
            $user = Auth::user();

            if ($user) {
                $query = Customer::where('status', 'assigned');

                if ($user->role === 'counselor') {
                    $query->where('assigned_counselor_id', $user->id);
                } elseif ($user->role === 'telecaller') {
                    $query->where('telecaller_id', $user->id);
                }

                $newCasesCount = $query->count();

                if (in_array($user->role, ['receptionist', 'telecaller'], true)) {
                    $visitingQuery = Customer::where('source', 'Telecaller')
                        ->whereIn('status', config('crm.visiting_client_statuses', []));

                    if ($user->role === 'telecaller') {
                        $visitingQuery->where('telecaller_id', $user->id);
                    }

                    $visitingClientsCount = $visitingQuery->count();
                }

                if ($user->role === 'receptionist') {
                    $tabEntriesCount = CustomerEntry::whereNull('converted_at')->count();
                }

                $followUpQuery = FollowUp::where('status', 'pending')
                    ->whereNotNull('follow_up_date')
                    ->whereHas('customer', function ($customerQuery) {
                        $customerQuery->whereNotIn('status', config('crm.statuses_no_follow_up', []));
                    });

                if (in_array($user->role, ['counselor', 'telecaller'], true)) {
                    $followUpQuery->where(function ($query) use ($user) {
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
                $overdueFollowUpsCount = (clone $followUpQuery)
                    ->whereDate('follow_up_date', '<', $today)
                    ->distinct('customer_id')
                    ->count('customer_id');
                $todayFollowUpsCount = (clone $followUpQuery)
                    ->whereDate('follow_up_date', '=', $today)
                    ->distinct('customer_id')
                    ->count('customer_id');
            }

            $view->with([
                'newCasesCount' => $newCasesCount,
                'visitingClientsCount' => $visitingClientsCount,
                'tabEntriesCount' => $tabEntriesCount,
                'overdueFollowUpsCount' => $overdueFollowUpsCount,
                'todayFollowUpsCount' => $todayFollowUpsCount,
            ]);
        });
    }
}
