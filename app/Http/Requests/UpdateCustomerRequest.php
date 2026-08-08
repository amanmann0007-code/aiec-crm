<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize()
    {
        if (!$this->user()) {
            return false;
        }

        if (in_array($this->user()->role, ['admin', 'counselor', 'director'], true)) {
            return true;
        }

        $customer = $this->route('customer');

        return $this->user()->role === 'agent'
            && $customer
            && (int) $customer->agent_id === (int) $this->user()->id;
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
        $customer = $this->route('customer');
        $customerId = $customer ? $customer->id : null;
        $isAgentSource = optional($this->user())->role === 'agent' || $this->input('source') === 'Agents';
        $assigneeRule = $isAgentSource ? 'nullable' : 'required_without:telecaller_id';
        $telecallerRule = $isAgentSource ? 'nullable' : 'required_without:assigned_counselor_id';

        return [
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:30', Rule::unique('customers', 'phone')->ignore($customerId)],
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
            'telecaller_id' => ['nullable', $telecallerRule, 'exists:users,id'],
            'agent_id' => ['nullable', Rule::exists('users', 'id')->where(function ($query) {
                $query->where('role', 'agent')->where('status', 'active');
            })],
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
            'assigned_counselor_id' => ['nullable', $assigneeRule, 'exists:users,id'],
            'status' => ['required', Rule::in(config('crm.customer_statuses', []))],
        ];
    }

    public function messages()
    {
        return [
            'phone.unique' => 'Phone is already registered.',
            'assigned_counselor_id.required_without' => 'Assign either a counselor or a telecaller.',
            'telecaller_id.required_without' => 'Assign either a counselor or a telecaller.',
            'english_subject_score.required_if' => 'Enter the English subject score when English Test is No.',
            'agent_id.exists' => 'Select a valid active agent.',
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
