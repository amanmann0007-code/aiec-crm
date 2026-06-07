<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleCors
{
    public function handle(Request $request, Closure $next)
    {
        if (!$this->matchesConfiguredPath($request)) {
            return $next($request);
        }

        $response = $request->isMethod('OPTIONS')
            ? response('', 204)
            : $next($request);

        return $this->addCorsHeaders($request, $response);
    }

    private function matchesConfiguredPath(Request $request): bool
    {
        foreach (config('cors.paths', []) as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }

    private function addCorsHeaders(Request $request, $response)
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = config('cors.allowed_origins', ['*']);
        $supportsCredentials = (bool) config('cors.supports_credentials', false);

        if (in_array('*', $allowedOrigins, true) && !$supportsCredentials) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } elseif ($origin && in_array($origin, $allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }

        if ($supportsCredentials) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        $response->headers->set('Access-Control-Allow-Methods', $this->headerList(
            config('cors.allowed_methods', ['*']),
            'GET, POST, PUT, PATCH, DELETE, OPTIONS'
        ));

        $response->headers->set('Access-Control-Allow-Headers', $this->headerList(
            config('cors.allowed_headers', ['*']),
            $request->headers->get('Access-Control-Request-Headers', '*')
        ));

        $exposedHeaders = config('cors.exposed_headers', []);
        if (!empty($exposedHeaders)) {
            $response->headers->set('Access-Control-Expose-Headers', implode(', ', $exposedHeaders));
        }

        $maxAge = (int) config('cors.max_age', 0);
        if ($maxAge > 0) {
            $response->headers->set('Access-Control-Max-Age', (string) $maxAge);
        }

        return $response;
    }

    private function headerList(array $configured, string $fallback): string
    {
        if (in_array('*', $configured, true)) {
            return $fallback;
        }

        return implode(', ', $configured);
    }
}
