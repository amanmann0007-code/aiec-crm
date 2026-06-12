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
            $todayFollowUpPopupItems = collect();
            $todayVisitPopupItems = collect();
            $user = Auth::user();

            if ($user) {
                $query = Customer::where('status', 'assigned')
                    ->whereDoesntHave('remarks', function ($remarkQuery) {
                        $remarkQuery->whereNotNull('status_update')
                            ->where('status_update', '<>', '');
                    });

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

                if (in_array($user->role, ['counselor', 'telecaller'], true)) {
                    $todayFollowUpItems = (clone $followUpQuery)
                        ->with('customer')
                        ->whereDate('follow_up_date', '=', $today)
                        ->orderBy('follow_up_date')
                        ->get()
                        ->groupBy('customer_id')
                        ->map(fn ($group) => $group->first())
                        ->values()
                        ->take(20);

                    if ($user->role === 'counselor') {
                        $todayFollowUpPopupItems = $todayFollowUpItems;
                    }

                    $todayFollowUpItems->each(function ($followUp) use ($user, $today) {
                        if (!$followUp->customer) {
                            return;
                        }

                        $exists = Notification::where('user_id', $user->id)
                            ->where('customer_id', $followUp->customer_id)
                            ->where('title', 'Today follow-up due')
                            ->whereDate('created_at', $today)
                            ->exists();

                        if (!$exists) {
                            Notification::create([
                                'user_id' => $user->id,
                                'customer_id' => $followUp->customer_id,
                                'title' => 'Today follow-up due',
                                'message' => 'Today follow-up due for ' . $followUp->customer->activitySummary(),
                            ]);
                        }
                    });
                }

                if (Schema::hasColumn('customers', 'visit_date') && in_array($user->role, ['receptionist', 'telecaller'], true)) {
                    $visitQuery = Customer::with('telecaller')
                        ->where('source', 'Telecaller')
                        ->whereIn('status', config('crm.visiting_client_statuses', []))
                        ->whereDate('visit_date', $today);

                    if ($user->role === 'telecaller') {
                        $visitQuery->where('telecaller_id', $user->id);
                    }

                    $todayVisitPopupItems = $visitQuery
                        ->orderBy('name')
                        ->limit(20)
                        ->get();

                    $todayVisitPopupItems->each(function ($customer) use ($user, $today) {
                        $exists = Notification::where('user_id', $user->id)
                            ->where('customer_id', $customer->id)
                            ->where('title', 'Client visiting today')
                            ->whereDate('created_at', $today)
                            ->exists();

                        if (!$exists) {
                            Notification::create([
                                'user_id' => $user->id,
                                'customer_id' => $customer->id,
                                'title' => 'Client visiting today',
                                'message' => 'Client visiting today: ' . $customer->activitySummary(),
                            ]);
                        }
                    });
                }
            }

            $view->with([
                'newCasesCount' => $newCasesCount,
                'visitingClientsCount' => $visitingClientsCount,
                'tabEntriesCount' => $tabEntriesCount,
                'overdueFollowUpsCount' => $overdueFollowUpsCount,
                'todayFollowUpsCount' => $todayFollowUpsCount,
                'todayFollowUpPopupItems' => $todayFollowUpPopupItems,
                'todayVisitPopupItems' => $todayVisitPopupItems,
            ]);
        });
    }
}
