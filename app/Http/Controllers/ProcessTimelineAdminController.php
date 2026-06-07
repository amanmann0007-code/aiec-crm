<?php

namespace App\Http\Controllers;

use App\Models\ProcessTimelineStep;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProcessTimelineAdminController extends Controller
{
    public function index()
    {
        $steps = ProcessTimelineStep::orderBy('visa_type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('visa_type');

        return view('process-timelines.index', [
            'stepsByVisaType' => $steps,
            'visaTypes' => config('crm.visa_types', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visa_type' => ['required', 'string', 'max:120', Rule::in(config('crm.visa_types', []))],
            'label' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $stepKey = $this->uniqueStepKey($validated['visa_type'], $validated['label']);

        $step = ProcessTimelineStep::create([
            'visa_type' => $validated['visa_type'],
            'step_key' => $stepKey,
            'label' => $validated['label'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLogger::log(Auth::id(), 'CREATE_PROCESS_TIMELINE_STEP', 'Created process timeline step ' . $step->label . ' for ' . $step->visa_type);

        return redirect()->route('process-timelines.index')->with('success', 'Process timeline step created.');
    }

    public function update(Request $request, ProcessTimelineStep $processTimeline)
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $processTimeline->update([
            'label' => $validated['label'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLogger::log(Auth::id(), 'UPDATE_PROCESS_TIMELINE_STEP', 'Updated process timeline step ' . $processTimeline->label . ' for ' . $processTimeline->visa_type);

        return redirect()->route('process-timelines.index')->with('success', 'Process timeline step updated.');
    }

    public function saveAll(Request $request)
    {
        $validated = $request->validate([
            'steps' => ['nullable', 'array'],
            'steps.*.id' => ['nullable', 'integer', 'exists:process_timeline_steps,id'],
            'steps.*.visa_type' => ['required', 'string', 'max:120', Rule::in(config('crm.visa_types', []))],
            'steps.*.label' => ['required', 'string', 'max:255'],
            'steps.*.is_active' => ['nullable', 'boolean'],
        ]);

        $steps = collect($validated['steps'] ?? [])
            ->map(function ($step) {
                $step['label'] = trim($step['label']);
                return $step;
            })
            ->filter(fn ($step) => $step['label'] !== '')
            ->values();

        $submittedIds = $steps->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
        ProcessTimelineStep::whereIn('visa_type', config('crm.visa_types', []))
            ->when($submittedIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $submittedIds))
            ->delete();

        $orderByVisaType = [];

        foreach ($steps as $step) {
            $visaType = $step['visa_type'];
            $orderByVisaType[$visaType] = ($orderByVisaType[$visaType] ?? 0) + 1;

            $attributes = [
                'visa_type' => $visaType,
                'label' => $step['label'],
                'sort_order' => $orderByVisaType[$visaType],
                'is_active' => (bool) ($step['is_active'] ?? true),
            ];

            if (!empty($step['id'])) {
                ProcessTimelineStep::whereKey($step['id'])->update($attributes);
            } else {
                $attributes['step_key'] = $this->uniqueStepKey($visaType, $step['label']);
                ProcessTimelineStep::create($attributes);
            }
        }

        ActivityLogger::log(Auth::id(), 'SAVE_PROCESS_TIMELINES', 'Saved all process timeline tiles.');

        return redirect()->route('process-timelines.index')->with('success', 'Process timelines saved.');
    }

    private function uniqueStepKey(string $visaType, string $label): string
    {
        $base = Str::slug($label) ?: 'step';
        $key = $base;
        $counter = 2;

        while (ProcessTimelineStep::where('visa_type', $visaType)->where('step_key', $key)->exists()) {
            $key = $base . '-' . $counter;
            $counter++;
        }

        return $key;
    }
}
