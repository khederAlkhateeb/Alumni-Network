<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name',
    'category',
])]
class Skill extends Model
{ use HasFactory;
    public $timestamps = false;

    public function alumniProfiles(): BelongsToMany
    {
        return $this->belongsToMany(
            AlumniProfile::class,
            'alumni_profile_skill',
            'skill_id',
            'alumni_profile_id'
        );
    }

    /**
     * Normalize skill name: trim whitespace, collapse multiple spaces,
     * and store in Title Case so "php", "PHP", " php " all resolve
     * to the same stored value ("Php" -> we actually want "PHP"-style
     * consistency handled at the application layer, see note below).
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => $this->normalizeText($value),
        );
    }

    /**
     * Normalize category the same way: trim + consistent casing.
     */
    protected function category(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => $this->normalizeText($value),
        );
    }

    /**
     * Shared normalization logic: trim, collapse internal whitespace,
     * and apply Title Case so comparisons and storage stay consistent.
     */
    private function normalizeText(string $value): string
    {
        $trimmed  = trim($value);
        $collapsed = preg_replace('/\s+/', ' ', $trimmed);

        return mb_convert_case($collapsed, MB_CASE_TITLE, 'UTF-8');
    }
}
