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

class PostRoutesTest extends TestCase
{
    use RefreshDatabase;

    private User $alumniUser;

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

    public function test_unauthenticated_user_cannot_access_post_endpoints(): void
    {
        $post = Post::factory()->create();

        $this->getJson('/api/v1/feed')->assertStatus(401);
        $this->postJson('/api/v1/posts', [])->assertStatus(401);
        $this->getJson("/api/v1/posts/{$post->id}")->assertStatus(401);
        $this->putJson("/api/v1/posts/{$post->id}", [])->assertStatus(401);
        $this->deleteJson("/api/v1/posts/{$post->id}")->assertStatus(401);
    }

    public function test_authenticated_user_can_retrieve_feed(): void
    {
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
                    ]
                ],
                'meta'
            ]);
    }

    public function test_active_alumni_can_create_post(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->postJson('/api/v1/posts', [
            'content' => 'This is a long enough content for testing post creation.',
            'visibility' => 'public',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.content', 'This is a long enough content for testing post creation.');
    }

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
            'content' => 'This is a long enough content for testing post creation.',
            'visibility' => 'public',
        ]);

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_view_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->alumniUser->id,
            'visibility' => PostVisibility::Public,
        ]);

        Sanctum::actingAs($this->alumniUser, ['*'], 'api');

        $response = $this->getJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $post->id);
    }

    public function test_post_owner_can_update_post(): void
    {
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
    }

    public function test_non_owner_cannot_update_post(): void
    {
        $otherUser = User::factory()->create(['is_active' => true]);
        $post = Post::factory()->create([
            'user_id' => $this->alumniUser->id,
            'visibility' => PostVisibility::Public,
        ]);

        Sanctum::actingAs($otherUser, ['*'], 'api');

        $response = $this->putJson("/api/v1/posts/{$post->id}", [
            'content' => 'This is the newly updated content for the post.',
            'visibility' => 'connections',
        ]);

        $response->assertStatus(403);
    }

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
}
