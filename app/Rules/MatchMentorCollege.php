<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Major;
use Illuminate\Support\Facades\DB;

/**
 * Class MatchMentorCollege
 *
 * Validation rule to ensure that the selected mentor belongs to the same
 * faculty or college as the authenticated student by comparing their major faculties.
 *
 * @package App\Rules
 */
class MatchMentorCollege implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value The mentor ID being validated.
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $studentId = auth()->id();
        $mentorId = $value;

        // Fetch student and mentor major IDs efficiently via query builder
        $studentMajorId = DB::table('student_profiles')->where('user_id', $studentId)->value('major_id');
        $mentorMajorId = DB::table('alumni_profiles')->where('user_id', $mentorId)->value('major_id');

        if (!$studentMajorId || !$mentorMajorId) {
            $fail('Could not verify the college for the student or mentor.');
            return;
        }

        // Fetch faculties for both majors in a single query
        $majors = Major::whereIn('id', [$studentMajorId, $mentorMajorId])->pluck('faculty_id', 'id');

        $studentFacultyId = $majors[$studentMajorId] ?? null;
        $mentorFacultyId = $majors[$mentorMajorId] ?? null;

        if (!$studentFacultyId || !$mentorFacultyId || $studentFacultyId !== $mentorFacultyId) {
            $fail('The selected mentor must belong to your same faculty/college.');
        }
    }
}
