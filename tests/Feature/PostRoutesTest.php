<?php

use App\Enums\PostVisibility;
use App\Enums\ProfileStatus;
use App\Models\AlumniProfile;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

/**
 * Feature tests for the post-related API endpoints.
 * Covers authentication, authorization, validation, CRUD operations,
 * feed behavior with visibility rules, and edge cases.
 */
uses(RefreshDatabase::class);

/**
 * Prepare the testing environment before each test.
 *
 * Creates the 'alumni' role (api guard), a fully active user, and an
 * associated AlumniProfile with ACTIVE status — used in most happy-path
 * and authorization tests via $this->alumniUser.
 */
beforeEach(function () {
    Cache::flush();
    Role::findOrCreate('alumni', 'api');

    $this->alumniUser = User::factory()->create(['is_active' => true]);
    $this->alumniUser->assignRole('alumni');
    AlumniProfile::factory()->create([
        'user_id' => $this->alumniUser->id,
        'status' => ProfileStatus::ACTIVE,
    ]);
});

// ----------------------------------------------------------------
// AUTHENTICATION & BASIC AUTHORIZATION
// ----------------------------------------------------------------

/**
 * Unauthenticated requests to all post endpoints must return 401 Unauthorized.
 */
it('forbids unauthenticated users from accessing post endpoints', function () {
    $post = Post::factory()->create();

    $this->getJson('/api/v1/feed')->assertStatus(401);
    $this->postJson('/api/v1/posts', [])->assertStatus(401);
    $this->getJson("/api/v1/posts/{$post->id}")->assertStatus(401);
    $this->putJson("/api/v1/posts/{$post->id}", [])->assertStatus(401);
    $this->deleteJson("/api/v1/posts/{$post->id}")->assertStatus(401);
});

/**
 * An inactive user (is_active = false and PENDING profile) is forbidden (403)
 * from creating a post.
 */
it('forbids an inactive user from creating a post', function () {
    $inactiveUser = User::factory()->create(['is_active' => false]);
    $inactiveUser->assignRole('alumni');
    AlumniProfile::factory()->create([
        'user_id' => $inactiveUser->id,
        'status' => ProfileStatus::PENDING,
    ]);

    Sanctum::actingAs($inactiveUser, ['*'], 'api');

    $response = $this->postJson('/api/v1/posts', [
        'content' => 'Valid content for a post that will be rejected.',
        'visibility' => 'public',
    ]);

    $response->assertStatus(403);
});

// ----------------------------------------------------------------
// FEED / LISTING
// ----------------------------------------------------------------

/**
 * An authenticated user receives a 200 with the expected structure
 * when posts exist in the feed.
 */
