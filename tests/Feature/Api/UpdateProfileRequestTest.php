<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateProfileRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'name' => 'Original Name',
            'username' => 'original_username',
            'email' => 'original@example.com',
        ]);
    }

    public function test_update_profile_with_valid_data()
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'name' => 'Updated Name',
                'username' => 'updated_username',
                'email' => 'updated@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'username',
                        'email',
                        'role'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'username' => 'updated_username',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_update_profile_with_partial_data()
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'name' => 'Updated Name Only',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name Only',
            'username' => 'original_username',
            'email' => 'original@example.com',
        ]);
    }

    public function test_update_profile_validates_name_length()
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'name' => str_repeat('a', 256), // 256 characters
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_profile_validates_username_uniqueness()
    {
        $otherUser = User::factory()->create(['username' => 'taken_username']);

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'username' => 'taken_username',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_update_profile_validates_email_uniqueness()
    {
        $otherUser = User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'email' => 'taken@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_profile_validates_email_format()
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'email' => 'invalid-email-format',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_profile_allows_same_user_to_keep_existing_values()
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'username' => $this->user->username,
                'email' => $this->user->email,
            ]);

        $response->assertStatus(200);
    }

    public function test_update_profile_validates_username_length()
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'username' => str_repeat('a', 256), // 256 characters
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_update_profile_validates_username_format()
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'username' => 'invalid username with spaces',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_update_profile_validates_phone_format()
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'phone' => 'invalid-phone',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_update_profile_accepts_valid_phone()
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'phone' => '+1234567890',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'phone' => '+1234567890',
        ]);
    }

    public function test_unauthenticated_user_cannot_update_profile()
    {
        $response = $this->putJson('/api/v1/profile', [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_profile_ignores_unauthorized_fields()
    {
        $originalRole = $this->user->role;
        $originalId = $this->user->id;

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'name' => 'Updated Name',
                'role' => 'admin',
                'id' => 999,
                'created_at' => now(),
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $originalId,
            'name' => 'Updated Name',
            'role' => $originalRole,
        ]);
    }
}
