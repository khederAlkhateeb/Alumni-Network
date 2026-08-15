<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiPerformanceLogger
{
    /**
     * Measure request duration and log each API call to a dedicated file.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        try {
            $response = $next($request);
            return $response;
        } finally {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $logData = [
                'time' => now()->toDateTimeString(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'status' => method_exists($request, 'route') && $request->route() ? $request->route()->getActionName() : null,
                'response_status' => optional($request->route())->getActionMethod(),
                'duration_ms' => $duration,
            ];

            $logLine = json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            file_put_contents(
                storage_path('logs/api-performance.log'),
                $logLine,
                FILE_APPEND | LOCK_EX
            );
        }
    }
}
