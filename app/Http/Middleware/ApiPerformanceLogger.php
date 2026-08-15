<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiPerformanceLogger
{
    /**
     * Logs only slow API requests to reduce noise and keep performance monitoring useful.
     */
    public function handle(Request $request, Closure $next): Response
    {
        DB::enableQueryLog();

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        try {
            $response = $next($request);
        } finally {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $memoryUsed = round((memory_get_usage() - $startMemory) / 1024 / 1024, 2);
            $peakMemory = round(memory_get_peak_usage() / 1024 / 1024, 2);

            $queries = DB::getQueryLog();
            $queriesCount = count($queries);
            $queriesTime = array_sum(array_column($queries, 'time'));

            $shouldLog = $duration >= 500 || app()->environment('local', 'testing');

            if ($shouldLog) {
                Log::channel('performance')->info('API Request', [
                    'timestamp' => now()->toDateTimeString(),
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                    'user_id' => $request->user()?->id,
                    'user_agent' => $request->userAgent(),
                    'controller' => $request->route()?->getActionName(),
                    'http_status' => $response->getStatusCode(),
                    'duration_ms' => $duration,
                    'memory_used_mb' => $memoryUsed,
                    'peak_memory_mb' => $peakMemory,
                    'queries_count' => $queriesCount,
                    'queries_time_ms' => round($queriesTime, 2),
                ]);
            }

            DB::disableQueryLog();
        }

        return $response;
    }
}