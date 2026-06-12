<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/\s+/', '', $this->input('phone')),
            ]);
        }
    }

    public function rules()
    {
        $leadId = $this->input('lead_id');
        $phoneRule = Rule::unique('customers', 'phone');
        if ($leadId && $this->routeIs('customers.store')) {
            $phoneRule->ignore($leadId);
        }

        return [
            'lead_id' => ['nullable', 'integer', 'exists:customers,id'],
            'entry_id' => ['nullable', 'integer', 'exists:customer_entries,id'],
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:30', $phoneRule],
            'email' => 'nullable|email|max:255',
            'dob' => 'nullable|date|before:today',
            'gender' => ['nullable', Rule::in(config('crm.genders'))],
            'marital_status' => ['nullable', Rule::in(config('crm.marital_statuses'))],
            'father_spouse_name' => 'nullable|string|max:255',
            'residence_country' => ['nullable', Rule::in(array_keys(config('crm.countries')))],
            'qualification' => ['nullable', 'string', 'max:120', Rule::in($this->qualificationOptions())],
            'qualification_year' => 'nullable|integer|min:1950|max:' . (date('Y') + 1),
            'gap_years' => ['nullable', Rule::in(config('crm.gap_options'))],
            'score' => 'nullable|string|max:50',
            'visa_type' => ['required', Rule::in(config('crm.visa_types'))],
            'country' => ['required', Rule::in(array_keys(config('crm.countries')))],
            'source' => 'nullable|string|max:255',
            'reference_name' => 'nullable|string|max:255',
            'telecaller_id' => ['nullable', 'exists:users,id'],
            'english_test' => 'nullable|in:yes,no',
            'english_subject_score' => ['nullable', 'required_if:english_test,no', 'string', 'max:50'],
            'test_type' => 'nullable|string|max:255',
            'listening' => 'nullable|numeric',
            'reading' => 'nullable|numeric',
            'writing' => 'nullable|numeric',
            'speaking' => 'nullable|numeric',
            'overall' => 'nullable|numeric',
            'test_expiry' => 'nullable|date',
            'previous_refusal' => 'nullable|in:yes,no',
            'refusal_countries' => 'nullable|array',
            'refusal_countries.*' => 'string|max:255',
            'assigned_counselor_id' => ['required', 'exists:users,id'],
            'status' => ['nullable', Rule::in(config('crm.customer_statuses', []))],
        ];
    }

    public function messages()
    {
        return [
            'phone.unique' => 'Phone is already registered.',
            'assigned_counselor_id.required' => 'Select a counselor before creating the customer.',
            'english_subject_score.required_if' => 'Enter the English subject score when English Test is No.',
        ];
    }

    private function qualificationOptions(): array
    {
        if (!Schema::hasTable('qualifications')) {
            return ['10th', '12th', 'graduated', 'master'];
        }

        return \App\Models\Qualification::active()->pluck('name')->all();
    }
}
