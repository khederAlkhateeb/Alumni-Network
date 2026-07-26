<?php

namespace App\V1\Actions\Authentication;

use App\Models\AlumniProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Handles the business logic for registering a new user account.
 *
 * Responsibilities:
 *  - Create the base User record (password is hashed automatically
 *    via the `hashed` cast defined on the User model).
 *  - Assign the appropriate Spatie role ("alumni" or "student").
 *  - Create the associated profile record (AlumniProfile or
 *    StudentProfile) with an initial "pending" status, awaiting
 *    admin approval.
 *
 * All operations run inside a database transaction to guarantee
 * that a User is never created without its corresponding profile.
 *
 * @package App\V1\Actions\Authentication
 */
class RegisterUser
{
    /**
     * Register a new user as either an alumnus or a current student.
     *
     * @param  array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     role: 'alumni'|'student',
     *     major_id: int,
     *     student_number?: string,
     *     graduation_year?: int,
     *     enrollment_number?: string,
     *     enrollment_year?: int,
     *     expected_graduation_year?: int
     * } $data The validated registration payload. Fields marked
     *         optional are required conditionally depending on `role`.
     *
     * @return array{user: User, profile: AlumniProfile|StudentProfile}
     *         The newly created user along with their associated
     *         (pending) profile.
     */
    public function handle(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'],
            ]);

            $user->assignRole($data['role']);

            $profile = $data['role'] === 'alumni'
                ? AlumniProfile::create([
                    'user_id'         => $user->id,
                    'major_id'        => $data['major_id'],
                    'student_number'  => $data['student_number'],
                    'graduation_year' => $data['graduation_year'],
                    'status'          => 'pending',
                ])
                : StudentProfile::create([
                    'user_id'                  => $user->id,
                    'major_id'                 => $data['major_id'],
                    'enrollment_number'        => $data['enrollment_number'],
                    'enrollment_year'          => $data['enrollment_year'],
                    'expected_graduation_year' => $data['expected_graduation_year'],
                    'status'                   => 'pending',
                ]);

            return [
                'user'    => $user,
                'profile' => $profile,
            ];
        });
    }
}
