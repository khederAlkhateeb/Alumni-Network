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
