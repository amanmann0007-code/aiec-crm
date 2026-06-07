<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleChatNotifier
{
    public static function send($message, string $type = 'activity_logs')
    {
        if (!GoogleChatNotificationSettings::isEnabled($type)) {
            return false;
        }

        $app = app();

        if (!$app->runningInConsole()) {
            /** @var \Illuminate\Foundation\Application $app */
            $app->terminating(function () use ($message) {
                self::sendNow($message);
            });

            return true;
        }

        return self::sendNow($message);
    }

    private static function sendNow($message)
    {
        $webhook = trim((string) config('services.google_chat.webhook'));
        $verifySsl = filter_var(config('services.google_chat.verify_ssl', false), FILTER_VALIDATE_BOOLEAN);

        if (!$webhook) {
            Log::warning('Google Chat notification skipped: GOOGLE_CHAT_WEBHOOK is not configured.');
            return false;
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->withOptions([
                    'connect_timeout' => 1,
                    'timeout' => 3,
                    'verify' => $verifySsl,
                ])
                ->post($webhook, [
                    'text' => trim((string) $message),
                ]);

            if ($response->failed()) {
                Log::error('Google Chat notification failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Google Chat notification exception: ' . $e->getMessage(), [
                'exception' => get_class($e),
            ]);

            return false;
        }
    }
}
