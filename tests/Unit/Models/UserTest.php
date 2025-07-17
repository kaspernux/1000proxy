<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Order;
use App\Models\ServerPlan;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_has_role_method_works()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $regularUser = User::factory()->create(['role' => 'user']);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('user'));
        $this->assertTrue($regularUser->hasRole('user'));
        $this->assertFalse($regularUser->hasRole('admin'));
    }

    public function test_admin_can_access_panel()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->assertTrue($admin->canAccessPanel());
        $this->assertFalse($user->canAccessPanel());
    }

    public function test_specific_emails_can_access_panel()
    {
        $adminEmail = 'admin@1000proxy.io';
        $user = User::factory()->create(['email' => $adminEmail]);

        $this->assertTrue($user->canAccessPanel());
    }

    public function test_user_can_have_orders()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->orders->contains($order));
        $this->assertEquals($user->id, $order->user_id);
    }

    public function test_user_last_login_is_updated()
    {
        $user = User::factory()->create(['last_login_at' => null]);

        $this->assertNull($user->last_login_at);

        $user->update(['last_login_at' => now()]);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_user_can_be_deactivated()
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->assertTrue($user->is_active);

        $user->update(['is_active' => false]);

        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_user_fillable_attributes()
    {
        $user = new User();
        $fillable = $user->getFillable();

        $expectedFillable = [
            'name', 'email', 'password', 'role', 'is_active', 'last_login_at'
        ];

        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }

    public function test_user_hidden_attributes()
    {
        $user = User::factory()->create();
        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    public function test_user_casts()
    {
        $user = new User();
        $casts = $user->getCasts();

        $this->assertEquals('datetime', $casts['email_verified_at']);
        $this->assertEquals('datetime', $casts['last_login_at']);
        $this->assertEquals('boolean', $casts['is_active']);
    }
}
