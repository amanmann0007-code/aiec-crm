<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;

class GoogleChatNotificationSettings
{
    private const KEY = 'google_chat_notification_types';

    public static function options(): array
    {
        return config('crm.google_chat_notification_types', []);
    }

    public static function enabledTypes(): array
    {
        $defaults = array_keys(self::options());

        if (!Schema::hasTable('app_settings')) {
            return $defaults;
        }

        $setting = AppSetting::where('key', self::KEY)->first();
        if (!$setting) {
            return $defaults;
        }

        $types = json_decode((string) $setting->value, true);
        if (!is_array($types)) {
            return $defaults;
        }

        return array_values(array_intersect($types, $defaults));
    }

    public static function isEnabled(string $type): bool
    {
        return in_array($type, self::enabledTypes(), true);
    }

    public static function saveEnabledTypes(array $types): void
    {
        $validTypes = array_keys(self::options());
        $enabledTypes = array_values(array_intersect($types, $validTypes));

        AppSetting::updateOrCreate(
            ['key' => self::KEY],
            ['value' => json_encode($enabledTypes)]
        );
    }
}
