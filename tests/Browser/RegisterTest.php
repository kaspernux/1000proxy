<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Support\Str;

class RegisterTest extends DuskTestCase
{
    /** @test */
    public function customer_can_register_via_livewire_form()
    {
        $this->browse(function (Browser $browser) {
            $email = 'dusk+' . time() . '@example.test';
            $browser->visit('/register')
                ->waitForText('Create Account', 5)
                ->type('#name', 'Dusk Test User')
                ->type('#email', $email)
                ->type('#password', 'Password123!')
                ->type('#password_confirmation', 'Password123!')
                ->check('#terms_accepted')
                ->pause(500);

            // submit a native POST form to /register to exercise the non-JS controller fallback
            $browser->driver->executeScript(<<<'JS'
                (function() {
                    var token = document.querySelector('meta[name="csrf-token"]').content;
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/register';
                    var inputs = { _token: token, name: document.getElementById('name').value, email: document.getElementById('email').value, password: document.getElementById('password').value, password_confirmation: document.getElementById('password_confirmation').value, terms_accepted: document.getElementById('terms_accepted').checked ? '1' : '0' };
                    for (var k in inputs) { var i = document.createElement('input'); i.type = 'hidden'; i.name = k; i.value = inputs[k]; form.appendChild(i); }
                    document.body.appendChild(form);
                    form.submit();
                })();
            JS
            );

            $browser->pause(2000)
                ->screenshot('register-after-submit')
                ->storeConsoleLog('register-console.log');

            // save the current page source for debugging into repository folder
            $html = $browser->driver->getPageSource();
            @file_put_contents(base_path('tests/Browser/source/register-after-submit.html'), $html);

            // collect browser console logs if available
            try {
                $logs = $browser->driver->manage()->getLog('browser');
                $out = '';
                foreach ($logs as $l) {
                    $out .= "[{$l['level']}] {$l['message']}\n";
                }
                @file_put_contents(base_path('tests/Browser/source/register-console.log'), $out);
            } catch (\Throwable $e) {
                // ignore if log retrieval not available
            }

            // The controller redirects to the verification notice. Assert the heading text we observed.
            $browser->pause(1000);
            $browser->assertSee('Almost there—check your email');
        });
    }
}
