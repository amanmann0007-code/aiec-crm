<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Document;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class WebDocumentController extends Controller
{
    public function store(Request $request, Customer $customer)
    {
        $this->authorizeCustomerAccess($customer);

        $request->validate([
            'document_name' => ['required', 'string', 'max:255'],
            'documents' => 'required|array',
            'documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:20480',
        ]);

        $documentName = trim($request->input('document_name'));

        foreach ($request->file('documents') as $file) {
            $path = $file->store("uploads/customers/{$customer->id}/documents", 'public');
            Document::create([
                'customer_id' => $customer->id,
                'uploaded_by' => Auth::id(),
                'document_name' => $documentName,
                'file_path' => $path,
            ]);
            ActivityLogger::log(Auth::id(), 'UPLOAD_DOCUMENT', "Uploaded {$documentName} on {$customer->activitySummary()}", $customer->id);
        }

        return back()->with('success', 'Documents uploaded.');
    }

    public function show(Document $document)
    {
        $customer = $document->customer;

        if (!$customer) {
            abort(404);
        }

        $this->authorizeCustomerAccess($customer);

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('public')->response($document->file_path, $document->document_name);
    }

    public function destroy(Document $document)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Only admin can delete documents.');
        }

        $customer = $document->customer;
        Storage::disk('public')->delete($document->file_path);
        ActivityLogger::log(
            Auth::id(),
            'DELETE_DOCUMENT',
            "Deleted {$document->document_name} from " . ($customer ? $customer->activitySummary() : 'customer'),
            $document->customer_id
        );
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }

    private function authorizeCustomerAccess(Customer $customer): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        if (in_array($user->role, ['admin', 'director', 'receptionist'], true)) {
            return;
        }

        if ($user->role === 'counselor' && $customer->assigned_counselor_id === $user->id) {
            return;
        }

        if ($user->role === 'telecaller' && $customer->telecaller_id === $user->id) {
            return;
        }

        if ($user->role === 'agent' && $this->agentHasAccess($customer)) {
            return;
        }

        abort(403);
    }

    private function agentHasAccess(Customer $customer): bool
    {
        $user = Auth::user();

        return $user && $user->role === 'agent'
            && ((int) $customer->agent_id === (int) $user->id
                || $customer->collaborators()->whereKey($user->id)->exists());
    }
}
