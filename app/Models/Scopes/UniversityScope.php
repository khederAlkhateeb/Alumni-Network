<?php

namespace App\Models\Scopes;

use App\Contracts\UniversityContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UniversityScope implements Scope
{
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
