<?php

namespace App\Models\Scopes;

use App\Contracts\UniversityContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Class UniversityScope
 *
 * An Eloquent global scope that automatically restricts query results to the
 * authenticated user's university. Bypassed for guests and super admins.
 */
class UniversityScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder  $builder
     * @param  Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(UniversityContext::class);

        if ($context->isGuest()) {
            return;
        }

        if ($context->isSuperAdmin()) {
            return;
        }

        $universityId = $context->getUniversityId();

        if ($universityId !== null) {
            $builder->where($model->getTable() . '.id', $universityId);
        } else {
            // Users without a university ID should not see any university data
            $builder->whereNull($model->getTable() . '.id');
        }
    }
}
