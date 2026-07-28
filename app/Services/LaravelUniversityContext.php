<?php

namespace App\Services;

use App\Contracts\UniversityContext;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Cache;

class LaravelUniversityContext implements UniversityContext
{
    private ?int $universityId = null;
    private bool $resolved = false;

    // Pure Dependency Injection for the user
    public function __construct(private readonly Guard $auth) {}
    public function getUniversityId(): ?int
    {
        if ($this->isGuest()) {
            return null;
        }

        if ($this->resolved) {
            return $this->universityId;
        }

        $user = $this->auth->user();

        // Use Cache to prevent N+1 queries across different requests
        $this->universityId = Cache::remember("user_{$user->id}_university_id", 3600, function () use ($user) {
            $profile = $user->alumniProfile ?? $user->studentProfile;

            if (!$profile) {
                return null;
            }

            // Eager load only if not already loaded to avoid N+1 issues
            $profile->loadMissing('major.faculty');

            return $profile->major?->faculty?->university_id;
        });

        $this->resolved = true;

        return $this->universityId;
    }

    public function isSuperAdmin(): bool
    {
        $user = $this->auth->user();
        return $user && $user->hasRole('super_admin');
    }

    public function isGuest(): bool
    {
        return $this->auth->guest();
    }
}
