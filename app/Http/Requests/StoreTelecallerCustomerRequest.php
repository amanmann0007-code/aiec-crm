<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTelecallerCustomerRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->role === 'telecaller';
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
        return [
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:30', Rule::unique('customers', 'phone')],
            'country' => ['required', Rule::in(array_keys(config('crm.countries')))],
            'visa_type' => ['required', Rule::in(config('crm.visa_types'))],
            'status' => ['required', Rule::in(config('crm.telecaller_statuses', []))],
            'visit_date' => ['nullable', 'required_if:status,will visit', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages()
    {
        return [
            'phone.unique' => 'Phone is already registered.',
            'visit_date.required_if' => 'Visit date is required when status is Will visit.',
            'visit_date.after_or_equal' => 'Visit date cannot be in the past.',
        ];
    }
}
