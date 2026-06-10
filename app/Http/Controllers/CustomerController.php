<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\StoreTelecallerCustomerRequest;
use App\Mail\WelcomeMail;
use App\Models\Customer;
use App\Models\CustomerRefusal;
use App\Models\Document;
use App\Models\Notification;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\PidGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    private $statusNeedsFollowUp = ['interested', 'wv again', 'pursuing ielts/pte', 'arranging docs', 'in process'];

    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();

        if (($validated['source'] ?? null) === 'Reference' && empty($validated['reference_name'])) {
            return response()->json(['message' => 'reference_name is required when source is Reference'], 422);
        }
        if (($validated['source'] ?? null) === 'Telecaller' && empty($validated['telecaller_id'])) {
            return response()->json(['message' => 'telecaller_id is required when source is Telecaller'], 422);
        }

        $customer = DB::transaction(function () use ($validated, $request) {
            $validated['pid'] = PidGenerator::next();
            $validated['created_by'] = optional($request->user())->id;
            $validated['status'] = $validated['status'] ?? 'assigned';
            $validated['english_test'] = $validated['english_test'] ?? 'no';
            $validated['previous_refusal'] = $validated['previous_refusal'] ?? 'no';

            $customer = Customer::create($validated);

            if (($validated['previous_refusal'] ?? 'no') === 'yes' && !empty($validated['refusal_countries'])) {
                foreach ($validated['refusal_countries'] as $country) {
                    CustomerRefusal::create([
                        'customer_id' => $customer->id,
                        'country' => $country,
                    ]);
                }
            }

            $assigneeIds = collect([
                $validated['assigned_counselor_id'] ?? null,
                $validated['telecaller_id'] ?? null,
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

            ActivityLogger::log(optional($request->user())->id, 'ADD_CUSTOMER', 'Added customer ' . $customer->activitySummary(), $customer->id);

            return $customer;
        });

        if ($customer->email) {
            Mail::to($customer->email)->send(new WelcomeMail($customer->load('counselor')));
        }

        return response()->json($customer, 201);
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

        return response()->json($customer, 201);
    }

    public function uploadDocuments(Request $request, Customer $customer)
    {
        $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'file|max:20480',
        ]);

        $saved = [];
        foreach ($request->file('documents') as $file) {
            $path = $file->store("uploads/customers/{$customer->id}/documents", 'public');
            $saved[] = Document::create([
                'customer_id' => $customer->id,
                'uploaded_by' => $request->user()->id,
                'document_name' => $file->getClientOriginalName(),
                'file_path' => $path,
            ]);
            ActivityLogger::log($request->user()->id, 'UPLOAD_DOCUMENT', "Uploaded {$file->getClientOriginalName()} on {$customer->activitySummary()}", $customer->id);
        }

        return response()->json($saved);
    }

    public function deleteDocument(Request $request, Document $document)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Only admin can delete documents.');
        }

        Storage::disk('public')->delete($document->file_path);
        $customer = $document->customer;
        ActivityLogger::log(
            $request->user()->id,
            'DELETE_DOCUMENT',
            "Deleted {$document->document_name} from " . ($customer ? $customer->activitySummary() : 'customer'),
            $document->customer_id
        );
        $document->delete();

        return response()->json(['message' => 'Document deleted']);
    }
}
