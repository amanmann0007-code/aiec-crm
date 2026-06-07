<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function customers(Request $request)
    {
        $q = $request->query('q', '');
        if (trim($q) === '') {
            return response()->json([]);
        }

        $deep = $request->boolean('deep');
        $term = trim($q);
        $like = "%{$term}%";

        $query = Customer::query();

        $user = $request->user();
        if ($user->role === 'counselor') {
            $query->where('assigned_counselor_id', $user->id);
        } elseif ($user->role === 'telecaller') {
            $query->where('telecaller_id', $user->id);
        }

        $query->where(function ($builder) use ($deep, $like) {
            $builder->where('name', 'LIKE', $like)
                ->orWhere('phone', 'LIKE', $like)
                ->orWhere('pid', 'LIKE', $like);

            if ($deep) {
                $builder->orWhere('email', 'LIKE', $like)
                    ->orWhere('status', 'LIKE', $like)
                    ->orWhere('visa_type', 'LIKE', $like)
                    ->orWhere('country', 'LIKE', $like)
                    ->orWhere('source', 'LIKE', $like)
                    ->orWhere('reference_name', 'LIKE', $like)
                    ->orWhere('qualification', 'LIKE', $like)
                    ->orWhere('score', 'LIKE', $like)
                    ->orWhere('dob', 'LIKE', $like)
                    ->orWhere('gender', 'LIKE', $like)
                    ->orWhere('marital_status', 'LIKE', $like)
                    ->orWhere('residence_country', 'LIKE', $like)
                    ->orWhere('qualification_year', 'LIKE', $like)
                    ->orWhere('gap_years', 'LIKE', $like)
                    ->orWhere('english_test', 'LIKE', $like)
                    ->orWhere('english_exam', 'LIKE', $like)
                    ->orWhere('test_type', 'LIKE', $like)
                    ->orWhere('listening', 'LIKE', $like)
                    ->orWhere('reading', 'LIKE', $like)
                    ->orWhere('writing', 'LIKE', $like)
                    ->orWhere('speaking', 'LIKE', $like)
                    ->orWhere('overall', 'LIKE', $like)
                    ->orWhere('test_expiry', 'LIKE', $like)
                    ->orWhere('previous_refusal', 'LIKE', $like)
                    ->orWhere('father_spouse_name', 'LIKE', $like)
                    ->orWhereHas('remarks', function ($remarkQuery) use ($like) {
                        $remarkQuery->where(function ($query) use ($like) {
                            $query->where('message', 'LIKE', $like)
                                ->orWhere('status_update', 'LIKE', $like);
                        });
                    })
                    ->orWhereHas('processSteps', function ($stepQuery) use ($like) {
                        $stepQuery->where(function ($query) use ($like) {
                            $query->where('step_label', 'LIKE', $like)
                                ->orWhere('step_key', 'LIKE', $like)
                                ->orWhere('visa_type', 'LIKE', $like);
                        });
                    });
            }
        });

        $customers = $query
            ->latest('id')
            ->limit(20)
            ->get(['id', 'pid', 'name', 'phone', 'status', 'country', 'visa_type', 'email', 'source', 'reference_name', 'qualification', 'score', 'dob', 'gender', 'marital_status', 'residence_country', 'qualification_year', 'gap_years', 'english_test', 'english_exam', 'test_type', 'listening', 'reading', 'writing', 'speaking', 'overall', 'test_expiry', 'previous_refusal', 'father_spouse_name']);

        if ($deep) {
            $customers->load([
                'remarks' => function ($remarkQuery) use ($like) {
                    $remarkQuery->where(function ($query) use ($like) {
                        $query->where('message', 'LIKE', $like)
                            ->orWhere('status_update', 'LIKE', $like);
                    })
                        ->latest('id');
                },
                'processSteps' => function ($stepQuery) use ($like) {
                    $stepQuery->where(function ($query) use ($like) {
                        $query->where('step_label', 'LIKE', $like)
                            ->orWhere('step_key', 'LIKE', $like)
                            ->orWhere('visa_type', 'LIKE', $like);
                    })
                        ->orderBy('step_order');
                },
            ]);
        }

        return response()->json($customers->map(function (Customer $customer) use ($deep, $term) {
            $matches = $deep ? $this->searchMatches($customer, $term) : [];

            return [
                'id' => $customer->id,
                'pid' => $customer->pid,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'status' => $customer->status,
                'country' => $customer->country,
                'visa_type' => $customer->visa_type,
                'matches' => $matches,
            ];
        })->values());
    }

    private function searchMatches(Customer $customer, string $term): array
    {
        $matches = [];
        $needle = strtolower($term);

        $fields = [
            'PID' => $customer->pid,
            'Name' => $customer->name,
            'Phone' => $customer->phone,
            'Email' => $customer->email,
            'Status' => $customer->status,
            'Country' => $customer->country,
            'Visa Type' => $customer->visa_type,
            'Source' => $customer->source,
            'Reference' => $customer->reference_name,
            'Qualification' => $customer->qualification,
            'Score' => $customer->score,
            'DOB' => $customer->dob,
            'Gender' => $customer->gender,
            'Marital Status' => $customer->marital_status,
            'Residence Country' => $customer->residence_country,
            'Qualification Year' => $customer->qualification_year,
            'Gap Years' => $customer->gap_years,
            'English Test' => $customer->english_test,
            'English Exam' => $customer->english_exam,
            'Test Type' => $customer->test_type,
            'Listening' => $customer->listening,
            'Reading' => $customer->reading,
            'Writing' => $customer->writing,
            'Speaking' => $customer->speaking,
            'Overall' => $customer->overall,
            'Test Expiry' => $customer->test_expiry,
            'Previous Refusal' => $customer->previous_refusal,
            'Father/Spouse' => $customer->father_spouse_name,
        ];

        foreach ($fields as $label => $value) {
            if ($value !== null && stripos((string) $value, $needle) !== false) {
                $matches[] = "{$label}: {$value}";
            }
        }

        foreach ($customer->remarks as $remark) {
            if ($remark->message && stripos($remark->message, $needle) !== false) {
                $matches[] = 'Remark: ' . $this->shortMatch($remark->message, $term);
            }

            if ($remark->status_update && stripos($remark->status_update, $needle) !== false) {
                $matches[] = "Remark status: {$remark->status_update}";
            }
        }

        foreach ($customer->processSteps as $step) {
            $stepText = trim("{$step->step_label} {$step->step_key} {$step->visa_type}");
            if ($stepText !== '' && stripos($stepText, $needle) !== false) {
                $matches[] = "Process: {$step->step_label}";
            }
        }

        return array_slice(array_values(array_unique($matches)), 0, 4);
    }

    private function shortMatch(string $text, string $term): string
    {
        $position = stripos($text, $term);
        if ($position === false) {
            return substr($text, 0, 90) . (strlen($text) > 90 ? '...' : '');
        }

        $start = max(0, $position - 30);
        $snippet = substr($text, $start, 100);

        return ($start > 0 ? '...' : '') . $snippet . (strlen($text) > $start + 100 ? '...' : '');
    }
}
