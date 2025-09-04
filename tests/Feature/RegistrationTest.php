<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Customer;
use PHPUnit\Framework\Attributes\Test;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function register_creates_customer_not_user(): void
    {
        $usersBefore = User::count();
        $customersBefore = Customer::count();

        $response = $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'test+reg@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true,
        ]);

        // Expect a redirect (web flow sends redirect to verification notice)
        $response->assertStatus(302);

        $this->assertEquals($usersBefore, User::count(), 'A User row should not be created by public /register');
        $this->assertEquals($customersBefore + 1, Customer::count(), 'A Customer row should be created by /register');
    }
}
