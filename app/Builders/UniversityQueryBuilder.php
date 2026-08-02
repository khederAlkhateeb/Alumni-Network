<?php

namespace App\Builders;

use App\Models\Scopes\UniversityScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * UniversityQueryBuilder
 *
 * A custom Eloquent builder for the University model.
 * Centralises all reusable query constraints so that Actions
 * remain responsible only for orchestration, not for building
 * query conditions.
 */
class UniversityQueryBuilder extends Builder
{
    /**
     * Filter universities by name (partial, case-insensitive).
     *
     * Only applies the constraint when $name is a non-empty string.
     *
     * @param  string|null $name
     * @return static
     */
    public function filterByName(?string $name): static
    {
        return $this->when(
            filled($name),
            fn (self $query) => $query->where('name', 'like', '%' . trim($name) . '%')
        );
    }

    /**
     * Filter universities by country (partial, case-insensitive).
     *
     * Only applies the constraint when $country is a non-empty string.
     *
     * @param  string|null $country
     * @return static
     */
    public function filterByCountry(?string $country): static
    {
        return $this->when(
            filled($country),
            fn (self $query) => $query->where('country', 'like', '%' . trim($country) . '%')
        );
    }

    /**
     * Remove the UniversityScope global scope.
     *
     * Use on public or super-admin endpoints where tenant isolation
     * should not apply.
     *
     * @return static
     */
    public function withoutTenantScope(): static
    {
        return $this->withoutGlobalScope(UniversityScope::class);
    }
}
