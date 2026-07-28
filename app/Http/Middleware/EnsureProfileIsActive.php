<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

namespace App\Http\Middleware;

use App\Enums\ProfileStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
           if ($user->alumniProfile->status !== ProfileStatus::ACTIVE) {
            return response()->json([
                'message' => 'Your alumni profile is not active yet.',
                'status' => $user->alumniProfile->status,
            ], Response::HTTP_FORBIDDEN); // 403 Forbidden
        }

        return $next($request);
    }
}