it('lets an authenticated user retrieve their feed', function () {
    Post::factory()->count(3)->create([
        'user_id' => $this->alumniUser->id,
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
});

/**
 * When no posts exist, the feed returns a 200 with an empty data array.
 */
it('returns an empty feed when no posts exist', function () {
    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->getJson('/api/v1/feed');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(0, 'data');
});

/**
 * The feed respects visibility rules:
 * - Public and University posts from same-university alumni should be visible.
 * - Connection-only posts are hidden because the user is not connected to themselves.
 */

it('respects visibility rules in the feed', function () {

    $universityPost = Post::factory()->create([
        'user_id' => $this->alumniUser->id,
        'visibility' => PostVisibility::University,
        'created_at' => now()->subMinutes(5),
    ]);


    $publicPost = Post::factory()->create([
        'user_id' => $this->alumniUser->id,
        'visibility' => PostVisibility::Public,
        'created_at' => now(),
    ]);

    // This post is connections-only; user is not connected to themselves → will NOT appear
    Post::factory()->create([
        'user_id' => $this->alumniUser->id,
        'visibility' => PostVisibility::Connections,
        'created_at' => now()->subMinutes(10),
    ]);

    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->getJson('/api/v1/feed');
    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $publicPost->id)
        ->assertJsonPath('data.1.id', $universityPost->id);
});

// ----------------------------------------------------------------
// CREATE POST
// ----------------------------------------------------------------

/**
 * An active alumni user can successfully create a post with valid input.
 */
it('lets an active alumni user create a post', function () {
    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->postJson('/api/v1/posts', [
        'content' => 'This is a long enough content for testing post creation.',
        'visibility' => 'public',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.content', 'This is a long enough content for testing post creation.');
});

/**
 * Validation fails with a 422 when the content field is missing or empty.
 */
it('fails to create a post with empty content', function () {
    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->postJson('/api/v1/posts', [
        'content' => '',
        'visibility' => 'public',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('content');
});

/**
 * Validation fails with a 422 when the content is shorter than the minimum length.
 */
it('fails to create a post with content shorter than the minimum length', function () {
    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->postJson('/api/v1/posts', [
        'content' => 'Short', // assuming min:10
        'visibility' => 'public',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('content');
});

/**
 * Validation fails with a 422 when an invalid visibility value is provided.
 */
it('fails to create a post with an invalid visibility value', function () {
    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->postJson('/api/v1/posts', [
        'content' => 'Valid content that meets the minimum length.',
        'visibility' => 'invalid-option',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('visibility');
});

// ----------------------------------------------------------------
// VIEW POST
// ----------------------------------------------------------------

/**
 * An authenticated user can view a single post by its ID.
 */
it('lets an authenticated user view a single post', function () {
    $post = Post::factory()->create([
        'user_id' => $this->alumniUser->id,
        'visibility' => PostVisibility::Public,
    ]);

    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->getJson("/api/v1/posts/{$post->id}");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $post->id);
});

/**
 * Attempting to view a non-existent post returns 404.
 */
it('returns 404 when viewing a post that does not exist', function () {
    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->getJson('/api/v1/posts/999999');

    $response->assertStatus(404);
});

// ----------------------------------------------------------------
// UPDATE POST
// ----------------------------------------------------------------

/**
 * The owner of a post can successfully update its content and visibility.
 */
it('lets the post owner update their post', function () {
    $post = Post::factory()->create([
        'user_id' => $this->alumniUser->id,
        'visibility' => PostVisibility::Public,
    ]);

    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->putJson("/api/v1/posts/{$post->id}", [
        'content' => 'This is the newly updated content for the post.',
        'visibility' => 'connections',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.content', 'This is the newly updated content for the post.');
});

/**
 * A user who is not the owner receives a 403 when trying to update a post.
 */
it('forbids a non-owner from updating a post', function () {
    $otherUser = User::factory()->create(['is_active' => true]);
    $post = Post::factory()->create([
        'user_id' => $this->alumniUser->id,
        'visibility' => PostVisibility::Public,
    ]);

    Sanctum::actingAs($otherUser, ['*'], 'api');

    $response = $this->putJson("/api/v1/posts/{$post->id}", [
        'content' => 'Trying to update someone else post.',
        'visibility' => 'connections',
    ]);

    $response->assertStatus(403);
});

/**
 * Updating a post with invalid data (e.g. short content, invalid visibility)
 * returns a 422 validation error.
 */
it('fails to update a post with invalid data', function () {
    $post = Post::factory()->create([
        'user_id' => $this->alumniUser->id,
        'visibility' => PostVisibility::Public,
    ]);

    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    // Send both short content and invalid visibility
    $response = $this->putJson("/api/v1/posts/{$post->id}", [
        'content' => 'X',
        'visibility' => 'nonexistent',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content', 'visibility']);
});

/**
 * Attempting to update a non-existent post returns 404.
 */
it('returns 404 when updating a post that does not exist', function () {
    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->putJson('/api/v1/posts/999999', [
        'content' => 'Valid content for update.',
        'visibility' => 'public',
    ]);

    $response->assertStatus(404);
});

// ----------------------------------------------------------------
// DELETE POST
// ----------------------------------------------------------------

/**
 * The owner can delete their own post, and the record is removed from the database.
 */
it('lets the owner delete their post', function () {
    $post = Post::factory()->create([
        'user_id' => $this->alumniUser->id,
    ]);

    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->deleteJson("/api/v1/posts/{$post->id}");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Post deleted successfully');

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

/**
 * A non-owner receives a 403 Forbidden when attempting to delete a post.
 */
it('forbids a non-owner from deleting a post', function () {
    $otherUser = User::factory()->create(['is_active' => true]);
    $post = Post::factory()->create([
        'user_id' => $this->alumniUser->id,
    ]);

    Sanctum::actingAs($otherUser, ['*'], 'api');

    $response = $this->deleteJson("/api/v1/posts/{$post->id}");

    $response->assertStatus(403);
    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});

/**
 * Deleting a post that does not exist returns 404.
 */
it('returns 404 when deleting a post that does not exist', function () {
    Sanctum::actingAs($this->alumniUser, ['*'], 'api');

    $response = $this->deleteJson('/api/v1/posts/999999');

    $response->assertStatus(404);
});
