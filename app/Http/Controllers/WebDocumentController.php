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
}
