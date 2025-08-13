<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BaselinePagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
    }

    /** @test */
    public function home_page_loads()
    {
        $this->get('/')->assertOk();
    }

    /** @test */
    public function auth_pages_load()
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    /** @test */
    public function dashboard_requires_authentication_and_then_loads()
    {
        $this->get('/dashboard')->assertStatus(302);
        $this->actingAs($this->user)->get('/dashboard')->assertOk();
    }

    /** @test */
    public function unknown_page_returns_404()
    {
        $this->get('/definitely-not-a-real-page-xyz')->assertNotFound();
    }
}
