<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Skill
 *
 * Represents a skill entity that can be associated with multiple alumni profiles.
 * Features automatic string normalization on mutation for consistent storage.
 *
 * @property int $id
 * @property string $name
 * @property string|null $category
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AlumniProfile> $alumniProfiles
 *
 * @package App\Models
 */
#[Fillable([
    'name',
    'category',
])]
#[Hidden(['created_at', 'updated_at'])]
class Skill extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /************************ Relationships ************************** */

    /**
     * The alumni profiles that belong to the skill.
     *
     * @return BelongsToMany<AlumniProfile>
     */
    public function alumniProfiles(): BelongsToMany
    {
        return $this->belongsToMany(
            AlumniProfile::class,
            'alumni_profile_skill',
            'skill_id',
            'alumni_profile_id'
        );
    }

    /************************ Accessors & Mutators ************************** */

    /**
     * Mutator for normalizing the skill name upon assignment.
     *
     * @return Attribute<void, string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => $this->normalizeText($value),
        );
    }

    /**
     * Mutator for normalizing the category name upon assignment.
     *
     * @return Attribute<void, string|null>
     */
    protected function category(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value ? $this->normalizeText($value) : null,
        );
    }

    /************************ Helper Methods ************************** */

    /**
     * Shared normalization logic: trim whitespace, collapse internal multi-spaces,
     * and convert string to Title Case for consistent comparison and storage.
     *
     * @param string $value Raw input string.
     * @return string Normalized title-cased string.
     */
    private function normalizeText(string $value): string
    {
        $trimmed = trim($value);
        $collapsed = preg_replace('/\s+/', ' ', $trimmed);

        return mb_convert_case($collapsed, MB_CASE_TITLE, 'UTF-8');
    }
}
