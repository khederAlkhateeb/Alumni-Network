<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks any Sanctum token that carries a restricted ability (e.g. the
 * 'complete-profile' token issued to Google OAuth users with no profile
 * yet) from reaching any route that isn't explicitly allowed for it.
 *
 * This is a "default-deny" safety net: routes don't need to remember to
 * add `ability:*` themselves — a restricted token is blocked everywhere
 * except the routes it was actually issued for, even if a route is
 * added later and someone forgets to protect it individually.
 */
class EnsureTokenIsFullAccess
{
    /**
     * Abilities considered "restricted" — a token carrying ONLY one of
     * these (not a full-access '*' token) is limited to routes that
     * explicitly declare that same ability via the `ability:` middleware.
     */
    private const RESTRICTED_ABILITIES = [
        'complete-profile',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token) {
            return $next($request);
        }

        $tokenAbilities = $token->abilities ?? [];

        // A full-access token (created without a restricted ability,
        // i.e. it has the default '*' ability) is always allowed through.
        if (in_array('*', $tokenAbilities, true)) {
            return $next($request);
        }

        $hasRestrictedAbility = count(array_intersect($tokenAbilities, self::RESTRICTED_ABILITIES)) > 0;

        if ($hasRestrictedAbility) {
            abort(403, 'This token is restricted to completing your profile only.');
        }

        return $next($request);
    }
}
