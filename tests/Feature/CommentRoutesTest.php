<?php

namespace Tests\Feature;

use App\Enums\ProfileStatus;
use App\Models\AlumniProfile;
use App\Models\Comment;
use App\Models\Faculty;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\Post;
use App\Models\StudentProfile;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommentRoutesTest extends TestCase
{
    use RefreshDatabase;

    private User $alumniUser;
    private User $studentUser;
    private User $uniAdminUser;
    private User $superAdminUser;
    private University $university;
    private Faculty $faculty;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('alumni', 'api');
        Role::findOrCreate('student', 'api');
        Role::findOrCreate('uni_admin', 'api');
        Role::findOrCreate('super_admin', 'api');

        $this->createPermissions();

        $this->university = University::factory()->create();
        $this->faculty = Faculty::factory()->create(['university_id' => $this->university->id]);

        $this->alumniUser = User::factory()->create(['is_active' => true]);
        $this->alumniUser->assignRole('alumni');
        AlumniProfile::factory()->create([
            'user_id' => $this->alumniUser->id,
            'status' => ProfileStatus::ACTIVE,
        ]);

        $this->studentUser = User::factory()->create(['is_active' => true]);
        $this->studentUser->assignRole('student');
        StudentProfile::factory()->create([
            'user_id' => $this->studentUser->id,
            'status' => ProfileStatus::ACTIVE,
        ]);

        $this->uniAdminUser = User::factory()->create(['is_active' => true]);
        $this->uniAdminUser->assignRole('uni_admin');
        $this->uniAdminUser->universityAdmin()->create(['university_id' => $this->university->id]);

        $this->superAdminUser = User::factory()->create(['is_active' => true]);
        $this->superAdminUser->assignRole('super_admin');
    }
    private function createPermissions(): void
    {
        $guardName = 'api';
        $permissions = [
            'view-jobs',
            'create-job',
            'comment-on-post',
            'delete-own-comment',
            'delete-any-comment',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]);
        }
    }


    public function test_unauthenticated_user_cannot_access_job_routes(): void
    {
        $this->getJson('/api/v1/jobs')->assertStatus(401);
        $this->postJson('/api/v1/jobs', [])->assertStatus(401);
        $this->getJson('/api/v1/jobs/1')->assertStatus(401);
        $this->putJson('/api/v1/jobs/1', [])->assertStatus(401);
        $this->deleteJson('/api/v1/jobs/1')->assertStatus(401);
        $this->patchJson('/api/v1/jobs/1/close')->assertStatus(401);
        $this->postJson('/api/v1/jobs/1/apply', [])->assertStatus(401);
        $this->getJson('/api/v1/jobs/my-applications')->assertStatus(401);
        $this->getJson('/api/v1/jobs/1/applications')->assertStatus(401);
        $this->patchJson('/api/v1/jobs/1/applications/1/status', [])->assertStatus(401);
    }

    public function test_authenticated_user_can_view_jobs(): void
    {
        JobListing::factory()->count(3)->create(['university_id' => $this->university->id]);

        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('view-jobs');

        $response = $this->getJson('/api/v1/jobs');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'company',
                        'location',
                        'type',
                        'status',
                    ]
                ],
                'message'
            ]);
    }

    public function test_authenticated_user_can_view_single_job(): void
    {
        $job = JobListing::factory()->create(['university_id' => $this->university->id]);

        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('view-jobs');

        $response = $this->getJson("/api/v1/jobs/{$job->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $job->id);
    }

    public function test_user_with_permission_can_create_job(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('create-job');

        $response = $this->postJson('/api/v1/jobs', [
            'university_id' => $this->university->id,
            'title' => 'Software Engineer',
            'company' => 'Tech Corp',
            'location' => 'Amman',
            'type' => 'full_time',
            'description' => 'A great job opportunity',
            'requirements' => 'Experience required',
            'salary_range' => '1000-2000 JOD',
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Job listing created successfully.');
    }

    public function test_unauthenticated_user_cannot_access_comment_routes(): void
    {
        $post = Post::factory()->create();

        $this->getJson("/api/v1/posts/{$post->id}/comments")->assertStatus(401);
        $this->postJson("/api/v1/posts/{$post->id}/comments", [])->assertStatus(401);
        $this->deleteJson("/api/v1/posts/{$post->id}/comments/1")->assertStatus(401);
    }

    public function test_authenticated_user_can_view_comments(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->count(3)->create(['post_id' => $post->id]);

        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->getJson("/api/v1/posts/{$post->id}/comments");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'user_id',
                        'post_id',
                        'created_at',
                    ]
                ],
                'meta',
                'message'
            ]);
    }

    public function test_active_alumni_can_create_comment(): void
    {
        $post = Post::factory()->create();

        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->postJson("/api/v1/posts/{$post->id}/comments", [
            'content' => 'This is a test comment.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Comment created successfully');
    }

    public function test_active_student_can_create_comment(): void
    {
        $post = Post::factory()->create();

        Sanctum::actingAs($this->studentUser, ['*'], 'api');

        $response = $this->postJson("/api/v1/posts/{$post->id}/comments", [
            'content' => 'This is a test comment from student.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Comment created successfully');
    }

    public function test_inactive_user_cannot_create_comment(): void
    {
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $inactiveUser->assignRole('alumni');
        AlumniProfile::factory()->create([
            'user_id' => $inactiveUser->id,
            'status' => ProfileStatus::PENDING,
        ]);

        $post = Post::factory()->create();

        Sanctum::actingAs($inactiveUser, ['*'], 'api');

        $response = $this->postJson("/api/v1/posts/{$post->id}/comments", [
            'content' => 'This should fail.',
        ]);

        $response->assertStatus(403);
    }

    public function test_comment_owner_can_delete_comment(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $this->alumniUser->id,
        ]);

        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->deleteJson("/api/v1/posts/{$post->id}/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Comment deleted successfully');

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_non_owner_cannot_delete_comment(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        Sanctum::actingAs($this->studentUser, ['*'], 'api');

        $response = $this->deleteJson("/api/v1/posts/{$post->id}/comments/{$comment->id}");

        $response->assertStatus(403);
    }

    public function test_uni_admin_can_delete_any_comment(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        Sanctum::actingAs($this->uniAdminUser, ['*'], 'api');

        $response = $this->deleteJson("/api/v1/posts/{$post->id}/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Comment deleted successfully');

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }


}
