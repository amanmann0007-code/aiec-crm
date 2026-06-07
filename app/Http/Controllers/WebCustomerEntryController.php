<?php

namespace App\Http\Controllers;

use App\Models\CustomerEntry;

class WebCustomerEntryController extends Controller
{
    public function index()
    {
        $entries = CustomerEntry::whereNull('converted_at')
            ->latest('id')
            ->paginate(15);

        return view('customer-entries.index', compact('entries'));
    }
}
