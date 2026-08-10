<?php

namespace Tests\Feature;

use App\Enums\ProfileStatus;
use App\Enums\PostVisibility;
use App\Models\AlumniProfile;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Class PostRoutesTest
 *
 * Comprehensive feature tests for the post-related API endpoints.
 * Covers authentication, authorization, validation, CRUD operations,
 * feed behavior with visibility rules, and edge cases.
 *
 * @package Tests\Feature
 */
class PostRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An active alumni user with a corresponding ACTIVE profile.
     * Used in most happy-path and authorization tests.
     *
     * @var User
     */
    private User $alumniUser;

    /**
     * Prepare the testing environment.
     *
     * Creates the 'alumni' role (api guard), a fully active user,
     * and an associated AlumniProfile with ACTIVE status.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('alumni', 'api');

        $this->alumniUser = User::factory()->create(['is_active' => true]);
        $this->alumniUser->assignRole('alumni');
        AlumniProfile::factory()->create([
            'user_id' => $this->alumniUser->id,
            'status' => ProfileStatus::ACTIVE,
        ]);
    }

    // ----------------------------------------------------------------
    // AUTHENTICATION & BASIC AUTHORIZATION
    // ----------------------------------------------------------------

    /**
     * @test
     *
     * Unauthenticated requests to all post endpoints must return 401 Unauthorized.
     */
    public function test_unauthenticated_user_cannot_access_post_endpoints(): void
    {
        $post = Post::factory()->create();

        $this->getJson('/api/v1/feed')->assertStatus(401);
        $this->postJson('/api/v1/posts', [])->assertStatus(401);
        $this->getJson("/api/v1/posts/{$post->id}")->assertStatus(401);
        $this->putJson("/api/v1/posts/{$post->id}", [])->assertStatus(401);
        $this->deleteJson("/api/v1/posts/{$post->id}")->assertStatus(401);
    }

    /**
     * @test
     *
     * An inactive user (is_active = false and PENDING profile) is forbidden (403)
     * from creating a post.
     */
    public function test_inactive_user_cannot_create_post(): void
    {
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $inactiveUser->assignRole('alumni');
        AlumniProfile::factory()->create([
            'user_id' => $inactiveUser->id,
            'status' => ProfileStatus::PENDING,
        ]);

        Sanctum::actingAs($inactiveUser, ['*'], 'api');

        $response = $this->postJson('/api/v1/posts', [
            'content'    => 'Valid content for a post that will be rejected.',
            'visibility' => 'public',
        ]);

        $response->assertStatus(403);
    }

    // ----------------------------------------------------------------
    // FEED / LISTING
    // ----------------------------------------------------------------

    /**
     * @test
     *
     * An authenticated user receives a 200 with the expected structure
     * when posts exist in the feed.
     */
    public function test_authenticated_user_can_retrieve_feed(): void
    {
        Post::factory()->count(3)->create([
            'user_id'    => $this->alumniUser->id,
            'visibility' => PostVisibility::Public,
        ]);

        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->getJson('/api/v1/feed');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'visibility',
                        'created_at',
                    ],
                ],
                'meta',
            ]);
    }

    /**
     * @test
     *
     * When no posts exist, the feed returns a 200 with an empty data array.
     */
    public function test_feed_returns_empty_when_no_posts(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->getJson('/api/v1/feed');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(0, 'data');
    }

/**
 * @test
 *
 * The feed respects visibility rules:
 * - Public and University posts from same-university alumni should be visible.
 * - Connection-only posts are hidden because the user is not connected to themselves.
 */
