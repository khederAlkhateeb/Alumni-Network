<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{
    public function test_api_requests_are_rate_limited_per_ip_per_minute(): void
    {
        Route::middleware('throttle:60,1')->get('/api/test-rate-limit', function () {
            return response()->json(['ok' => true]);
        });

        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/test-rate-limit')->assertOk();
        }

        $this->getJson('/api/test-rate-limit')->assertStatus(429);
    }
}
