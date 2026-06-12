<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerFee;
use App\Models\CustomerProcessStep;
use App\Models\FollowUp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $this->isSuperAdmin($user);
        $canViewAllStats = $this->canViewAllStats($user);
        $selectedUserId = $isSuperAdmin ? $request->query('user_id') : null;
        $selectedUser = $selectedUserId ? User::find($selectedUserId) : null;
        $selectedUserId = $selectedUser ? $selectedUser->id : null;
        $userFilterOptions = $isSuperAdmin
            ? User::where('status', 'active')->orderBy('role')->orderBy('name')->get(['id', 'name', 'role'])
            : collect();
        $customerQuery = $this->customerScopeQuery($user, $selectedUser);

        $range = $request->query('range');
        $from = $request->query('from');
        $to = $request->query('to');

        if (in_array($range, ['7', '14', '30'], true)) {
            $from = now()->subDays((int) $range - 1)->toDateString();
            $to = now()->toDateString();
        }

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->subDays(29)->startOfDay();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $today = now()->toDateString();
        $followUpQuery = $this->followUpScopeQuery($user, $selectedUser);

        $stats = [
            'customers' => (clone $customerQuery)->count(),
            'overdue_followups' => (clone $followUpQuery)
                ->whereDate('follow_up_date', '<', $today)
                ->distinct('customer_id')
                ->count('customer_id'),
            'today_followups' => (clone $followUpQuery)
                ->whereDate('follow_up_date', '=', $today)
                ->distinct('customer_id')
                ->count('customer_id'),
            'future_followups' => (clone $followUpQuery)
                ->whereDate('follow_up_date', '>', $today)
                ->distinct('customer_id')
                ->count('customer_id'),
            'users' => $isSuperAdmin && !$selectedUser ? User::count() : null,
        ];

        $visibleCustomerIds = (clone $customerQuery)->pluck('id');
        $feeStats = [
            'collected' => 0,
            'refunds' => 0,
            'net' => 0,
        ];

        if ($isSuperAdmin) {
            $feeQuery = CustomerFee::whereBetween('created_at', [$fromDate, $toDate])
                ->when($selectedUser, function ($query) use ($visibleCustomerIds) {
                    $query->whereIn('customer_id', $visibleCustomerIds);
                });

            $feeStats['collected'] = (float) (clone $feeQuery)
                ->whereRaw('LOWER(purpose) NOT LIKE ?', ['%refund%'])
                ->sum('amount');
            $feeStats['refunds'] = (float) (clone $feeQuery)
                ->whereRaw('LOWER(purpose) LIKE ?', ['%refund%'])
                ->sum('amount');
            $feeStats['net'] = $feeStats['collected'] - $feeStats['refunds'];
        }

        $quickReports = [
            'last_7' => $this->periodReport($user, 7, $selectedUser),
            'last_14' => $this->periodReport($user, 14, $selectedUser),
            'last_30' => $this->periodReport($user, 30, $selectedUser),
        ];

        $latestProcessSteps = CustomerProcessStep::query()
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$fromDate, $toDate])
            ->when(!$canViewAllStats || $selectedUser, function ($query) use ($visibleCustomerIds) {
                $query->whereIn('customer_id', $visibleCustomerIds);
            })
            ->orderBy('customer_id')
            ->orderByDesc('step_order')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get(['customer_id', 'step_label']);

        $processTimelineCounts = $latestProcessSteps
            ->unique('customer_id')
            ->countBy('step_label')
            ->sortDesc();

        $notStartedCount = $visibleCustomerIds->count() - $latestProcessSteps->unique('customer_id')->count();
        if ($notStartedCount > 0) {
            $processTimelineCounts->put('Not started', $notStartedCount);
            $processTimelineCounts = $processTimelineCounts->sortDesc();
        }

        $statusCounts = (clone $customerQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->pluck('total', 'status');

        $leadStatusCounts = collect(['will visit' => 0, 'interested' => 0])->merge((clone $customerQuery)
            ->where('source', 'Telecaller')
            ->whereIn('status', ['will visit', 'interested'])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status'));

        $sourceCounts = (clone $customerQuery)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw("COALESCE(NULLIF(source, ''), 'Unknown') as source_label, COUNT(*) as total")
            ->groupBy('source_label')
            ->orderByDesc('total')
            ->pluck('total', 'source_label');

        $enrollmentBaseQuery = $this->customerScopeQuery($user, $selectedUser)
            ->whereBetween('created_at', [$fromDate, $toDate]);

        $enrollmentTotal = (clone $enrollmentBaseQuery)->count();
        $enrollmentCount = (clone $enrollmentBaseQuery)
            ->where(function ($query) {
                $query->where('status', 'in process')
                    ->orWhereHas('processSteps', function ($processQuery) {
                        $processQuery->whereNotNull('completed_at')
                            ->where('step_key', '<>', 'dropout');
                    });
            })
            ->where('status', '<>', 'plan drop')
            ->where('status', '<>', 'not eligible')
            ->count();

        $enrollmentStats = [
            'label' => $user->role === 'telecaller' ? 'leads' : 'customers',
            'total' => $enrollmentTotal,
            'enrolled' => $enrollmentCount,
            'ratio' => $enrollmentTotal > 0 ? round(($enrollmentCount / $enrollmentTotal) * 100, 1) : 0,
        ];

        $recentCustomers = (clone $customerQuery)
            ->with([
                'counselor',
                'processSteps' => function ($processQuery) {
                    $processQuery->whereNotNull('completed_at')
                        ->orderByDesc('step_order')
                        ->orderByDesc('completed_at');
                },
            ])
            ->latest('id')
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'stats',
            'recentCustomers',
            'user',
            'statusCounts',
            'processTimelineCounts',
            'quickReports',
            'fromDate',
            'toDate',
            'isSuperAdmin',
            'selectedUser',
            'selectedUserId',
            'userFilterOptions',
            'feeStats',
            'leadStatusCounts',
            'sourceCounts',
            'enrollmentStats'
        ));
    }

    private function periodReport($user, int $days, ?User $selectedUser = null): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $customerQuery = $this->customerScopeQuery($user, $selectedUser)->whereBetween('created_at', [$from, $to]);
        $followUpQuery = $this->followUpScopeQuery($user, $selectedUser)->whereBetween('follow_up_date', [$from, $to]);
        $processQuery = CustomerProcessStep::whereNotNull('completed_at')->whereBetween('completed_at', [$from, $to]);

        if (!$this->canViewAllStats($user) || $selectedUser) {
            $visibleCustomerIds = $this->customerScopeQuery($user, $selectedUser)->pluck('id');
            $processQuery->whereIn('customer_id', $visibleCustomerIds);
        }

        return [
            'days' => $days,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'customers' => $customerQuery->count(),
            'followups' => $followUpQuery->count(),
            'process_steps' => $processQuery->count(),
        ];
    }

    private function customerScopeQuery($viewer, ?User $selectedUser = null)
    {
        $query = Customer::query();
        $scopeUser = $this->canViewAllStats($viewer) ? $selectedUser : $viewer;

        if ($scopeUser) {
            $this->applyUserCustomerScope($query, $scopeUser);
        }

        return $query;
    }

    private function followUpScopeQuery($viewer, ?User $selectedUser = null)
    {
        $query = FollowUp::where('status', 'pending')
            ->whereNotNull('follow_up_date')
            ->whereHas('customer', function ($customerQuery) {
                $customerQuery->whereNotIn('status', config('crm.statuses_no_follow_up', []));
            });

        $scopeUser = $this->canViewAllStats($viewer) ? $selectedUser : $viewer;
        if ($scopeUser) {
            $query->where(function ($followUpQuery) use ($scopeUser) {
                $followUpQuery->where('user_id', $scopeUser->id)
                    ->orWhereHas('customer', function ($customerQuery) use ($scopeUser) {
                        if ($scopeUser->role === 'counselor') {
                            $customerQuery->where('assigned_counselor_id', $scopeUser->id);
                        } elseif ($scopeUser->role === 'telecaller') {
                            $customerQuery->where('telecaller_id', $scopeUser->id);
                        } else {
                            $customerQuery->where('created_by', $scopeUser->id);
                        }
                    });
            });
        }

        return $query;
    }

    private function applyUserCustomerScope($query, User $scopeUser): void
    {
        if ($scopeUser->role === 'counselor') {
            $query->where('assigned_counselor_id', $scopeUser->id);
        } elseif ($scopeUser->role === 'telecaller') {
            $query->where('telecaller_id', $scopeUser->id);
        } else {
            $query->where('created_by', $scopeUser->id);
        }
    }

    private function isSuperAdmin($user): bool
    {
        return $user->role === 'admin';
    }

    private function canViewAllStats($user): bool
    {
        return in_array($user->role, ['admin', 'receptionist', 'director'], true);
    }
}
