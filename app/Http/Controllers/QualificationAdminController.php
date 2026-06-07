<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Qualification;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class QualificationAdminController extends Controller
{
    public function index()
    {
        return view('qualifications.index', [
            'qualifications' => Qualification::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('qualifications', 'name')],
        ]);

        $qualification = Qualification::create([
            'name' => trim($validated['name']),
            'is_active' => true,
        ]);

        ActivityLogger::log(Auth::id(), 'CREATE_QUALIFICATION', 'Created qualification ' . $qualification->name);

        return redirect()->route('qualifications.index')->with('success', 'Qualification added.');
    }

    public function destroy(Qualification $qualification)
    {
        if (Customer::where('qualification', $qualification->name)->exists()) {
            return back()->withErrors(['qualification' => 'This qualification is used by customers and cannot be removed.']);
        }

        $name = $qualification->name;
        $qualification->delete();

        ActivityLogger::log(Auth::id(), 'DELETE_QUALIFICATION', 'Deleted qualification ' . $name);

        return redirect()->route('qualifications.index')->with('success', 'Qualification removed.');
    }
}
