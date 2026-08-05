<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\ProfileStatus;

class EnsureProfileIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

            $profile = $user->alumniProfile ?? $user->studentProfile;

        if ($profile && $profile->status !== ProfileStatus::ACTIVE) {
            return response()->json([
                'message' => 'Your profile is not active yet.',
                'status' => $profile->status,
            ], Response::HTTP_FORBIDDEN); // 403 Forbidden
        }

        return $next($request);
    }
}
