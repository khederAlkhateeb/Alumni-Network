<?php

namespace App\Models;

use App\Builders\UniversityQueryBuilder;
use App\Models\Scopes\UniversityScope;
use App\Policies\UniversityPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder ;

#[Fillable([
    'name',
    'country',
    'website',
    'logo',
    'created_by',
    'updated_by',
])]
#[UsePolicy(UniversityPolicy::class)]
#[ScopedBy(UniversityScope::class)]
class University extends Model
{
    use HasFactory, SoftDeletes;

    public function newEloquentBuilder($query): UniversityQueryBuilder
    {
        return new UniversityQueryBuilder($query);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function faculties(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }



}
