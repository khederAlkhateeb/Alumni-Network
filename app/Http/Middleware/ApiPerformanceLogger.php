<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiPerformanceLogger
 *
 * Middleware responsible for monitoring and logging performance metrics
 * (execution time, memory usage, and database queries) for incoming API requests.
 */
class ApiPerformanceLogger
{
    /**
     * Threshold in milliseconds to consider a request as "slow".
     */
    private const SLOW_THRESHOLD_MS = 500;

    /**
     * Handle an incoming HTTP request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Enable database query logging to capture query metrics
        DB::enableQueryLog();

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        try {
            $response = $next($request);
        } finally {
            // Calculate performance metrics
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $memoryUsed = round((memory_get_usage() - $startMemory) / 1024 / 1024, 2);
            $peakMemory = round(memory_get_peak_usage() / 1024 / 1024, 2);

            // Extract query metrics
            $queries = DB::getQueryLog();
            $queriesCount = count($queries);
            $queriesTime = array_sum(array_column($queries, 'time'));

            // Log if request execution exceeds threshold or if running in local/testing environment
            $shouldLog = $duration >= self::SLOW_THRESHOLD_MS || app()->environment('local', 'testing');

            if ($shouldLog) {
                Log::channel('performance')->info('API Request', [
                    'timestamp'      => now()->toDateTimeString(),
                    'method'         => $request->method(),
                    'url'            => $request->fullUrl(),
                    'path'           => $request->path(),
                    'ip'             => $request->ip(),
                    'user_id'        => $request->user()?->id,
                    'user_agent'     => $request->userAgent(),
                    'controller'     => $request->route()?->getActionName(),
                    'http_status'    => $response->getStatusCode(),
                    'duration_ms'    => $duration,
                    'memory_used_mb' => $memoryUsed,
                    'peak_memory_mb' => $peakMemory,
                    'queries_count'  => $queriesCount,
                    'queries_time_ms'=> round($queriesTime, 2),
                ]);
            }

            // Clean up query log memory
            DB::disableQueryLog();
        }

        return $response;
    }
}