public function test_feed_respects_visibility_mix(): void
{
    // This post is public and from the same university → will appear
    $publicPost = Post::factory()->create([
        'user_id'    => $this->alumniUser->id,
        'visibility' => PostVisibility::Public,
    ]);

    // This post is university announcement and from the same university → will appear
    $universityPost = Post::factory()->create([
        'user_id'    => $this->alumniUser->id,
        'visibility' => PostVisibility::University,
    ]);

    // This post is connections-only; user is not connected to themselves → will NOT appear
    Post::factory()->create([
        'user_id'    => $this->alumniUser->id,
        'visibility' => PostVisibility::Connections,
    ]);

    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->getJson('/api/v1/feed');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')                        // Public + University only
        ->assertJsonPath('data.0.id', $publicPost->id)      // order depends on `latest()`
        ->assertJsonPath('data.1.id', $universityPost->id);
}

    // ----------------------------------------------------------------
    // CREATE POST
    // ----------------------------------------------------------------

    /**
     * @test
     *
     * An active alumni user can successfully create a post with valid input.
     */
    public function test_active_alumni_can_create_post(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->postJson('/api/v1/posts', [
            'content'    => 'This is a long enough content for testing post creation.',
            'visibility' => 'public',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.content', 'This is a long enough content for testing post creation.');
    }

    /**
     * @test
     *
     * Validation fails with a 422 when the content field is missing or empty.
     */
    public function test_post_creation_fails_with_empty_content(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->postJson('/api/v1/posts', [
            'content'    => '',
            'visibility' => 'public',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    /**
     * @test
     *
     * Validation fails with a 422 when the content is shorter than the minimum length.
     */
    public function test_post_creation_fails_with_short_content(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->postJson('/api/v1/posts', [
            'content'    => 'Short',  // assuming min:10
            'visibility' => 'public',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    /**
     * @test
     *
     * Validation fails with a 422 when an invalid visibility value is provided.
     */
    public function test_post_creation_fails_with_invalid_visibility(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->postJson('/api/v1/posts', [
            'content'    => 'Valid content that meets the minimum length.',
            'visibility' => 'invalid-option',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('visibility');
    }

    // ----------------------------------------------------------------
    // VIEW POST
    // ----------------------------------------------------------------

    /**
     * @test
     *
     * An authenticated user can view a single post by its ID.
     */
    public function test_authenticated_user_can_view_post(): void
    {
        $post = Post::factory()->create([
            'user_id'    => $this->alumniUser->id,
            'visibility' => PostVisibility::Public,
        ]);

        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->getJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $post->id);
    }

    /**
     * @test
     *
     * Attempting to view a non-existent post returns 404.
     */
    public function test_view_post_that_does_not_exist_returns_404(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->getJson('/api/v1/posts/999999');

        $response->assertStatus(404);
    }

    // ----------------------------------------------------------------
    // UPDATE POST
    // ----------------------------------------------------------------

    /**
     * @test
     *
     * The owner of a post can successfully update its content and visibility.
     */
    public function test_post_owner_can_update_post(): void
    {
        $post = Post::factory()->create([
            'user_id'    => $this->alumniUser->id,
            'visibility' => PostVisibility::Public,
        ]);

        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->putJson("/api/v1/posts/{$post->id}", [
            'content'    => 'This is the newly updated content for the post.',
            'visibility' => 'connections',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.content', 'This is the newly updated content for the post.');
    }

    /**
     * @test
     *
     * A user who is not the owner receives a 403 when trying to update a post.
     */
    public function test_non_owner_cannot_update_post(): void
    {
        $otherUser = User::factory()->create(['is_active' => true]);
        $post = Post::factory()->create([
            'user_id'    => $this->alumniUser->id,
            'visibility' => PostVisibility::Public,
        ]);

        Sanctum::actingAs($otherUser, ['*'], 'api');

        $response = $this->putJson("/api/v1/posts/{$post->id}", [
            'content'    => 'Trying to update someone else post.',
            'visibility' => 'connections',
        ]);

        $response->assertStatus(403);
    }

    /**
     * @test
     *
     * Updating a post with invalid data (e.g. short content, invalid visibility)
     * returns a 422 validation error.
     */
    public function test_post_update_fails_with_invalid_data(): void
    {
        $post = Post::factory()->create([
            'user_id'    => $this->alumniUser->id,
            'visibility' => PostVisibility::Public,
        ]);

        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        // Send both short content and invalid visibility
        $response = $this->putJson("/api/v1/posts/{$post->id}", [
            'content'    => 'X',
            'visibility' => 'nonexistent',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content', 'visibility']);
    }

    /**
     * @test
     *
     * Attempting to update a non-existent post returns 404.
     */
    public function test_update_non_existent_post_returns_404(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->putJson('/api/v1/posts/999999', [
            'content'    => 'Valid content for update.',
            'visibility' => 'public',
        ]);

        $response->assertStatus(404);
    }

    // ----------------------------------------------------------------
    // DELETE POST
    // ----------------------------------------------------------------

    /**
     * @test
     *
     * The owner can delete their own post, and the record is removed from the database.
     */
    public function test_owner_can_delete_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->alumniUser->id,
        ]);

        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->deleteJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Post deleted successfully');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    /**
     * @test
     *
     * A non-owner receives a 403 Forbidden when attempting to delete a post.
     */
    public function test_non_owner_cannot_delete_post(): void
    {
        $otherUser = User::factory()->create(['is_active' => true]);
        $post = Post::factory()->create([
            'user_id' => $this->alumniUser->id,
        ]);

        Sanctum::actingAs($otherUser, ['*'], 'api');

        $response = $this->deleteJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    /**
     * @test
     *
     * Deleting a post that does not exist returns 404.
     */
    public function test_delete_non_existent_post_returns_404(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->deleteJson('/api/v1/posts/999999');

        $response->assertStatus(404);
    }
}
