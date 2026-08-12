<?php

namespace App\Models;

use App\Builders\AlumniProfileBuilder;
use App\Enums\ProfileStatus;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Class AlumniProfile
 *
 * Represents an alumni's professional profile, including academic info,
 * career history, skills, and profile completeness metrics.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $major_id
 * @property string|null $student_number
 * @property int|null $graduation_year
 * @property string|null $current_job_title
 * @property string|null $current_company
 * @property string|null $linkedin_url
 * @property string|null $bio
 * @property string|null $city
 * @property string|null $country
 * @property ProfileStatus $status
 * @property bool $is_open_to_mentor
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read float $total_experience_years
 * @property-read bool $is_currently_employed
 * @property-read WorkExperience|null $current_work_experience
 * @property-read int $completeness_score
 * @property-read array $missing_fields
 *
 * @property-read User $user
 * @property-read Major|null $major
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WorkExperience> $workExperiences
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Skill> $skills
 * @property-read Attachment|null $photo
 *
 * @method static AlumniProfileBuilder query()
 *
 * @package App\Models
 */
#[Fillable([
    'user_id',
    'major_id',
    'student_number',
    'graduation_year',
    'current_job_title',
    'current_company',
    'linkedin_url',
    'bio',
    'city',
    'country',
    'status',
    'is_open_to_mentor',
])]
#[Appends([
    'total_experience_years',
    'is_currently_employed',
    'completeness_score',
    'missing_fields',
])]

class AlumniProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'student_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProfileStatus::class,
            'is_open_to_mentor' => 'boolean',
            'graduation_year' => 'integer',
        ];
    }

    /************************ Relationships ************************** */

    /**
     * Get the user that owns the alumni profile.
     *
     * @return BelongsTo<User, AlumniProfile>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the academic major associated with the alumni.
     *
     * @return BelongsTo<Major, AlumniProfile>
     */
    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    /**
     * Get all work experiences for the alumni profile sorted by most recent.
     *
     * @return HasMany<WorkExperience>
     */
    public function workExperiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class)->orderByDesc('start_date');
    }

    /**
     * The skills that belong to the alumni profile.
     *
     * @return BelongsToMany<Skill>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'alumni_profile_skill');
    }

    /**
     * Get the alumni profile's latest uploaded photo attachment.
     *
     * @return MorphOne<Attachment>
     */
    public function photo(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->latestOfMany();
    }

    /************************ Accessors & Mutators ************************** */

    /**
     * Accessor for total experience in years rounded to one decimal place.
     *
     * @return Attribute<float, void>
     */
    protected function totalExperienceYears(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->relationLoaded('workExperiences')) {
                return 0.0;
            }

            $totalMonths = $this->workExperiences->sum(function (WorkExperience $exp) {
                $end = $exp->end_date ?? now();

                return $exp->start_date->diffInMonths($end);
            });

            return round($totalMonths / 12, 1);
        });
    }

    /**
     * Accessor to determine if the alumni is currently employed.
     *
     * @return Attribute<bool, void>
     */
    protected function isCurrentlyEmployed(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->relationLoaded('workExperiences')) {
                return false;
            }

            return $this->workExperiences->contains(fn(WorkExperience $exp) => is_null($exp->end_date));
        });
    }

    /**
     * Accessor for profile completeness percentage score based on configured weights.
     *
     * @return Attribute<int, void>
     */
    protected function completenessScore(): Attribute
    {
        return Attribute::get(function () {
            $weights = config('profile_scoring.weights', []);

            return collect($this->completionMap())
                ->filter()
                ->sum(fn($_, $field) => $weights[$field] ?? 0);
        });
    }

    /**
     * Accessor for missing fields along with their potential weight points.
     *
     * @return Attribute<array<int, array{field: string, points: int}>, void>
     */
    protected function missingFields(): Attribute
    {
        return Attribute::get(function () {
            $weights = config('profile_scoring.weights', []);

            return collect($this->completionMap())
                ->reject()
                ->map(fn($_, $field) => [
                    'field' => $field,
                    'points' => $weights[$field] ?? 0,
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Map profile completion state for fields and relations.
     *
     * Handles N+1 prevention by checking if relations are preloaded before querying.
     *
     * @return array<string, bool>
     */
    private function completionMap(): array
    {
        return [
            'bio' => ! empty($this->bio),
            'linkedin_url' => ! empty($this->linkedin_url),
            'city' => ! empty($this->city),
            'country' => ! empty($this->country),
            'current_job_title' => ! empty($this->current_job_title),
            'current_company' => ! empty($this->current_company),
            'has_at_least_one_skill' => $this->relationLoaded('skills') && $this->skills->isNotEmpty(),
        ];
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return AlumniProfileBuilder
     */
    public function newEloquentBuilder($query): AlumniProfileBuilder
    {
        return new AlumniProfileBuilder($query);
    }
}
