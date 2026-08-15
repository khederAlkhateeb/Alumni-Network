<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);
            return $this->applySecurityHeaders($request, $response);
        }

        $origin = $request->headers->get('Origin');

        if ($origin !== null && ! in_array($origin, $this->allowedOrigins(), true)) {
            $this->logRejectedOrigin($origin, $request);

            return response()->json([
                'error' => 'CORS origin not allowed',
                'origin' => $origin,
            ], 403);
        }

        $response = $next($request);

        return $this->applySecurityHeaders($request, $response);
    }

    private function applySecurityHeaders(Request $request, Response $response): Response
    {
        $origin = $request->headers->get('Origin');
        $nonce = base64_encode(random_bytes(16));

        if ($origin !== null && in_array($origin, $this->allowedOrigins(), true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Vary', 'Origin');
            $response->headers->set('X-Request-Id', (string) str()->uuid());
        } elseif ($origin === null) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } else {
            $response->headers->set('Access-Control-Allow-Origin', 'null');
            $response->headers->set('Vary', 'Origin');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Accept');
        $response->headers->set('Access-Control-Max-Age', '86400');
        $response->headers->set('Access-Control-Expose-Headers', 'X-Request-Id, Authorization');

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; img-src 'self' data: https:; style-src 'self' 'nonce-{$nonce}'; script-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; connect-src 'self' https:"
        );

        $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');

        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        if (app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }

    private function allowedOrigins(): array
    {
        $raw = config('cors.allowed_origins', '');

        if ($raw !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $raw))));
        }

        return array_values(array_filter([
            config('app.url', 'http://localhost'),
            'http://localhost:3000',
            'http://localhost:5173',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:5173',
        ]));
    }

    private function logRejectedOrigin(string $origin, Request $request): void
    {
        $line = json_encode([
            'time' => now()->toDateTimeString(),
            'origin' => $origin,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], JSON_UNESCAPED_SLASHES) . PHP_EOL;

        file_put_contents(
            storage_path('logs/rejected-origins.log'),
            $line,
            FILE_APPEND | LOCK_EX
        );
    }
}
