<?php

namespace Tests\Feature;

use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UniversityRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'super_admin', 'guard_name' => 'api']);
    }

    public function test_unauthenticated_user_can_list_universities()
    {
        University::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/universities');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['id', 'name', 'country', 'website', 'logo', 'created_at', 'updated_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_index_filters_by_name()
    {
        University::factory()->create(['name' => 'Harvard University']);
        University::factory()->create(['name' => 'MIT']);

        $response = $this->getJson('/api/v1/universities?name=Harvard');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Harvard University');
    }

    public function test_index_filters_by_country()
    {
        University::factory()->create(['country' => 'United States']);
        University::factory()->create(['country' => 'United Kingdom']);

        $response = $this->getJson('/api/v1/universities?country=United States');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_unauthenticated_user_cannot_create_university()
    {
        $response = $this->postJson('/api/v1/universities', [
            'name' => 'New University',
            'country' => 'United States',
        ]);

        $response->assertStatus(401);
    }

    public function test_non_super_admin_cannot_create_university()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/universities', [
            'name' => 'New University',
            'country' => 'United States',
        ]);

        $response->assertStatus(403);
    }

    public function test_super_admin_can_create_university()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/universities', [
            'name' => 'New University',
            'country' => 'United States',
            'website' => 'https://newuni.edu',
            'logo' => 'https://example.com/logo.png',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'name', 'country', 'website', 'logo', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'University created successfully.')
            ->assertJsonPath('data.name', 'New University')
            ->assertJsonPath('data.country', 'United States');

        $this->assertDatabaseHas('universities', [
            'name' => 'New University',
            'country' => 'United States',
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/universities', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'country']);
    }

    public function test_store_validates_unique_name()
    {
        University::factory()->create(['name' => 'Existing University']);

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/universities', [
            'name' => 'Existing University',
            'country' => 'United States',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_user_cannot_view_university()
    {
        $university = University::factory()->create();

        $response = $this->getJson("/api/v1/universities/{$university->id}");

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_view_university()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);
        $university = University::factory()->create();

        $response = $this->getJson("/api/v1/universities/{$university->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $university->id)
            ->assertJsonPath('data.name', $university->name);
    }

    public function test_unauthenticated_user_cannot_update_university()
    {
        $university = University::factory()->create();

        $response = $this->putJson("/api/v1/universities/{$university->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_non_authorized_user_cannot_update_university()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $university = University::factory()->create();

        $response = $this->putJson("/api/v1/universities/{$university->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(404);
    }

    public function test_super_admin_can_update_university()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);
        $university = University::factory()->create();

        $response = $this->putJson("/api/v1/universities/{$university->id}", [
            'name' => 'Updated University Name',
            'country' => 'Canada',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'University updated successfully.')
            ->assertJsonPath('data.name', 'Updated University Name')
            ->assertJsonPath('data.country', 'Canada');
    }

    public function test_unauthenticated_user_cannot_delete_university()
    {
        $university = University::factory()->create();

        $response = $this->deleteJson("/api/v1/universities/{$university->id}");

        $response->assertStatus(401);
    }

    public function test_non_super_admin_cannot_delete_university()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $university = University::factory()->create();

        $response = $this->deleteJson("/api/v1/universities/{$university->id}");

        $response->assertStatus(404);
    }

    public function test_super_admin_can_delete_university()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);
        $university = University::factory()->create();

        $response = $this->deleteJson("/api/v1/universities/{$university->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'University deleted successfully.');

        $this->assertSoftDeleted($university);
    }

    public function test_returns_404_for_nonexistent_university()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/universities/99999');

        $response->assertStatus(404);
    }
}
