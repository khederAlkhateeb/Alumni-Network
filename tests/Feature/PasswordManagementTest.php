<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Class PasswordManagementTest
 *
 * Feature tests for user password management including changing passwords,
 * requesting reset links, and resetting forgotten passwords.
 */
class PasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('OldPassword123!')
        ]);
    }

    /**
     * Ensure an authenticated user can change their password successfully.
     */
    public function test_authenticated_user_can_change_password(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/auth/change-password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewStrongPassword123!',
            'password_confirmation' => 'NewStrongPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Password changed successfully.',
            ]);

        $this->assertTrue(Hash::check('NewStrongPassword123!', $this->user->fresh()->password));
    }

    /**
     * Ensure an unauthenticated user gets a 401 error when trying to change password.
     */
    public function test_unauthenticated_user_cannot_change_password(): void
    {
        $response = $this->putJson('/api/v1/auth/change-password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewStrongPassword123!',
            'password_confirmation' => 'NewStrongPassword123!',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Ensure the system sends a reset link when a valid email is provided.
     */
    public function test_user_can_request_password_reset_link(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $this->user->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', __(Password::RESET_LINK_SENT));
    }

    /**
     * Ensure the API returns a 400 bad request if the broker fails.
     */
    public function test_forgot_password_returns_400_on_broker_failure(): void
    {
        // First request works fine
        $this->postJson('/api/v1/auth/forgot-password', ['email' => $this->user->email]);

        // Second immediate request should be throttled
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $this->user->email,
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure(['message']);
    }

    /**
     * Ensure a user can reset their password using a valid token and email.
     */
    public function test_user_can_reset_password_with_valid_token(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $this->user->email,
            'token' => $token,
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', __(Password::PASSWORD_RESET));

        $this->assertTrue(Hash::check('BrandNewPassword123!', $this->user->fresh()->password));
    }

    /**
     * Ensure the API rejects a password reset attempt with an invalid token.
     */
    public function test_user_cannot_reset_password_with_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $this->user->email,
            'token' => 'invalid-fake-token-string',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => __(Password::INVALID_TOKEN)
            ]);
    }
}
