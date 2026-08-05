<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;

/**
 * Class MatchMentorCollege
 *
 * Validation rule to ensure that the selected mentor belongs to the same
 * faculty or college as the authenticated student.
 *
 * @package App\Rules
 */
class MatchMentorCollege implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $student = auth()->user();
        $mentor = User::find($value);

        $studentMajorId = optional($student->studentProfile)->major_id;
        $mentorMajorId = optional($mentor?->alumniProfile)->major_id;

        if (!$studentMajorId || !$mentorMajorId) {
            $fail('Could not verify the college for the student or mentor.');
            return;
        }

        $studentFacultyId = \App\Models\Major::where('id', $studentMajorId)->value('faculty_id');
        $mentorFacultyId = \App\Models\Major::where('id', $mentorMajorId)->value('faculty_id');

        if ($studentFacultyId !== $mentorFacultyId) {
            $fail('The selected mentor must belong to your same faculty/college.');
        }
    }
}
