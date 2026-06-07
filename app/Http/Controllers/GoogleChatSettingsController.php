<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\GoogleChatNotificationSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GoogleChatSettingsController extends Controller
{
    public function edit()
    {
        return view('settings.google-chat', [
            'options' => GoogleChatNotificationSettings::options(),
            'enabledTypes' => GoogleChatNotificationSettings::enabledTypes(),
        ]);
    }

    public function update(Request $request)
    {
        $validTypes = array_keys(GoogleChatNotificationSettings::options());

        $validated = $request->validate([
            'enabled_types' => ['nullable', 'array'],
            'enabled_types.*' => ['string', Rule::in($validTypes)],
        ]);

        GoogleChatNotificationSettings::saveEnabledTypes($validated['enabled_types'] ?? []);

        ActivityLogger::log(Auth::id(), 'UPDATE_GOOGLE_CHAT_SETTINGS', 'Updated Google Chat notification settings.');

        return redirect()->route('google-chat-settings.edit')->with('success', 'Google Chat notification settings saved.');
    }
}
