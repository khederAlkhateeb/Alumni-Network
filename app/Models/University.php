<?php

namespace App\Models;

use App\Builders\UniversityQueryBuilder;
use App\Models\Scopes\UniversityScope;
use App\Policies\UniversityPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

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
    use HasFactory;


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

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
    /**
     * Get all events belonging to this university.
     *
     * @return HasMany<Event>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get all mentorship programs belonging to this university.
     *
     * @return HasMany
     */
    public function mentorshipPrograms(): HasMany
    {
        return $this->hasMany(MentorshipProgram::class);
    }
}
