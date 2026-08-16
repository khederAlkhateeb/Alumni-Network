<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\ProfileStatus;

/**
 * Middleware to ensure the authenticated user's profile is active or incomplete.
 */
class EnsureProfileIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $profile = $user?->alumniProfile ?? $user?->studentProfile;

        if ($profile && $profile->status !== ProfileStatus::ACTIVE && $profile->status !== ProfileStatus::INCOMPLETE) {
            return response()->json([
                'message' => 'Your profile is not active yet.',
                'status'  => $profile->status,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
