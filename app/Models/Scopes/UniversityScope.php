<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UniversityScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->guest()) {
            return;
        }

        $user = auth()->user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        $universityId = $this->resolveUniversityId($user);

        if ($universityId) {
            $builder->where('id', $universityId);
        }
    }

    private function resolveUniversityId($user): ?int
    {
        $profile = $user->alumniProfile ?? $user->studentProfile;

        if (!$profile) {
            return null;
        }

        $profile->loadMissing('major.faculty');

        return $profile->major?->faculty?->university_id;
    }
}
