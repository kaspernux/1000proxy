<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AdminAccessAndNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'email_verified_at' => now(), 'is_active' => true]);
    }

    #[Test]
    public function roles_can_access_expected_sections()
    {
    $admin = $this->makeUser('admin');
    $manager = $this->makeUser('manager');
    $support = $this->makeUser('support_manager');
    $sales = $this->makeUser('sales_support');
    $filamentGuard = \Filament\Facades\Filament::getAuthGuard();

        // Ensure the test client uses a consistent Host for every request so
        // session cookies are scoped to the same domain (localhost) and will
        // be sent back by the Symfony test client on subsequent requests.
        $this->withHeader('Host', 'localhost');
        $this->withServerVariables([
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
        ]);

        // Clear previous run debug file (helps keep per-run output deterministic)
        @unlink(storage_path('app/test_admin_responses.json'));

        // Admin can see BI and Activity Logs
    $this->actingAs($admin, $filamentGuard);
    $this->be($admin, $filamentGuard);
    $resp = $this->get('/admin');
    $this->writeAdminDebug('/admin', $resp->getStatusCode(), $resp->headers->all());
    $resp->assertOk();
    $resp = $this->get('/admin/business-intelligence');
    $this->writeAdminDebug('/admin/business-intelligence', $resp->getStatusCode(), $resp->headers->all());
    $resp->assertOk();
    $resp = $this->get('/admin/activity-logs');
    $this->writeAdminDebug('/admin/activity-logs', $resp->getStatusCode(), $resp->headers->all());
    $resp->assertOk();

        // Manager cannot access Activity Logs (admin only), but can access customers/orders/servers
    $this->actingAs($manager, $filamentGuard);
    $this->be($manager, $filamentGuard);
    // Use a per-role stabilized chain so requests use the testing header + session
    // fallback rather than relying on fragile cookie timing.
    $managerChain = $this->withHeader('X-Testing-User', (string) $manager->id)
        ->withHeader('Host', 'localhost')
        ->withServerVariables(['HTTP_X_TESTING_USER' => (string) $manager->id, 'HTTP_HOST' => 'localhost', 'SERVER_NAME' => 'localhost'])
        ->withSession([
            'testing_auth_user_id' => $manager->id,
            'password_hash_' . ($filamentGuard ?: 'web') => $manager->getAuthPassword(),
        ]);
    // Make an initial request to establish a session cookie for the manager and reuse it.
    // Use a path that is authenticated but forbidden to managers (/admin/activity-logs)
    // so the response does not redirect to login and we can capture the session cookie.
    $init = $this->get('/admin/activity-logs');
    $this->writeAdminDebug('/admin/activity-logs (manager init)', $init->getStatusCode(), $init->headers->all());
    $cookieName = config('session.cookie');
    $cookieValue = null;
    foreach ($init->headers->getCookies() as $c) {
        if ($c->getName() === $cookieName) {
            $cookieValue = $c->getValue();
            break;
        }
    }

    // The initial request above should already be forbidden for manager; assert that.
    $init->assertForbidden();

    // Re-authenticate the manager and prefer the testing header fallback so
    // Filament's Authenticate middleware can accept the user even if the
    // PHPUnit client cookie timing is brittle. Use the explicit $manager id
    // rather than relying on auth()->id() to avoid confusion about the
    // default guard inside the test harness.
    $this->actingAs($manager, $filamentGuard);
    $this->be($manager, 'web');

    // Echo the headers/cookies the test client will send for inspection
    // Ensure Host is localhost so cookies scoped to localhost are sent by the client
    $this->withHeader('Host', 'localhost');
    $this->withServerVariables(['HTTP_HOST' => 'localhost', 'SERVER_NAME' => 'localhost']);
    $echo = $this->get('/test-echo-headers');
    $this->writeAdminDebug('/test-echo-headers (manager)', $echo->getStatusCode(), $echo->headers->all());
    // Persist the response body (contains the request headers/cookies the test client sent)
    try {
        $bodyFile = storage_path('app/test_echo_request_bodies.json');
        $existing = [];
        if (file_exists($bodyFile)) {
            $existing = json_decode(file_get_contents($bodyFile), true) ?: [];
        }
        $existing[] = json_decode($echo->getContent(), true);
        @file_put_contents($bodyFile, json_encode($existing, JSON_PRETTY_PRINT));
    } catch (\Throwable $_) {
        // noop in tests
    }

    // Stabilize: use the X-Testing-User header with the explicit manager id so
    // the Authenticate middleware testing fallback will set the guard user.
    // Ensure Host header is set on the testing client so cookies scoped to
    // localhost are sent back by the client. Persist Host on the chain.
    $chain = $this->withHeader('X-Testing-User', (string) $manager->id)
        ->withHeader('Host', 'localhost')
        ->withServerVariables(['HTTP_X_TESTING_USER' => (string) $manager->id, 'HTTP_HOST' => 'localhost', 'SERVER_NAME' => 'localhost']);
    if ($cookieValue) {
        $chain = $chain->withCookie($cookieName, $cookieValue);
    }

    // As an extra in-process guarantee, set the user on the Filament guard
    // and the default guard so middleware that reads auth() sees the user.
    try {
        auth($filamentGuard)->setUser($manager);
    } catch (\Throwable $_) {
        // noop
    }
    // do not set global Filament auth here (it persists across requests)
    $chain = $chain->withSession([
        'testing_auth_user_id' => $manager->id,
        'password_hash_' . ($filamentGuard ?: 'web') => $manager->getAuthPassword(),
    ]);

    $resp = $chain->get('/admin/customer-management/customers');
    $this->writeAdminDebug('/admin/customer-management/customers (manager)', $resp->getStatusCode(), $resp->headers->all());
    $resp->assertOk();

    // Clear testing fallbacks so they don't leak into subsequent role checks.
    // The withHeader/withServerVariables calls modify the test client instance,
    // so reset the X-Testing-User header and server var and clear test session
    // markers before other roles run.
    $this->withHeader('X-Testing-User', '');
    $this->withServerVariables(['HTTP_X_TESTING_USER' => '']);
    $this->withSession([]);

    $this->actingAs($manager, $filamentGuard);
    $this->be($manager, $filamentGuard);
    // Use the managerChain for subsequent manager requests to keep auth stable.
    if ($cookieValue) {
        $resp = $managerChain->withCookie($cookieName, $cookieValue)->get('/admin/order-management/orders');
    } else {
        $resp = $managerChain->get('/admin/order-management/orders');
    }
    $this->writeAdminDebug('/admin/order-management/orders (manager)', $resp->getStatusCode(), $resp->headers->all());
    $resp->assertOk();

    $this->actingAs($manager, 'web');
    if ($cookieValue) {
        $resp = $managerChain->withCookie($cookieName, $cookieValue)->get('/admin/server-management/servers');
    } else {
        $resp = $managerChain->get('/admin/server-management/servers');
    }
    $this->writeAdminDebug('/admin/server-management/servers (manager)', $resp->getStatusCode(), $resp->headers->all());
    $resp->assertOk();

        // Support manager: read-only sections visible
    $this->actingAs($support, $filamentGuard);
    $this->be($support, $filamentGuard);
    $supportChain = $this->withHeader('X-Testing-User', (string) $support->id)
        ->withHeader('Host', 'localhost')
        ->withServerVariables(['HTTP_X_TESTING_USER' => (string) $support->id, 'HTTP_HOST' => 'localhost', 'SERVER_NAME' => 'localhost'])
        ->withSession([
            'testing_auth_user_id' => $support->id,
            'password_hash_' . ($filamentGuard ?: 'web') => $support->getAuthPassword(),
        ]);
    $resp = $supportChain->get('/admin/customer-management/customers');
    $this->writeAdminDebug('/admin/customer-management/customers (support)', $resp->getStatusCode(), $resp->headers->all());
    $resp->assertOk();
    $resp = $this->get('/admin/server-management/servers');
    $this->writeAdminDebug('/admin/server-management/servers (support)', $resp->getStatusCode(), $resp->headers->all());
    $resp->assertOk();
    $resp = $this->get('/admin/order-management/orders');
    $this->writeAdminDebug('/admin/order-management/orders (support)', $resp->getStatusCode(), $resp->headers->all());
    $resp->assertOk();

    // Clear support test fallbacks so they don't affect subsequent role tests
    $this->withHeader('X-Testing-User', '');
    $this->withServerVariables(['HTTP_X_TESTING_USER' => '']);
    $this->withSession([]);

        // Sales support: can view customers and orders but not servers
    $this->actingAs($sales, $filamentGuard);
    $this->be($sales, $filamentGuard);
    $salesChain = $this->withHeader('X-Testing-User', (string) $sales->id)
        ->withHeader('Host', 'localhost')
        ->withServerVariables(['HTTP_X_TESTING_USER' => (string) $sales->id, 'HTTP_HOST' => 'localhost', 'SERVER_NAME' => 'localhost'])
        ->withSession([
            'testing_auth_user_id' => $sales->id,
            'password_hash_' . ($filamentGuard ?: 'web') => $sales->getAuthPassword(),
        ]);
    $resp = $salesChain->get('/admin/customer-management/customers');
    $this->writeAdminDebug('/admin/customer-management/customers (sales)', $resp->getStatusCode(), $resp->headers->all());
    $resp->assertOk();
    $resp = $salesChain->get('/admin/order-management/orders');
    $this->writeAdminDebug('/admin/order-management/orders (sales)', $resp->getStatusCode(), $resp->headers->all());
    $resp->assertOk();
    $resp = $salesChain->get('/admin/server-management/servers');
    $this->writeAdminDebug('/admin/server-management/servers (sales)', $resp->getStatusCode(), $resp->headers->all());
    $resp->assertForbidden();
    }

    private function writeAdminDebug(string $path, int $status, array $headers): void
    {
        $file = storage_path('app/test_admin_responses.json');
        $existing = [];
        if (file_exists($file)) {
            $existing = json_decode(file_get_contents($file), true) ?: [];
        }
        $existing[] = ['path' => $path, 'status' => $status, 'headers' => $headers];
        @file_put_contents($file, json_encode($existing, JSON_PRETTY_PRINT));
    }
}
