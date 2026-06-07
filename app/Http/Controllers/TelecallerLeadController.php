<?php

namespace App\Http\Controllers;

use App\Models\TelecallerLead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TelecallerLeadController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            TelecallerLead::where('telecaller_id', $request->user()->id)->latest('id')->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'country' => 'required|string|max:255',
            'visa_type' => 'required|string|max:255',
            'status' => ['required', Rule::in(config('crm.telecaller_statuses', []))],
        ]);

        $validated['telecaller_id'] = $request->user()->id;
        return response()->json(TelecallerLead::create($validated), 201);
    }
}
