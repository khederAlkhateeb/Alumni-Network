<?php

use App\Models\User;
use App\Models\University;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\GraduationRequest;
use App\Models\AlumniProfile;
use App\Models\UniversityAdmin;
use App\Jobs\SendGraduationApprovedNotificationJob;
use App\Contracts\AttachmentSecurity\FileValidatorInterface;
use App\Contracts\AttachmentSecurity\SecureFileStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup permissions and roles across all available guards
    $guards = ['api', 'sanctum', 'web'];

    $permissions = [
        'submit-graduation-request',
        'view-graduation-requests',
        'approve-graduation-request',
        'reject-graduation-request',
        'complete-alumni-profile',
    ];

    foreach ($guards as $guard) {
        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, $guard);
        }

        Role::findOrCreate('student', $guard)->givePermissionTo(['submit-graduation-request']);
        Role::findOrCreate('uni_admin', $guard)->givePermissionTo([
            'view-graduation-requests',
            'approve-graduation-request',
            'reject-graduation-request',
        ]);
        Role::findOrCreate('alumni', $guard)->givePermissionTo(['complete-alumni-profile']);
    }
});

/**
 * Helper function for authentication via Sanctum.
 *
 * @param User $user
 * @return array<string, string>
 */
if (! function_exists('authenticateUser')) {
    function authenticateUser(User $user)
    {
        Sanctum::actingAs($user, ['*']);
        return ['Accept' => 'application/json'];
    }
}

/**
 * Helper function to create interconnected structures for university, student, and admin.
 *
 * @return array
 */
if (! function_exists('makeEntitiesFromCode')) {
    function makeEntitiesFromCode()
    {
        $university = University::factory()->create();
        $faculty    = Faculty::factory()->create(['university_id' => $university->id]);
        $major      = Major::factory()->create(['faculty_id' => $faculty->id]);

        $student = User::factory()->create(['is_active' => true]);
        $student->assignRole('student');

        $studentProfile = StudentProfile::factory()->create([
            'user_id'  => $student->id,
            'major_id' => $major->id,
        ]);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('uni_admin');

        UniversityAdmin::factory()->create([
            'user_id'       => $admin->id,
            'university_id' => $university->id,
        ]);

        return [$student, $studentProfile, $admin, $university];
    }
}

// -----------------------------------------------------------------------------
// 1. Graduation Request Submission
// -----------------------------------------------------------------------------
describe('1. Graduation Request Submission', function () {

    it('allows a student to submit a graduation request successfully', function () {
        Queue::fake();

        $this->mock(FileValidatorInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('validateFile')
                ->andReturn([
                    'valid' => true,
                    'safe_filename' => 'certificates/fake_cert.pdf',
                ]);
        });

        $this->mock(SecureFileStorageInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('storeFile')
                ->andReturn('/storage/certificates/fake_cert.pdf');
        });

        [$student, $profile] = makeEntitiesFromCode();
        $headers = authenticateUser($student);

        $file = UploadedFile::fake()->create('certificate.pdf', 200, 'application/pdf');

        $response = $this->withHeaders($headers)
            ->postJson('/api/v1/student/graduation-requests', [
                'graduation_certificate' => $file,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('graduation_requests', [
            'student_profile_id' => $profile->id,
            'user_id'            => $student->id,
            'status'             => 'pending',
        ]);
    });

    it('denies unauthenticated users from submitting a request', function () {
        $response = $this->postJson('/api/v1/student/graduation-requests', []);
        $response->assertStatus(401);
    });

    it('fails validation when certificate is missing', function () {
        $this->mock(FileValidatorInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('validateFile')
                ->andReturn([
                    'valid' => true,
                    'safe_filename' => 'certificates/fake_cert.pdf',
                ]);
        });

        $this->mock(SecureFileStorageInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('storeFile')
                ->andReturn('/storage/certificates/fake_cert.pdf');
        });

        [$student] = makeEntitiesFromCode();
        $headers = authenticateUser($student);

        $response = $this->withHeaders($headers)
            ->postJson('/api/v1/student/graduation-requests', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['graduation_certificate']);
    });
});

