<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Services\XUIService;
use Illuminate\Support\Facades\URL;
use Tests\Fakes\FakeXUIService;
use App\Models\Server;

abstract class TestCase extends BaseTestCase
{
    // Define default headers in setUp using withHeaders() to avoid property signature mismatches

    protected function setUp(): void
    {
        parent::setUp();
        // Force non-JSON Accept to exercise web redirect + session errors
        $this->withHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ]);
        // Ensure PHPUnit runs always detect testing environment for provisioning logic
        try {
            putenv('APP_ENV=testing');
            $_ENV['APP_ENV'] = 'testing';
            $_SERVER['APP_ENV'] = 'testing';
            $this->app['env'] = 'testing';
            \Illuminate\Support\Facades\Config::set('app.env', 'testing');
            // Enable detailed provisioning snapshots for easier triage in tests
            \Illuminate\Support\Facades\Config::set('provision.debug_xui', true);

            // Ensure cookies are issued for the test host (localhost) during tests.
            // In CI the app may be configured with a production domain which causes
            // Set-Cookie to be scoped to that domain (e.g. 1000proxy.io) and the
            // HTTP client used by PHPUnit won't send that cookie back for
            // requests made to the test host. Force a localhost URL and clear
            // any session domain to ensure cookies are written for the test
            // client and are sent on subsequent requests.
            \Illuminate\Support\Facades\Config::set('app.url', 'http://localhost');
            \Illuminate\Support\Facades\Config::set('session.domain', null);
            \Illuminate\Support\Facades\Config::set('session.secure', false);
            \Illuminate\Support\Facades\Config::set('session.secure_cookie', false);
            // Ensure the URL generator and test client use localhost as the root
            try {
                URL::forceRootUrl('http://localhost');
            } catch (\Throwable $_) {}
        } catch (\Throwable $_) {}
        // Force the test client's server variables to use localhost as the Host
        // so cookies issued for the test host are sent on subsequent requests.
        try {
            $this->withServerVariables([
                'HTTP_HOST' => 'localhost',
                'SERVER_NAME' => 'localhost',
            ]);
        } catch (\Throwable $_) {}
        // Also ensure PHP superglobals reflect localhost so Symfony client uses it
        try {
            $_SERVER['HTTP_HOST'] = 'localhost';
            $_SERVER['SERVER_NAME'] = 'localhost';
        } catch (\Throwable $_) {}
        // Ensure the test client has an active session so cookies (session cookie)
        // are created and persisted between requests during feature tests. Start
        // the session after we've configured app.url and session domain so the
        // Set-Cookie header is scoped correctly for the test client.
        try {
            $this->startSession();
        } catch (\Throwable $_) {}
        // Bind XUIService for tests. Use the real service so tests that call Http::fake()
        // will control HTTP interactions; fall back to creating a Server if none is provided.
        $this->app->bind(XUIService::class, function ($app, $params) {
            $server = $params['server'] ?? Server::factory()->create();
            return new \App\Services\XUIService($server);
        });
        // Defensive: ensure no lingering customer guard session exists which can
        // cause RedirectIfCustomer middleware to trigger 302 redirects in tests
        // where we expect a web staff user to be active.
        try {
            \Illuminate\Support\Facades\Auth::guard('customer')->logout();
        } catch (\Throwable $_) {}
    }

    /**
     * Ensure tests that call actingAs also expose a lightweight testing header
     * so our EarlyTestAuth / ProbeAdminAuth middleware can set the guard user
     * and minimal session markers before Filament's authentication runs.
     */
    public function actingAs($user, $guard = null)
    {
        // Prepare headers, server vars, session markers and cookie BEFORE calling parent::actingAs
        try {
            $id = is_object($user) ? ($user->id ?? null) : $user;
            if ($id) {
                // Add both header and server var to be robust across request styles
                $this->withHeader('X-Testing-User', $id);
                $this->withServerVariables(['HTTP_X_TESTING_USER' => $id]);
                // Also set PHP superglobal so any low-level inspect sees it
                $_SERVER['HTTP_X_TESTING_USER'] = $id;

                // Ensure session contains the minimal markers used by ProbeAdminAuth/Authenticate
                try {
                    $pwKey = 'password_hash_' . ($guard ?: 'web');
                    $this->withSession([
                        $pwKey => is_object($user) ? ($user->getAuthPassword() ?? null) : null,
                        'testing_auth_user_id' => $id,
                    ]);
                    // Also set a cookie for the session id so Symfony client sends it on subsequent requests
                    $sessionCookie = config('session.cookie', session_name());
                    $sessionId = $this->app['session']->getId();
                    if ($sessionId) {
                        $this->withCookie($sessionCookie, $sessionId);
                        $_COOKIE[$sessionCookie] = $sessionId;
                    }
                } catch (\Throwable $_) {}

                // Also proactively set the auth user on the application so any immediate
                // middleware that checks auth() will see the user even before the
                // testing middlewares run.
                try {
                    auth()->setUser(is_object($user) ? $user : (\App\Models\User::find($id) ?: null));
                    auth('web')->setUser(is_object($user) ? $user : (\App\Models\User::find($id) ?: null));
                } catch (\Throwable $_) {}
            }
        } catch (\Throwable $_) {}

        // Emit a quick debug line before delegating to the parent so we can
        // observe the session id / Cookie header that the test client has
        // prepared for the request. This helps diagnose why some admin
        // requests are redirected due to missing session state.
        try {
            $preSessionId = $this->app['session']->getId() ?? null;
            $preCookie = $_SERVER['HTTP_COOKIE'] ?? null;
            $preUserId = is_object($user) ? ($user->id ?? null) : $user;
            error_log('[TESTCASE actingAs BEFORE] user_id=' . ($preUserId ?? 'NULL') . ' session_id=' . ($preSessionId ?? 'NULL') . ' cookie=' . ($preCookie ?? 'NULL'));
        } catch (\Throwable $_) {}

        $res = parent::actingAs($user, $guard);

        // After parent::actingAs ensure the test client has the session cookie
        try {
            $sessionCookie = config('session.cookie', session_name());
            $sessionId = $this->app['session']->getId();
            if ($sessionId) {
                $this->withCookie($sessionCookie, $sessionId);
                $_COOKIE[$sessionCookie] = $sessionId;
                // Also set a raw Cookie header so DebugAdminRedirects can inspect it
                $this->withHeader('Cookie', $sessionCookie . '=' . $sessionId);
                $_SERVER['HTTP_COOKIE'] = $sessionCookie . '=' . $sessionId;
            }
        } catch (\Throwable $_) {}

        // Emit another debug line after parent::actingAs and after we ensure
        // the session cookie and raw Cookie header are present. This gives a
        // clear before/after snapshot for each actingAs call in tests.
        try {
            $postSessionId = $this->app['session']->getId() ?? null;
            $postCookie = $_SERVER['HTTP_COOKIE'] ?? null;
            $postUserId = is_object($user) ? ($user->id ?? null) : $user;
            error_log('[TESTCASE actingAs AFTER] user_id=' . ($postUserId ?? 'NULL') . ' session_id=' . ($postSessionId ?? 'NULL') . ' cookie=' . ($postCookie ?? 'NULL'));
        } catch (\Throwable $_) {}

        // Ensure the application session store itself contains the minimal
        // testing markers used by ProbeAdminAuth / Authenticate. Writing them
        // directly to the app session reduces flakiness where the Symfony
        // client sends the cookie but the session payload on the server is
        // missing expected keys.
        try {
            $id = is_object($user) ? ($user->id ?? null) : $user;
            if ($id && $this->app->bound('session')) {
                $pwKey = 'password_hash_' . ($guard ?: 'web');
                $pwVal = is_object($user) ? ($user->getAuthPassword() ?? null) : null;
                $this->app['session']->put($pwKey, $pwVal);

                // If the provided model is a Customer (used by customer-facing routes),
                // also persist the customer guard session marker and set the customer guard
                // user so auth:customer middleware recognizes the user during tests.
                try {
                    if (is_object($user) && $user instanceof \App\Models\Customer) {
                        $this->app['session']->put('password_hash_customer', $pwVal);
                        // Ensure the customer guard is set to the same user instance
                        try { auth('customer')->setUser($user); } catch (\Throwable $_) {}
                    }
                } catch (\Throwable $_) {}

                $this->app['session']->put('testing_auth_user_id', $id);
                $this->app['session']->save();
                error_log('[TESTCASE actingAs SAVED SESSION] user_id=' . ($id ?? 'NULL') . ' session_id=' . ($this->app['session']->getId() ?? 'NULL'));
            }
        } catch (\Throwable $_) {}

        return $res;
    }
}
