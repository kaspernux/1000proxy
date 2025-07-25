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

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function mobile_layout_loads_correctly()
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
            ])
            ->get('/dashboard');

        $response->assertOk()
            ->assertViewHas('mobileClasses')
            ->assertSee('mobile-nav')
            ->assertSee('device-mobile');
    }

    /** @test */
    public function touch_targets_are_properly_sized()
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
            ])
            ->get('/dashboard');

        // Check that buttons have minimum touch target size
        $response->assertSee('min-height: 44px')
            ->assertSee('min-width: 44px')
            ->assertSee('touch-action: manipulation');
    }

    /** @test */
    public function viewport_meta_tag_is_present()
    {
        $response = $this->get('/');

        $response->assertSee('name="viewport"')
            ->assertSee('width=device-width')
            ->assertSee('initial-scale=1.0')
            ->assertSee('user-scalable=no');
    }

    /** @test */
    public function mobile_navigation_works()
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
            ])
            ->get('/dashboard');

        $response->assertSee('mobile-nav-toggle')
            ->assertSee('mobile-nav-menu')
            ->assertSee('aria-expanded="false"')
            ->assertSee('role="menu"');
    }

    /** @test */
    public function responsive_grid_system_works()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertSee('grid-template-columns: 1fr') // Mobile first
            ->assertSee('@media (min-width: 640px)') // Small screens
            ->assertSee('@media (min-width: 768px)') // Medium screens
            ->assertSee('@media (min-width: 1024px)'); // Large screens
    }

    /** @test */
    public function form_inputs_are_mobile_friendly()
    {
        $response = $this->get('/login');

        // Check that inputs have minimum size and proper spacing
        $response->assertSee('min-height: 44px')
            ->assertSee('font-size: 1rem') // Prevent zoom on iOS
            ->assertSee('padding: 0.75rem');
    }

    /** @test */
    public function tables_are_responsive()
    {
        Order::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get('/orders');

        $response->assertSee('table-responsive')
            ->assertSee('overflow-x: auto')
            ->assertSee('-webkit-overflow-scrolling: touch');
    }

    /** @test */
    public function images_are_optimized_for_mobile()
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
            ])
            ->get('/dashboard');

        // Check for lazy loading and responsive images
        $response->assertSee('data-src')
            ->assertSee('loading="lazy"');
    }

    /** @test */
    public function performance_optimizations_apply_for_low_end_devices()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 4.4.2; SM-G355H) AppleWebKit/537.36'
        ])
        ->get('/dashboard');

        $response->assertSee('performance-low')
            ->assertSee('animation: none !important')
            ->assertSee('transition: none !important');
    }

    /** @test */
    public function swipe_gestures_are_enabled()
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
            ])
            ->get('/dashboard');

        $response->assertSee('touchstart')
            ->assertSee('touchend')
            ->assertSee('handleSwipeRight')
            ->assertSee('handleSwipeLeft');
    }

    /** @test */
    public function pwa_manifest_is_accessible()
    {
        $response = $this->get('/manifest.json');

        $response->assertOk()
            ->assertJsonStructure([
                'name',
                'short_name',
                'start_url',
                'display',
                'theme_color',
                'background_color',
                'icons'
            ]);
    }

    /** @test */
    public function service_worker_registration_works()
    {
        $response = $this->get('/sw.js');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/javascript');
    }

    /** @test */
    public function mobile_forms_prevent_zoom()
    {
        $response = $this->get('/register');

        // Check that input font-size is 16px or larger to prevent zoom
        $response->assertSeeInOrder([
            'input',
            'font-size: 1rem' // 16px
        ]);
    }

    /** @test */
    public function accessibility_features_work_on_mobile()
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
            ])
            ->get('/dashboard');

        $response->assertSee('Skip to main content')
            ->assertSee('role="navigation"')
            ->assertSee('aria-label')
            ->assertSee('sr-only');
    }

    /** @test */
    public function mobile_orientation_handling_works()
    {
        $response = $this->get('/dashboard');

        $response->assertSee('orientationchange')
            ->assertSee('setVH')
            ->assertSee('--vh');
    }

    /** @test */
    public function mobile_performance_monitoring_active()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
        ])
        ->get('/dashboard');

        $response->assertSee('window.mobileOptimization')
            ->assertSee('deviceInfo')
            ->assertSee('performanceSettings');
    }

    /** @test */
    public function reduced_motion_preference_respected()
    {
        $response = $this->get('/dashboard');

        $response->assertSee('@media (prefers-reduced-motion: reduce)')
            ->assertSee('animation-duration: 0.01ms !important')
            ->assertSee('transition-duration: 0.01ms !important');
    }

    /** @test */
    public function high_contrast_mode_supported()
    {
        $response = $this->get('/dashboard');

        $response->assertSee('@media (prefers-contrast: high)')
            ->assertSee('border: 2px solid currentColor');
    }

    /** @test */
    public function mobile_error_handling_works()
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'wrong'
        ], [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
        ]);

        $response->assertSessionHasErrors()
            ->assertSee('alert-error')
            ->assertSee('aria-live="assertive"');
    }

    /** @test */
    public function mobile_loading_states_work()
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
            ])
            ->get('/orders/create');

        $response->assertSee('loading')
            ->assertSee('aria-busy="true"')
            ->assertSee('Loading content');
    }

    /** @test */
    public function mobile_offline_support_works()
    {
        $response = $this->get('/sw.js');

        $response->assertOk()
            ->assertSee('cache')
            ->assertSee('offline')
            ->assertSee('fetch');
    }

    /** @test */
    public function mobile_analytics_tracking_works()
    {
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
        ])
        ->get('/dashboard');

        // Check that mobile analytics are being tracked
        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'page_view',
            'device_type' => 'mobile'
        ]);
    }

    /** @test */
    public function tablet_layout_differs_from_mobile()
    {
        $mobileResponse = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/dashboard');

        $tabletResponse = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/dashboard');

        $mobileResponse->assertSee('device-mobile');
        $tabletResponse->assertSee('device-tablet');
    }

    /** @test */
    public function critical_css_is_inlined()
    {
        $response = $this->get('/dashboard');

        // Critical CSS should be inlined in <style> tags
        $response->assertSee('<style>')
            ->assertSee('/* Critical CSS')
            ->assertSee('body {')
            ->assertSee('margin: 0;');
    }

    /** @test */
    public function non_critical_css_is_deferred()
    {
        $response = $this->get('/dashboard');

        // Non-critical CSS should be loaded asynchronously
        $response->assertSee('media="print"')
            ->assertSee('onload="this.media=\'all\'"');
    }

    /** @test */
    public function mobile_search_functionality_works()
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
            ])
            ->get('/dashboard');

        $response->assertSee('role="search"')
            ->assertSee('input[type="search"]')
            ->assertSee('aria-label="Search"');
    }
}
