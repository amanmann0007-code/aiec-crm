<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\StoreTelecallerCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Mail\WelcomeMail;
use App\Models\Customer;
use App\Models\CustomerEntry;
use App\Models\CustomerProcessStep;
use App\Models\CustomerRefusal;
use App\Models\Notification;
use App\Models\Qualification;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\PidGenerator;
use App\Services\ProcessTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class WebCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->visibleCustomerQuery();

        $statusFilter = $request->query('status');
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $processStageFilter = trim((string) $request->query('process_stage', ''));
        if ($processStageFilter !== '') {
            $this->applyProcessStageFilter($query, $processStageFilter);
        }

        [$sortColumn, $sortDirection] = $this->customerSort($request);
        $this->applyCustomerSort($query, $sortColumn, $sortDirection);

        $customers = $query->paginate(15)->appends($request->query());
        $statusOptions = config('crm.customer_statuses', []);
        $processStageOptions = collect();

        return view('customers.index', [
            'customers' => $customers,
            'statusFilter' => $statusFilter,
            'statusOptions' => $statusOptions,
            'processStageFilter' => $processStageFilter,
            'processStageOptions' => $processStageOptions,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'pageTitle' => Auth::user()->role === 'telecaller'
                ? 'My Leads'
                : (Auth::user()->role === 'agent' ? 'My Cases' : 'Customers'),
            'showStatusFilter' => true,
            'showProcessFilter' => false,
            'emptyMessage' => 'No customers found.',
        ]);
    }

    public function newCases(Request $request)
    {
        $query = $this->visibleCustomerQuery()
            ->where('status', 'assigned')
            ->whereDoesntHave('remarks', function ($remarkQuery) {
                $remarkQuery->whereNotNull('status_update')
                    ->where('status_update', '<>', '');
            });

        [$sortColumn, $sortDirection] = $this->customerSort($request);
        $this->applyCustomerSort($query, $sortColumn, $sortDirection);

        $customers = $query->paginate(15)->appends($request->query());

        return view('customers.index', [
            'customers' => $customers,
            'statusFilter' => 'assigned',
            'statusOptions' => config('crm.customer_statuses', []),
            'processStageFilter' => '',
            'processStageOptions' => $this->processStageOptions(),
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'pageTitle' => 'New Cases',
            'showStatusFilter' => false,
            'showProcessFilter' => false,
            'emptyMessage' => 'No new cases found.',
        ]);
    }

    public function visitingClients()
    {
        $user = Auth::user();
        $statuses = config('crm.visiting_client_statuses', []);
        $query = Customer::with('telecaller')
            ->where('source', 'Telecaller')
            ->whereIn('status', $statuses);

        if ($user->role === 'telecaller') {
            $query->where('telecaller_id', $user->id);
        }

        $customers = $query
            ->latest('id')
            ->paginate(15);

        return view('customers.visiting-clients', [
            'customers' => $customers,
            'canMarkReady' => $user->role === 'receptionist',
        ]);
    }

    public function create(Request $request)
    {
        if (Auth::user()->role === 'telecaller') {
            return redirect()->route('customers.create-telecaller');
        }

        $prefillLead = null;
        if ($request->filled('lead_id')) {
            $prefillLead = Customer::whereKey($request->query('lead_id'))
                ->where('source', 'Telecaller')
                ->whereIn('status', config('crm.visiting_client_statuses', []))
                ->firstOrFail();
        }

        $prefillEntry = null;
        if ($request->filled('entry_id')) {
            $prefillEntry = CustomerEntry::whereKey($request->query('entry_id'))
                ->whereNull('converted_at')
                ->firstOrFail();
        }

        $counselors = User::where('role', 'counselor')->where('status', 'active')->get();
        $agents = User::where('role', 'agent')->where('status', 'active')->orderBy('name')->get();
        $qualifications = Qualification::active()->orderBy('name')->pluck('name');

        return view('customers.create', compact('counselors', 'agents', 'qualifications', 'prefillLead', 'prefillEntry'));
    }

    public function createTelecaller()
    {
        return view('customers.create-telecaller');
    }

    public function storeTelecaller(StoreTelecallerCustomerRequest $request)
    {
        $validated = $request->validated();

        $customer = DB::transaction(function () use ($validated) {
            return Customer::create([
                'pid' => PidGenerator::next(),
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'country' => $validated['country'],
                'visa_type' => $validated['visa_type'],
                'status' => $validated['status'],
                'visit_date' => $validated['status'] === 'will visit' ? $validated['visit_date'] : null,
                'telecaller_id' => Auth::id(),
                'created_by' => Auth::id(),
                'source' => 'Telecaller',
                'english_test' => 'no',
                'previous_refusal' => 'no',
            ]);
        });

        ActivityLogger::log(Auth::id(), 'ADD_LEAD', 'Telecaller added lead ' . $customer->activitySummary(), $customer->id);

        return redirect()->route('customers.show', $customer)->with('success', 'Lead added successfully.');
    }

    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();

        if (($validated['source'] ?? '') === 'Reference' && empty($validated['reference_name'])) {
            return back()->withErrors(['reference_name' => 'Reference name is required.'])->withInput();
        }
        if (($validated['source'] ?? '') === 'Telecaller' && empty($validated['lead_id'])) {
            return back()->withErrors(['source' => 'Telecaller source is only available from visiting clients.'])->withInput();
        }
        if (Auth::user()->role === 'agent') {
            $validated['source'] = 'Agents';
            $validated['agent_id'] = Auth::id();
            $validated['reference_name'] = Auth::user()->name;
            $validated['assigned_counselor_id'] = null;
            $validated['telecaller_id'] = null;
        } elseif (($validated['source'] ?? '') === 'Agents' && empty($validated['agent_id'])) {
            return back()->withErrors(['agent_id' => 'Select an agent.'])->withInput();
        } elseif (($validated['source'] ?? '') === 'Agents') {
            $validated['assigned_counselor_id'] = null;
            $validated['telecaller_id'] = null;
        } else {
            $validated['agent_id'] = null;
        }

        $customer = DB::transaction(function () use ($validated) {
            $leadId = $validated['lead_id'] ?? null;
            $entryId = $validated['entry_id'] ?? null;
            $refusalCountries = $validated['refusal_countries'] ?? [];
            $customerFields = collect($validated)->except(['lead_id', 'entry_id', 'refusal_countries'])->all();
            $customerFields['status'] = 'assigned';
            $customerFields['english_test'] = $customerFields['english_test'] ?? 'no';
            $customerFields['previous_refusal'] = $customerFields['previous_refusal'] ?? 'no';
            $this->normalizeEnglishFields($customerFields);

            if ($leadId) {
                $customer = Customer::whereKey($leadId)
                    ->where('source', 'Telecaller')
                    ->whereIn('status', config('crm.visiting_client_statuses', []))
                    ->lockForUpdate()
                    ->first();

                if (!$customer) {
                    abort(404);
                }

                $customer->update($customerFields);
            } else {
                $entry = null;
                if ($entryId) {
                    $entry = CustomerEntry::whereKey($entryId)
                        ->whereNull('converted_at')
                        ->lockForUpdate()
                        ->first();

                    if (!$entry) {
                        abort(404);
                    }
                }

                $customerFields['pid'] = PidGenerator::next();
                $customerFields['created_by'] = Auth::id();
                $customer = Customer::create($customerFields);

                if ($entry) {
                    $entry->update([
                        'converted_customer_id' => $customer->id,
                        'converted_by' => Auth::id(),
                        'converted_at' => now(),
                    ]);
                }
            }

            $customer->refusals()->delete();
            if ($customerFields['previous_refusal'] === 'yes' && !empty($refusalCountries)) {
                foreach ($refusalCountries as $country) {
                    if ($country) {
                        CustomerRefusal::create(['customer_id' => $customer->id, 'country' => $country]);
                    }
                }
            }

            $assigneeIds = collect([
                $customerFields['assigned_counselor_id'] ?? null,
                $customerFields['telecaller_id'] ?? null,
            ])->filter()->unique()->values();

            if ($assigneeIds->isNotEmpty()) {
                $assignees = User::whereIn('id', $assigneeIds)->get()->keyBy('id');
                $assigneeNames = $assigneeIds
                    ->map(fn ($id) => optional($assignees->get($id))->name)
                    ->filter()
                    ->join(', ');

                $assigneeIds->each(function ($userId) use ($customer, $assigneeNames) {
                    Notification::create([
                        'user_id' => $userId,
                        'customer_id' => $customer->id,
                        'title' => 'New case assigned',
                        'message' => 'New customer ' . $customer->activitySummary() . ' assigned to ' . ($assigneeNames ?: 'assigned users'),
                    ]);
                });
            }

            $actionText = $leadId
                ? 'Marked visiting client ready as customer '
                : ($entryId ? 'Submitted tab entry as customer ' : 'Added customer ');
            ActivityLogger::log(Auth::id(), 'ADD_CUSTOMER', $actionText . $customer->activitySummary(), $customer->id);
            $this->notifyAdminsAboutNewCustomer($customer, Auth::user());

            return $customer;
        });

        if ($customer->email) {
            try {
                Mail::to($customer->email)->send(new WelcomeMail($customer->load('counselor')));
            } catch (\Throwable $e) {
                // Mail may be unconfigured locally
            }
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $counselors = User::where('role', 'counselor')->where('status', 'active')->get();
        $telecallers = User::where('role', 'telecaller')->where('status', 'active')->get();
        $agents = User::where('role', 'agent')->where('status', 'active')->orderBy('name')->get();
        $qualifications = Qualification::active()->orderBy('name')->pluck('name');
        $existingRefusalCountries = $customer->refusals()->pluck('country')->all();

        return view('customers.edit', compact('customer', 'counselors', 'telecallers', 'agents', 'qualifications', 'existingRefusalCountries'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $validated = $request->validated();

        if (($validated['source'] ?? '') === 'Reference' && empty($validated['reference_name'])) {
            return back()->withErrors(['reference_name' => 'Reference name is required.'])->withInput();
        }
        if (($validated['source'] ?? '') === 'Telecaller' && empty($validated['telecaller_id'])) {
            return back()->withErrors(['telecaller_id' => 'Select a telecaller.'])->withInput();
        }
        if (Auth::user()->role === 'agent') {
            $validated['source'] = 'Agents';
            $validated['agent_id'] = Auth::id();
            $validated['reference_name'] = Auth::user()->name;
            $validated['assigned_counselor_id'] = null;
            $validated['telecaller_id'] = null;
        } elseif (($validated['source'] ?? '') === 'Agents' && empty($validated['agent_id'])) {
            return back()->withErrors(['agent_id' => 'Select an agent.'])->withInput();
        } elseif (($validated['source'] ?? '') === 'Agents') {
            $validated['assigned_counselor_id'] = null;
            $validated['telecaller_id'] = null;
        } else {
            $validated['agent_id'] = null;
        }

        DB::transaction(function () use ($validated, $customer) {
            $validated['english_test'] = $validated['english_test'] ?? 'no';
            $validated['previous_refusal'] = $validated['previous_refusal'] ?? 'no';
            $this->normalizeEnglishFields($validated);

            $before = $this->customerChangeSnapshot($customer, $customer->refusals()->pluck('country')->all());
            $customerFields = collect($validated)->except('refusal_countries')->all();

            $customer->update($customerFields);

            $customer->refusals()->delete();
            if ($validated['previous_refusal'] === 'yes' && !empty($validated['refusal_countries'])) {
                foreach ($validated['refusal_countries'] as $country) {
                    if ($country) {
                        CustomerRefusal::create(['customer_id' => $customer->id, 'country' => $country]);
                    }
                }
            }

            $customer->refresh();
            $after = $this->customerChangeSnapshot($customer, $customer->refusals()->pluck('country')->all());
            $changes = $this->formatCustomerChanges($before, $after);
            $changeSummary = $changes ? implode('; ', $changes) : 'No field changes';
            $summary = $customer->activitySummary();
            $actorName = Auth::user()->name;

            ActivityLogger::log(
                Auth::id(),
                'EDIT_CUSTOMER',
                "Updated customer {$summary}. Changes: {$changeSummary}",
                $customer->id
            );

            if ($changes) {
                $this->notifyCustomerUpdated($customer, "{$actorName} updated {$summary}. Changes: {$changeSummary}");
            }
        });

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated successfully.');
    }

    public function updateIntake(Request $request, Customer $customer)
    {
        $this->authorizeIntake($customer);

        $validated = $request->validate([
            'intake_month' => ['required', 'integer', 'between:1,12'],
            'intake_year' => ['required', 'integer', 'between:' . now()->year . ',' . (now()->year + 20)],
        ]);

        $selected = Carbon::create((int) $validated['intake_year'], (int) $validated['intake_month'], 1)->startOfMonth();
        $current = now()->startOfMonth();

        if ($selected->lt($current)) {
            return back()->withErrors(['intake_month' => 'Intake cannot be earlier than the current month.'])->withInput();
        }

        $customer->update([
            'intake_month' => (int) $validated['intake_month'],
            'intake_year' => (int) $validated['intake_year'],
        ]);

        ActivityLogger::log(
            Auth::id(),
            'UPDATE_INTAKE',
            'Updated intake for ' . $customer->activitySummary() . ' to ' . $selected->format('M Y'),
            $customer->id
        );

        return redirect()->route('customers.show', $customer)->with('success', 'Intake updated.');
    }

    public function updateAgentCommercial(Request $request, Customer $customer)
    {
        if (!in_array(Auth::user()->role, ['admin', 'director'], true)) {
            abort(403);
        }

        $validated = $request->validate([
            'visa_duration' => ['nullable', 'string', 'max:120'],
            'actual_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'b2b_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);

        $actualCost = $validated['actual_cost'] ?? null;
        $b2bCost = $validated['b2b_cost'] ?? null;
        $margin = ($actualCost !== null && $b2bCost !== null)
            ? round((float) $b2bCost - (float) $actualCost, 2)
            : null;

        $customer->update([
            'visa_duration' => $validated['visa_duration'] ?? null,
            'actual_cost' => $actualCost,
            'b2b_cost' => $b2bCost,
            'margin' => $margin,
        ]);

        ActivityLogger::log(
            Auth::id(),
            'UPDATE_AGENT_COMMERCIAL',
            'Updated agent commercial details for ' . $customer->activitySummary(),
            $customer->id
        );

        return redirect()->route('customers.show', $customer)->with('success', 'Agent commercial details updated.');
    }

    public function updateFilingBy(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $validated = $request->validate([
            'filing_by' => ['nullable', 'string', 'max:5000'],
        ]);

        $customer->update([
            'filing_by' => trim((string) ($validated['filing_by'] ?? '')) ?: null,
        ]);

        ActivityLogger::log(
            Auth::id(),
            'UPDATE_FILING_BY',
            'Updated filing by for ' . $customer->activitySummary(),
            $customer->id
        );

        return redirect()->route('customers.show', $customer)->with('success', 'Filing by updated.');
    }

    public function storeSpecialRemark(Request $request, Customer $customer)
    {
        $this->authorizeSpecialRemarkEntry($customer);

        if (trim((string) $customer->special_remark) !== '') {
            return back()
                ->withErrors(['special_remark' => 'Special remark has already been entered for this customer.'])
                ->withInput();
        }

        $validated = $request->validate([
            'special_remark' => ['required', 'string', 'max:5000'],
        ]);

        $specialRemark = trim($validated['special_remark']);
        if ($specialRemark === '') {
            return back()
                ->withErrors(['special_remark' => 'Special remark is required.'])
                ->withInput();
        }

        $customer->update([
            'special_remark' => $specialRemark,
            'special_remark_by' => Auth::id(),
            'special_remark_at' => now(),
        ]);

        ActivityLogger::log(
            Auth::id(),
            'SPECIAL_REMARK',
            'Added special remark for ' . $customer->activitySummary(),
            $customer->id
        );

        return redirect()->route('customers.show', $customer)->with('success', 'Special remark saved.');
    }

    public function show(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $relations = [
            'counselor',
            'telecaller',
            'agent',
            'refusals',
            'remarks' => function ($remarkQuery) {
                $remarkQuery->latest('id');
            },
            'remarks.user',
            'remarks.taggedUsers',
            'fees' => function ($feeQuery) {
                $feeQuery->latest('id');
            },
            'fees.user',
            'specialRemarkAuthor',
            'documents.uploader',
            'followUps',
            'processSteps.completedBy',
        ];

        if (Auth::user()->role === 'admin') {
            $relations[] = 'activityLogs.user';
            $relations[] = 'activityLogs.customer';
        }

        $customer->load($relations);

        $activityLogs = Auth::user()->role === 'admin' ? $customer->activityLogs : collect();
        $processTimeline = $this->timelineSteps($customer->visa_type);
        $completedProcessSteps = $customer->processSteps->keyBy('step_key');
        $isAssignedAgent = Auth::user()->role === 'agent' && $customer->agent_id === Auth::id();
        $canViewProcessTimeline = in_array(Auth::user()->role, ['admin', 'director', 'receptionist', 'counselor'], true) || $isAssignedAgent;
        $canCompleteProcessTimeline = in_array(Auth::user()->role, ['admin', 'director', 'counselor'], true) || $isAssignedAgent;
        $canReopenProcessTimeline = in_array(Auth::user()->role, ['admin', 'director'], true) || $isAssignedAgent;
        $canAddFees = Auth::user()->role !== 'telecaller';
        $canManageIntake = in_array(Auth::user()->role, ['admin', 'director'], true)
            || (Auth::user()->role === 'counselor' && $customer->assigned_counselor_id === Auth::id())
            || (Auth::user()->role === 'agent' && $customer->agent_id === Auth::id());
        $canViewSpecialRemark = in_array(Auth::user()->role, ['admin', 'director', 'counselor'], true);
        $canAddSpecialRemark = Auth::user()->role === 'counselor'
            && $customer->assigned_counselor_id === Auth::id()
            && trim((string) $customer->special_remark) === '';
        $canViewAgentCommercial = in_array(Auth::user()->role, ['admin', 'director'], true)
            || (Auth::user()->role === 'agent' && $customer->agent_id === Auth::id());
        $canManageAgentCommercial = in_array(Auth::user()->role, ['admin', 'director'], true);

        return view('customers.show', compact(
            'customer',
            'activityLogs',
            'processTimeline',
            'completedProcessSteps',
            'canViewProcessTimeline',
            'canCompleteProcessTimeline',
            'canReopenProcessTimeline',
            'canAddFees',
            'canManageIntake',
            'canViewSpecialRemark',
            'canAddSpecialRemark',
            'canViewAgentCommercial',
            'canManageAgentCommercial'
        ));
    }

    private function timelineSteps(?string $visaType): array
    {
        return ProcessTimelineService::stepsForVisaType($visaType);
    }

    private function visibleCustomerQuery()
    {
        $user = Auth::user();
        $query = Customer::with([
            'counselor',
            'nextFollowUp',
            'processSteps' => function ($processQuery) {
                $processQuery->whereNotNull('completed_at')
                    ->orderByDesc('step_order')
                    ->orderByDesc('completed_at');
            },
        ])->latest('id');

        if ($user->role === 'counselor') {
            $query->where('assigned_counselor_id', $user->id);
        } elseif ($user->role === 'telecaller') {
            $query->where('telecaller_id', $user->id);
        } elseif ($user->role === 'agent') {
            $query->where('agent_id', $user->id);
        }

        return $query;
    }

    private function normalizeEnglishFields(array &$fields): void
    {
        if (($fields['english_test'] ?? 'no') === 'yes') {
            $fields['english_subject_score'] = null;
            return;
        }

        foreach (['test_type', 'listening', 'reading', 'writing', 'speaking', 'overall', 'test_expiry'] as $field) {
            $fields[$field] = null;
        }
    }

    private function customerSort(Request $request): array
    {
        $allowedColumns = ['pid', 'name', 'phone', 'country', 'visa_type', 'source', 'process', 'status', 'follow_up', 'counselor'];
        $sortColumn = $request->query('sort', 'latest');
        $sortDirection = strtolower((string) $request->query('direction', 'desc'));

        if (!in_array($sortColumn, $allowedColumns, true)) {
            $sortColumn = 'latest';
        }

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        return [$sortColumn, $sortDirection];
    }

    private function applyCustomerSort($query, string $sortColumn, string $sortDirection): void
    {
        $query->reorder();

        if ($sortColumn === 'latest') {
            $query->latest('customers.id');
            return;
        }

        if ($sortColumn === 'process') {
            $latestProcessLabelSql = "(
                SELECT cps_latest.step_label
                FROM customer_process_steps as cps_latest
                WHERE cps_latest.customer_id = customers.id
                    AND cps_latest.completed_at IS NOT NULL
                ORDER BY cps_latest.step_order DESC, cps_latest.completed_at DESC, cps_latest.id DESC
                LIMIT 1
            )";

            $query->orderByRaw("COALESCE({$latestProcessLabelSql}, 'Not started') {$sortDirection}")
                ->orderBy('customers.id', 'desc');

            return;
        }

        if ($sortColumn === 'follow_up') {
            $nextFollowUpSql = "(
                SELECT fu_latest.follow_up_date
                FROM follow_ups as fu_latest
                WHERE fu_latest.customer_id = customers.id
                    AND fu_latest.status = 'pending'
                    AND fu_latest.follow_up_date IS NOT NULL
                ORDER BY fu_latest.follow_up_date ASC, fu_latest.id ASC
                LIMIT 1
            )";

            $query->orderByRaw("{$nextFollowUpSql} IS NULL ASC")
                ->orderByRaw("{$nextFollowUpSql} {$sortDirection}")
                ->orderBy('customers.id', 'desc');

            return;
        }

        if ($sortColumn === 'counselor') {
            $query->leftJoin('users as counselor_sort', 'customers.assigned_counselor_id', '=', 'counselor_sort.id')
                ->select('customers.*')
                ->orderByRaw("COALESCE(counselor_sort.name, '') {$sortDirection}")
                ->orderBy('customers.id', 'desc');

            return;
        }

        $columnMap = [
            'pid' => 'customers.pid',
            'name' => 'customers.name',
            'phone' => 'customers.phone',
            'country' => 'customers.country',
            'visa_type' => 'customers.visa_type',
            'source' => 'customers.source',
            'status' => 'customers.status',
        ];

        $query->orderBy($columnMap[$sortColumn], $sortDirection)
            ->orderBy('customers.id', 'desc');
    }

    private function applyProcessStageFilter($query, string $processStage): void
    {
        if ($processStage === 'Not started') {
            $query->whereDoesntHave('processSteps', function ($processQuery) {
                $processQuery->whereNotNull('completed_at');
            });

            return;
        }

        $query->whereExists(function ($processQuery) use ($processStage) {
            $processQuery->selectRaw('1')
                ->from('customer_process_steps as cps')
                ->whereColumn('cps.customer_id', 'customers.id')
                ->whereNotNull('cps.completed_at')
                ->where('cps.step_label', $processStage)
                ->whereRaw('cps.id = (
                    SELECT cps_latest.id
                    FROM customer_process_steps as cps_latest
                    WHERE cps_latest.customer_id = customers.id
                        AND cps_latest.completed_at IS NOT NULL
                    ORDER BY cps_latest.step_order DESC, cps_latest.completed_at DESC, cps_latest.id DESC
                    LIMIT 1
                )');
        });
    }

    private function processStageOptions()
    {
        $visibleCustomerIds = $this->visibleCustomerQuery()->pluck('id');

        $completedStages = CustomerProcessStep::query()
            ->whereNotNull('completed_at')
            ->when($visibleCustomerIds->isNotEmpty(), function ($query) use ($visibleCustomerIds) {
                $query->whereIn('customer_id', $visibleCustomerIds);
            })
            ->when($visibleCustomerIds->isEmpty(), function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->distinct()
            ->orderBy('step_label')
            ->pluck('step_label');

        return collect(['Not started'])
            ->merge($completedStages)
            ->unique()
            ->values();
    }

    private function customerChangeSnapshot(Customer $customer, array $refusalCountries): array
    {
        return [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'dob' => $customer->dob,
            'gender' => $customer->gender,
            'marital_status' => $customer->marital_status,
            'father_spouse_name' => $customer->father_spouse_name,
            'residence_country' => $this->countryLabel($customer->residence_country),
            'qualification' => $customer->qualification,
            'qualification_year' => $customer->qualification_year,
            'gap_years' => $customer->gap_years,
            'score' => $customer->score,
            'visa_type' => $customer->visa_type,
            'country' => $this->countryLabel($customer->country),
            'source' => $customer->source,
            'reference_name' => $customer->reference_name,
            'telecaller_id' => $this->userLabel($customer->telecaller_id),
            'english_test' => $customer->english_test,
            'english_subject_score' => $customer->english_subject_score,
            'test_type' => $customer->test_type,
            'listening' => $customer->listening,
            'reading' => $customer->reading,
            'writing' => $customer->writing,
            'speaking' => $customer->speaking,
            'overall' => $customer->overall,
            'test_expiry' => $customer->test_expiry,
            'previous_refusal' => $customer->previous_refusal,
            'refusal_countries' => $this->countryListLabel($refusalCountries),
            'assigned_counselor_id' => $this->userLabel($customer->assigned_counselor_id),
            'status' => $customer->status,
        ];
    }

    private function formatCustomerChanges(array $before, array $after): array
    {
        $labels = [
            'name' => 'Name',
            'phone' => 'Phone',
            'email' => 'Email',
            'dob' => 'DOB',
            'gender' => 'Gender',
            'marital_status' => 'Marital status',
            'father_spouse_name' => 'Father / spouse name',
            'residence_country' => 'Residence country',
            'qualification' => 'Qualification',
            'qualification_year' => 'Pass-out year',
            'gap_years' => 'GAP',
            'score' => 'Score',
            'visa_type' => 'Visa type',
            'country' => 'Visa country',
            'source' => 'Source',
            'reference_name' => 'Reference name',
            'telecaller_id' => 'Telecaller',
            'english_test' => 'English test',
            'english_subject_score' => 'English subject score',
            'test_type' => 'Test type',
            'listening' => 'Listening',
            'reading' => 'Reading',
            'writing' => 'Writing',
            'speaking' => 'Speaking',
            'overall' => 'Overall',
            'test_expiry' => 'Test expiry',
            'previous_refusal' => 'Previous refusal',
            'refusal_countries' => 'Refusal countries',
            'assigned_counselor_id' => 'Counselor',
            'status' => 'Status',
        ];

        $changes = [];
        foreach ($labels as $field => $label) {
            $old = $this->displayValue($before[$field] ?? null);
            $new = $this->displayValue($after[$field] ?? null);

            if ($old !== $new) {
                $changes[] = "{$label}: {$old} -> {$new}";
            }
        }

        return $changes;
    }

    private function notifyCustomerUpdated(Customer $customer, string $message): void
    {
        $recipientIds = User::where('role', 'admin')
            ->where('status', 'active')
            ->pluck('id');

        if ($customer->assigned_counselor_id) {
            $recipientIds->push($customer->assigned_counselor_id);
        }

        $recipientIds->unique()
            ->each(function ($userId) use ($customer, $message) {
                Notification::create([
                    'user_id' => $userId,
                    'customer_id' => $customer->id,
                    'title' => 'Customer updated',
                    'message' => $message,
                ]);
            });
    }

    private function userLabel($userId): ?string
    {
        if (!$userId) {
            return null;
        }

        return optional(User::find($userId))->name ?: 'User #' . $userId;
    }

    private function countryLabel($code): ?string
    {
        if (!$code) {
            return null;
        }

        return config('crm.countries')[$code] ?? $code;
    }

    private function countryListLabel(array $countries): string
    {
        return collect($countries)
            ->filter()
            ->map(fn ($country) => $this->countryLabel($country))
            ->sort()
            ->values()
            ->join(', ');
    }

    private function displayValue($value): string
    {
        if ($value === null || $value === '') {
            return 'blank';
        }

        return (string) $value;
    }

    private function authorizeCustomer(Customer $customer)
    {
        $user = Auth::user();

        if (in_array($user->role, ['admin', 'director', 'receptionist'], true)) {
            return;
        }
        if ($user->role === 'counselor' && $customer->assigned_counselor_id === $user->id) {
            return;
        }
        if ($user->role === 'telecaller' && $customer->telecaller_id === $user->id) {
            return;
        }
        if ($user->role === 'agent') {
            if ($customer->agent_id === $user->id) {
                return;
            }

            abort(403);
        }
        if (Notification::where('user_id', $user->id)->where('customer_id', $customer->id)->exists()) {
            return;
        }

        abort(403);
    }

    private function authorizeIntake(Customer $customer): void
    {
        $user = Auth::user();

        if (!$user || !in_array($user->role, ['admin', 'director', 'counselor', 'agent'], true)) {
            abort(403);
        }

        if (in_array($user->role, ['admin', 'director'], true)) {
            return;
        }

        if ($user->role === 'counselor' && $customer->assigned_counselor_id === $user->id) {
            return;
        }

        if ($user->role === 'agent' && $customer->agent_id === $user->id) {
            return;
        }

        abort(403);
    }

    private function authorizeSpecialRemarkEntry(Customer $customer): void
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'counselor' || $customer->assigned_counselor_id !== $user->id) {
            abort(403);
        }
    }

    private function notifyAdminsAboutNewCustomer(Customer $customer, User $creator): void
    {
        $agentName = optional($customer->agent)->name ?: ($customer->agent_id ? $this->userLabel($customer->agent_id) : null);
        $creatorLabel = $creator->role === 'agent'
            ? 'Agent ' . $creator->name
            : ucfirst($creator->role) . ' ' . $creator->name;
        $agentText = $agentName ? ' Agent: ' . $agentName . '.' : '';

        User::where('role', 'admin')
            ->where('status', 'active')
            ->pluck('id')
            ->each(function ($adminId) use ($customer, $creatorLabel, $agentText) {
                Notification::create([
                    'user_id' => $adminId,
                    'customer_id' => $customer->id,
                    'title' => 'New customer registered',
                    'message' => $creatorLabel . ' registered new customer ' . $customer->activitySummary() . '.' . $agentText,
                ]);
            });
    }
}
