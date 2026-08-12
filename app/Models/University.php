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
#[Appends(['logo_url'])]
class University extends Model
{
    use HasFactory;
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'logo',
        'created_by',
        'updated_by',
    ];

    /**
     * Interact with the university logo asset URL.
     *
     * @return Attribute
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(
            fn() => $this->logo ? Storage::url($this->logo) : null
        );
    }

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
