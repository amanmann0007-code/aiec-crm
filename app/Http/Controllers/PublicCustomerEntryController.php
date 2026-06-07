<?php

namespace App\Http\Controllers;

use App\Models\CustomerEntry;
use App\Models\Qualification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PublicCustomerEntryController extends Controller
{
    public function create()
    {
        return view('customer-entries.public-create', [
            'qualifications' => $this->qualificationOptions(),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has('phone')) {
            $request->merge([
                'phone' => preg_replace('/\s+/', '', $request->input('phone')),
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(config('crm.genders'))],
            'marital_status' => ['nullable', Rule::in(config('crm.marital_statuses'))],
            'father_spouse_name' => ['nullable', 'string', 'max:255'],
            'residence_country' => ['nullable', Rule::in(array_keys(config('crm.countries')))],
            'qualification' => ['nullable', 'string', 'max:120', Rule::in($this->qualificationOptions()->all())],
            'qualification_year' => ['nullable', 'integer', 'min:1950', 'max:' . (date('Y') + 1)],
            'gap_years' => ['nullable', Rule::in(config('crm.gap_options'))],
            'score' => ['nullable', 'string', 'max:50'],
            'visa_type' => ['required', Rule::in(config('crm.visa_types'))],
            'country' => ['required', Rule::in(array_keys(config('crm.countries')))],
            'english_test' => ['nullable', Rule::in(['yes', 'no'])],
            'test_type' => ['nullable', 'string', 'max:255'],
            'listening' => ['nullable', 'numeric'],
            'reading' => ['nullable', 'numeric'],
            'writing' => ['nullable', 'numeric'],
            'speaking' => ['nullable', 'numeric'],
            'overall' => ['nullable', 'numeric'],
            'test_expiry' => ['nullable', 'date'],
            'previous_refusal' => ['nullable', Rule::in(['yes', 'no'])],
            'refusal_countries' => ['nullable', 'array'],
            'refusal_countries.*' => ['string', 'max:255'],
        ]);

        $validated['english_test'] = $validated['english_test'] ?? 'no';
        $validated['previous_refusal'] = $validated['previous_refusal'] ?? 'no';
        if ($validated['previous_refusal'] !== 'yes') {
            $validated['refusal_countries'] = [];
        }

        CustomerEntry::create($validated);

        return redirect()->route('public.customer-entry.thanks');
    }

    public function thanks()
    {
        return view('customer-entries.thanks');
    }

    private function qualificationOptions()
    {
        if (!Schema::hasTable('qualifications')) {
            return collect(['10th', '12th', 'graduated', 'master']);
        }

        return Qualification::active()->orderBy('name')->pluck('name');
    }
}
