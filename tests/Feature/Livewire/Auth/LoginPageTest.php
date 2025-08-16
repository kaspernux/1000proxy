<?php

namespace Tests\Feature\Livewire\Auth;

use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Auth\LoginPage;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class LoginPageTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

    // Ensure rate limiter state is clean for each test so attempts don't leak across tests
    RateLimiter::clear('login.' . request()->ip());

        $this->createTestUsers();
    }

    private function createTestUsers()
    {
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->customer = Customer::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password123')
        ]);
    }

    /** @test */
    public function login_page_renders_successfully()
    {
        Livewire::test(LoginPage::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.auth.login-page')
            ->assertSee('Sign In');
    }

    /** @test */
    public function admin_login_works_with_valid_credentials()
    {
        Livewire::test(LoginPage::class)
            ->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->call('save')
            ->assertRedirect('/admin');

        $this->assertTrue(Auth::guard('web')->check());
    }

    /** @test */
    public function customer_login_works_with_valid_credentials()
    {
        Livewire::test(LoginPage::class)
            ->set('email', 'customer@example.com')
            ->set('password', 'password123')
            ->call('save')
            ->assertRedirect('/servers');

        $this->assertTrue(Auth::guard('customer')->check());
    }

    /** @test */
    public function login_fails_with_invalid_credentials()
    {
        Livewire::test(LoginPage::class)
            ->set('email', 'nonexistent@example.com')
            ->set('password', 'wrongpassword')
            ->call('save')
            ->assertHasErrors('email');

        $this->assertFalse(Auth::guard('web')->check());
        $this->assertFalse(Auth::guard('customer')->check());
    }

    /** @test */
    public function email_validation_works()
    {
        Livewire::test(LoginPage::class)
            ->set('email', 'invalid-email')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors('email');
    }

    /** @test */
    public function password_validation_works()
    {
        Livewire::test(LoginPage::class)
            ->set('email', 'admin@example.com')
            ->set('password', '123') // Too short
            ->call('save')
            ->assertHasErrors('password');
    }

    /** @test */
    public function empty_fields_validation_works()
    {
        Livewire::test(LoginPage::class)
            ->set('email', '')
            ->set('password', '')
            ->call('save')
            ->assertHasErrors(['email', 'password']);
    }

    /** @test */
    public function password_visibility_toggle_works()
    {
        Livewire::test(LoginPage::class)
            ->assertSet('show_password', false)
            ->call('togglePasswordVisibility')
            ->assertSet('show_password', true)
            ->call('togglePasswordVisibility')
            ->assertSet('show_password', false);
    }

    /** @test */
    public function remember_me_functionality_works()
    {
        Livewire::test(LoginPage::class)
            ->set('email', 'customer@example.com')
            ->set('password', 'password123')
            ->set('remember', true)
            ->call('save')
            ->assertRedirect('/servers');

        // Verify remember token was set
        $this->assertNotNull(Auth::guard('customer')->user()->remember_token);
    }

    /** @test */
    public function rate_limiting_blocks_excessive_attempts()
    {
        $component = Livewire::test(LoginPage::class);

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $component->set('email', 'wrong@example.com')
                ->set('password', 'wrongpassword')
                ->call('save');
        }

        // 6th attempt should be rate limited
        $component->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors('email');
    }

    /** @test */
    public function rate_limit_status_updates_correctly()
    {
        // Simulate rate limiting
        $key = 'login.' . request()->ip();
        RateLimiter::hit($key, 300);
        RateLimiter::hit($key, 300);
        RateLimiter::hit($key, 300);
        RateLimiter::hit($key, 300);
        RateLimiter::hit($key, 300);

        $component = Livewire::test(LoginPage::class);

        $this->assertTrue($component->get('captcha_required'));
        $this->assertEquals(5, $component->get('login_attempts'));
    }

    /** @test */
    public function successful_login_clears_rate_limit()
    {
        $key = 'login.' . request()->ip();

        // Hit rate limit
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::hit($key, 300);
        }

        // Successful login should clear attempts
        Livewire::test(LoginPage::class)
            ->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->call('save');

        $this->assertEquals(0, RateLimiter::attempts($key));
    }

    /** @test */
    public function loading_state_works()
    {
        Livewire::test(LoginPage::class)
            ->assertSet('is_loading', false)
            ->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->call('save');

        // Loading state should be set during processing
    }

    /** @test */
    public function password_reset_request_works()
    {
        Livewire::test(LoginPage::class)
            ->set('email', 'admin@example.com')
            ->call('requestPasswordReset')
            ->assertRedirect(route('auth.forgot', ['email' => 'admin@example.com']));
    }

    /** @test */
    public function password_reset_requires_email()
    {
        Livewire::test(LoginPage::class)
            ->set('email', '')
            ->call('requestPasswordReset')
            ->assertDispatched('toast'); // Should show warning alert
    }

    /** @test */
    public function redirect_to_register_works()
    {
        Livewire::test(LoginPage::class)
            ->call('redirectToRegister')
            ->assertRedirect(route('auth.register'));
    }

    /** @test */
    public function social_login_integration_works()
    {
        Livewire::test(LoginPage::class)
            ->call('loginWithGoogle')
            ->assertDispatched('initGoogleLogin');
    }

    /** @test */
    public function github_login_redirects_correctly()
    {
        Livewire::test(LoginPage::class)
            ->call('loginWithGithub')
            ->assertRedirect(route('auth.github.redirect'));
    }

    /** @test */
    public function social_login_success_handler_works()
    {
        Livewire::test(LoginPage::class)
            ->dispatch('socialLoginSuccess', 'google', ['user' => 'test'])
            ->assertRedirect('/servers');
    }

    /** @test */
    public function authenticated_admin_redirects_to_admin()
    {
        Auth::guard('web')->login($this->adminUser);

        $this->get(route('auth.login'))
            ->assertRedirect('/admin');
    }

    /** @test */
    public function authenticated_customer_redirects_to_customer()
    {
        Auth::guard('customer')->login($this->customer);

        $this->get(route('auth.login'))
            ->assertRedirect('/servers');
    }

    /** @test */
    public function intended_url_is_preserved()
    {
        session()->put('url.intended', '/products/premium-plan');

        Livewire::test(LoginPage::class)
            ->assertSet('redirect_after_login', '/products/premium-plan');
    }

    /** @test */
    public function captcha_required_after_multiple_failures()
    {
        $component = Livewire::test(LoginPage::class);

        // Simulate multiple failed attempts
        for ($i = 0; $i < 5; $i++) {
            $component->set('email', 'wrong@example.com')
                ->set('password', 'wrongpassword')
                ->call('save');
        }

        $this->assertTrue($component->get('captcha_required'));
    }

    /** @test */
    public function session_regenerates_on_successful_login()
    {
        $oldSessionId = session()->getId();

        Livewire::test(LoginPage::class)
            ->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->call('save');

        $this->assertNotEquals($oldSessionId, session()->getId());
    }

    /** @test */
    public function customer_last_login_is_recorded()
    {
        Livewire::test(LoginPage::class)
            ->set('email', 'customer@example.com')
            ->set('password', 'password123')
            ->call('save');

        $this->assertNotNull(session('customer_last_login'));
    }
}