// -----------------------------------------------------------------------------
// 2. View Graduation Requests Flow
// -----------------------------------------------------------------------------
describe('2. View Graduation Requests Flow', function () {

    it('allows university admin to view graduation requests', function () {
        [$student, $profile, $admin] = makeEntitiesFromCode();
        $headers = authenticateUser($admin);

        GraduationRequest::factory()->count(3)->create([
            'user_id'            => $student->id,
            'student_profile_id' => $profile->id,
            'status'             => 'pending',
        ]);

        $response = $this->withHeaders($headers)
            ->getJson('/api/v1/admin/graduation-requests');

        $response->assertStatus(200);
    });

    it('forbids normal students from viewing admin graduation requests list', function () {
        [$student] = makeEntitiesFromCode();
        $headers = authenticateUser($student);

        $response = $this->withHeaders($headers)
            ->getJson('/api/v1/admin/graduation-requests');

        $response->assertStatus(403);
    });
});

// -----------------------------------------------------------------------------
// 3. Graduation Request Approval & Notification Job
// -----------------------------------------------------------------------------
describe('3. Graduation Request Approval & Notification Job', function () {

    it('allows admin to approve a request, updates role, and dispatches notification job', function () {
        Queue::fake();

        [$student, $profile, $admin] = makeEntitiesFromCode();
        $headers = authenticateUser($admin);

        $request = GraduationRequest::factory()->create([
            'user_id'            => $student->id,
            'student_profile_id' => $profile->id,
            'certificate_path'   => 'certificates/sample.pdf',
            'status'             => 'pending',
        ]);

        $response = $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/graduation-requests/{$request->id}/approve");

        $response->assertStatus(200);

        // 1. Ensure the request status is updated
        $this->assertDatabaseHas('graduation_requests', [
            'id'     => $request->id,
            'status' => 'approved',
        ]);

        // 2. Ensure the student role has transformed to alumni
        expect($student->fresh()->hasRole('alumni'))->toBeTrue();

        // 3. Ensure the notification job is pushed to the queue with the alumni profile
        Queue::assertPushed(SendGraduationApprovedNotificationJob::class, function ($job) use ($student) {
            return $job->alumniProfile->user_id === $student->id;
        });
    });

    it('creates notification record correctly when SendGraduationApprovedNotificationJob runs', function () {
        [$student] = makeEntitiesFromCode();

        $alumniProfile = AlumniProfile::factory()->create([
            'user_id' => $student->id,
            'status'  => 'active',
        ]);

        // Run the job directly and test its database side effects
        $job = new SendGraduationApprovedNotificationJob($alumniProfile);
        $job->handle();

        $this->assertDatabaseHas('notifications', [
            'user_id'      => $student->id,
            'type'         => 'graduation_request_approved',
            'related_id'   => $alumniProfile->id,
            'related_type' => get_class($alumniProfile),
        ]);
    });

    it('prevents non-admin users from approving requests', function () {
        [$student, $profile] = makeEntitiesFromCode();

        $otherStudent = User::factory()->create(['is_active' => true]);
        $otherStudent->assignRole('student');
        $headers = authenticateUser($otherStudent);

        $request = GraduationRequest::factory()->create([
            'user_id'            => $student->id,
            'student_profile_id' => $profile->id,
            'status'             => 'pending',
        ]);

        $response = $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/graduation-requests/{$request->id}/approve");

        $response->assertStatus(403);
    });
});

// -----------------------------------------------------------------------------
// 4. Graduation Request Rejection
// -----------------------------------------------------------------------------
describe('4. Graduation Request Rejection', function () {

    it('allows admin to reject a request with a reason', function () {
        Queue::fake();

        [$student, $profile, $admin] = makeEntitiesFromCode();
        $headers = authenticateUser($admin);

        $request = GraduationRequest::factory()->create([
            'user_id'            => $student->id,
            'student_profile_id' => $profile->id,
            'certificate_path'   => 'certificates/sample.pdf',
            'status'             => 'pending',
        ]);

        $response = $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/graduation-requests/{$request->id}/reject", [
                'rejection_reason' => 'Missing required credit hours.',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('graduation_requests', [
            'id'               => $request->id,
            'status'           => 'rejected',
            'rejection_reason' => 'Missing required credit hours.',
        ]);
    });

    it('requires rejection_reason field when rejecting', function () {
        [$student, $profile, $admin] = makeEntitiesFromCode();
        $headers = authenticateUser($admin);

        $request = GraduationRequest::factory()->create([
            'user_id'            => $student->id,
            'student_profile_id' => $profile->id,
            'status'             => 'pending',
        ]);

        $response = $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/graduation-requests/{$request->id}/reject", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rejection_reason']);
    });
});
