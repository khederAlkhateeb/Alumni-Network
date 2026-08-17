<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SecurityMiddleware
 *
 * Middleware responsible for enforcing Cross-Origin Resource Sharing (CORS) rules,
 * setting strict security HTTP headers, and logging unauthorized origins.
 */
class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Preflight OPTIONS requests handling
        if ($request->isMethod('OPTIONS')) {
            $response = response('', Response::HTTP_OK);
            return $this->applySecurityHeaders($request, $response);
        }

        $origin = $request->headers->get('Origin');

        // Reject requests from non-allowed origins
        if ($origin !== null && ! in_array($origin, $this->allowedOrigins(), true)) {
            $this->logRejectedOrigin($origin, $request);

            return response()->json([
                'error'  => 'CORS origin not allowed',
                'origin' => $origin,
            ], Response::HTTP_FORBIDDEN);
        }

        $response = $next($request);

        return $this->applySecurityHeaders($request, $response);
    }

    /**
     * Apply security and CORS headers to the outgoing response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function applySecurityHeaders(Request $request, Response $response): Response
    {
        $origin = $request->headers->get('Origin');
        $nonce = base64_encode(random_bytes(16));

        // CORS Headers Configuration
        if ($origin !== null && in_array($origin, $this->allowedOrigins(), true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Vary', 'Origin');
        }

        $requestId = $request->headers->get('X-Request-Id') ?? (string) str()->uuid();
        $response->headers->set('X-Request-Id', $requestId);

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Accept');
        $response->headers->set('Access-Control-Max-Age', '86400');
        $response->headers->set('Access-Control-Expose-Headers', 'X-Request-Id, Authorization');

        // Content Security Policy
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; img-src 'self' data: https:; style-src 'self' 'nonce-{$nonce}'; script-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; connect-src 'self' https:"
        );

        // Cache Control
        $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        // Hardening Security Headers
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // Server Information Disclosure Removal
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

    /**
     * Get the list of allowed CORS origins from configuration or defaults.
     *
     * @return array<int, string>
     */
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

    /**
     * Log rejected CORS origin attempts.
     *
     * @param  string  $origin
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    private function logRejectedOrigin(string $origin, Request $request): void
    {
        Log::channel('single')->warning('Rejected CORS Origin Attempt', [
            'origin'     => $origin,
            'method'     => $request->method(),
            'path'       => $request->path(),
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}