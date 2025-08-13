<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Server;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class MobileResponsivenessTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
    }

    /**
     * NOTE: The original comprehensive mobile optimization feature set (device classes,
     * performance tuning, swipe gestures, analytics, etc.) has been intentionally removed
     * from the application. This test suite has been simplified to reflect the current
     * minimal baseline: core pages should respond successfully for authenticated and
     * guest users. If mobile features are reintroduced in the future, restore or expand
     * the assertions accordingly (see git history of this file for reference).
     */

    /** @test */
    public function home_page_loads()
    {
        $this->get('/')->assertOk();
    }

    /** @test */
    public function login_and_register_pages_load()
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    /** @test */
    public function dashboard_requires_authentication()
    {
        $this->get('/dashboard')->assertStatus(302); // redirect to login
        $this->actingAs($this->user)->get('/dashboard')->assertOk();
    }

    /** @test */
    public function orders_page_loads_for_authenticated_user()
    {
        $this->actingAs($this->user)->get('/orders')->assertOk();
    }

    /** @test */
    public function not_found_page_returns_404()
    {
        $this->get('/definitely-not-a-real-page-xyz')->assertNotFound();
    }
}
