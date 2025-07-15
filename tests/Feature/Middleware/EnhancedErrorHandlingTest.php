<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\EnhancedErrorHandling;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class EnhancedErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_middleware_logs_slow_requests()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Slow request detected', \Mockery::type('array'));

        $middleware = new EnhancedErrorHandling();

        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($request) {
            // Simulate slow request
            usleep(3000000); // 3 seconds
            return response()->json(['message' => 'success']);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_handles_validation_errors()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                // Missing required fields
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ])
            ->assertJson([
                'success' => false,
                'message' => 'The given data was invalid.'
            ]);
    }

    public function test_middleware_handles_authentication_errors()
    {
        $response = $this->postJson('/api/orders', [
            'server_id' => 1,
            'plan_id' => 1,
            'quantity' => 1,
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.'
            ]);
    }

    public function test_middleware_handles_authorization_errors()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/orders/' . $otherUser->id);

        $response->assertStatus(403)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.'
            ]);
    }

    public function test_middleware_handles_not_found_errors()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/orders/999999');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found.'
            ]);
    }

    public function test_middleware_handles_rate_limit_errors()
    {
        // Make multiple requests to trigger rate limiting
        for ($i = 0; $i < 62; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/orders', [
                    'server_id' => 1,
                    'plan_id' => 1,
                    'quantity' => 1,
                ]);
        }

        $response->assertStatus(429)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Too many requests.'
            ]);
    }

    public function test_middleware_handles_server_errors()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Server error occurred', \Mockery::type('array'));

        $middleware = new EnhancedErrorHandling();

        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($request) {
            throw new \Exception('Test server error');
        });

        $this->assertEquals(500, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('An error occurred while processing your request.', $responseData['message']);
    }

    public function test_middleware_includes_debug_info_in_development()
    {
        config(['app.debug' => true]);

        $middleware = new EnhancedErrorHandling();

        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($request) {
            throw new \Exception('Test error with debug info');
        });

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('debug', $responseData);
        $this->assertArrayHasKey('exception', $responseData['debug']);
        $this->assertArrayHasKey('trace', $responseData['debug']);
    }

    public function test_middleware_hides_debug_info_in_production()
    {
        config(['app.debug' => false]);

        $middleware = new EnhancedErrorHandling();

        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($request) {
            throw new \Exception('Test error in production');
        });

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayNotHasKey('debug', $responseData);
    }

    public function test_middleware_includes_request_id_in_response()
    {
        $middleware = new EnhancedErrorHandling();

        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($request) {
            return response()->json(['message' => 'success']);
        });

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('request_id', $responseData);
        $this->assertIsString($responseData['request_id']);
    }

    public function test_middleware_logs_error_with_context()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Server error occurred', \Mockery::on(function ($context) {
                return isset($context['request_id']) &&
                       isset($context['user_id']) &&
                       isset($context['url']) &&
                       isset($context['method']) &&
                       isset($context['exception']);
            }));

        $middleware = new EnhancedErrorHandling();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $middleware->handle($request, function ($request) {
            throw new \Exception('Test error with context');
        });

        $this->assertEquals(500, $response->getStatusCode());
    }
}
