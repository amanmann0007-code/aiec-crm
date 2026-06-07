<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerProcessStep;
use App\Models\ProcessTimelineStep;

class ProcessTimelineService
{
    public const DROPOUT_KEY = 'dropout';
    public const DROPOUT_LABEL = 'Dropout';

    public static function stepsForVisaType(?string $visaType): array
    {
        $steps = ProcessTimelineStep::where('visa_type', $visaType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (ProcessTimelineStep $step) {
                return [
                    'key' => $step->step_key,
                    'label' => $step->label,
                    'order' => $step->sort_order,
                ];
            })
            ->values()
            ->all();

        return self::appendDropoutStep($steps);
    }

    public static function stepForCustomer(Customer $customer, string $stepKey): ?array
    {
        if ($stepKey === self::DROPOUT_KEY) {
            return self::dropoutStep($customer->visa_type);
        }

        $step = ProcessTimelineStep::where('visa_type', $customer->visa_type)
            ->where('step_key', $stepKey)
            ->where('is_active', true)
            ->first();

        if (!$step) {
            return null;
        }

        return [
            'key' => $step->step_key,
            'label' => $step->label,
            'order' => $step->sort_order,
        ];
    }

    public static function completeDropout(Customer $customer, ?int $userId): CustomerProcessStep
    {
        $step = self::dropoutStep($customer->visa_type);

        return CustomerProcessStep::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'step_key' => self::DROPOUT_KEY,
            ],
            [
                'visa_type' => $customer->visa_type,
                'step_label' => self::DROPOUT_LABEL,
                'step_order' => $step['order'],
                'completed_by' => $userId,
                'completed_at' => now(),
            ]
        );
    }

    private static function appendDropoutStep(array $steps): array
    {
        foreach ($steps as $step) {
            if (($step['key'] ?? null) === self::DROPOUT_KEY) {
                return $steps;
            }
        }

        $steps[] = self::dropoutStepFromSteps($steps);

        return $steps;
    }

    private static function dropoutStep(?string $visaType): array
    {
        $maxOrder = (int) ProcessTimelineStep::where('visa_type', $visaType)
            ->where('is_active', true)
            ->max('sort_order');

        return [
            'key' => self::DROPOUT_KEY,
            'label' => self::DROPOUT_LABEL,
            'order' => $maxOrder + 1,
        ];
    }

    private static function dropoutStepFromSteps(array $steps): array
    {
        $maxOrder = collect($steps)->max('order') ?: 0;

        return [
            'key' => self::DROPOUT_KEY,
            'label' => self::DROPOUT_LABEL,
            'order' => $maxOrder + 1,
        ];
    }
}
