<?php

use App\Enums\ProfileStatus;
use App\Models\AlumniProfile;
use App\Models\Comment;
use App\Models\Faculty;
use App\Models\Post;
use App\Models\StudentProfile;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Feature tests for the comment-related API endpoints: viewing,
 * creating, and deleting comments on a post, including role-based
 * and ownership-based authorization.
 */
uses(RefreshDatabase::class);

/**
 * Seed the roles/permissions used across these tests, a minimal
 * University/Faculty pair, and four users covering every relevant
 * role: alumni, student, university admin, and super admin.
 */
beforeEach(function () {
    // Prevents a feed/cache entry from a previous test leaking into
    // this one, since all tests share the same PHP process.
    Cache::flush();

    Role::findOrCreate('alumni', 'api');
    Role::findOrCreate('student', 'api');
    Role::findOrCreate('uni_admin', 'api');
    Role::findOrCreate('super_admin', 'api');

    createCommentPermissions();

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
});

/**
 * Create the permissions used by comment-related policies, guarded
 * for the 'api' guard.
 */
function createCommentPermissions(): void
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

/**
 * Unauthenticated requests to all comment endpoints must return 401 Unauthorized.
 */
it('forbids unauthenticated users from accessing comment routes', function () {
    $post = Post::factory()->create();

    $this->getJson("/api/v1/posts/{$post->id}/comments")->assertStatus(401);
    $this->postJson("/api/v1/posts/{$post->id}/comments", [])->assertStatus(401);
    $this->deleteJson("/api/v1/posts/{$post->id}/comments/1")->assertStatus(401);
});

/**
 * An authenticated user can retrieve the paginated list of comments
 * on a post, with the expected JSON structure.
 */
it('lets an authenticated user view comments on a post', function () {
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
                ],
            ],
            'meta',
            'message',
        ]);
});

/**
 * An active alumni user can successfully create a comment on a post.
 */
it('lets an active alumni user create a comment', function () {
    $post = Post::factory()->create();

    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->postJson("/api/v1/posts/{$post->id}/comments", [
        'content' => 'This is a test comment.',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Comment created successfully');
});

/**
 * An active student user can successfully create a comment on a post,
 * confirming comments aren't restricted to alumni only.
 */
it('lets an active student user create a comment', function () {
    $post = Post::factory()->create();

    Sanctum::actingAs($this->studentUser, ['*'], 'api');

    $response = $this->postJson("/api/v1/posts/{$post->id}/comments", [
        'content' => 'This is a test comment from student.',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Comment created successfully');
});

/**
 * An inactive user (is_active = false and PENDING profile) is
 * forbidden (403) from creating a comment.
 */
it('forbids an inactive user from creating a comment', function () {
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
});

/**
 * The owner of a comment can delete it, and the record is removed
 * from the database.
 */
it('lets the comment owner delete their comment', function () {
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
});

/**
 * A user who is not the comment's owner receives a 403 when
 * attempting to delete it.
 */
it('forbids a non-owner from deleting a comment', function () {
    $post = Post::factory()->create();
    $comment = Comment::factory()->create(['post_id' => $post->id]);

    Sanctum::actingAs($this->studentUser, ['*'], 'api');

    $response = $this->deleteJson("/api/v1/posts/{$post->id}/comments/{$comment->id}");

    $response->assertStatus(403);
});

/**
 * A university admin can delete any comment, regardless of ownership
 * (moderation privilege).
 */
it('lets a university admin delete any comment', function () {
    $post = Post::factory()->create();
    $comment = Comment::factory()->create(['post_id' => $post->id]);

    Sanctum::actingAs($this->uniAdminUser, ['*'], 'api');

    $response = $this->deleteJson("/api/v1/posts/{$post->id}/comments/{$comment->id}");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Comment deleted successfully');

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});
