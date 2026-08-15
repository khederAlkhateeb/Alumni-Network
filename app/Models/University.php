<?php

namespace App\Models;

use App\Builders\UniversityQueryBuilder;
use App\Models\AlumniProfile;
use App\Models\MentorshipProgram;
use App\Models\Scopes\UniversityScope;
use App\Models\StudentProfile;
use App\Policies\UniversityPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'name',
    'country',
    'website',
    'logo',
    'created_by',
    'updated_by',
])]
#[Hidden(['created_at', 'updated_at'])]
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


    // Attributes
    public function getPendingApprovalsAttribute():int {
        $studentsCount = StudentProfile::sameUniversityAs($this->id)->pending()->count();
        $alumniCount = AlumniProfile::sameUniversityAs($this->id)->pending()->count();

        return $studentsCount + $alumniCount ;
    }
}
