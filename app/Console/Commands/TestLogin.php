<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class TestLogin extends Command
{
    protected $signature = 'test:login {email} {password}';
    protected $description = 'Test customer login credentials';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $this->info("Testing login for: {$email}");

        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            $this->error('Customer not found!');
            return 1;
        }

        $this->info("Customer found: {$customer->name}");
        $this->info("Customer ID: {$customer->id}");
        $this->info("Is Active: " . ($customer->is_active ? 'Yes' : 'No'));
        $this->info("Email Verified: " . ($customer->email_verified_at ? 'Yes' : 'No'));

        if (Hash::check($password, $customer->password)) {
            $this->info('✅ Password check: PASSED');
        } else {
            $this->error('❌ Password check: FAILED');
        }

        // Test Auth attempt
        if (Auth::guard('customer')->attempt(['email' => $email, 'password' => $password])) {
            $this->info('✅ Auth attempt: SUCCESS');
            Auth::guard('customer')->logout();
        } else {
            $this->error('❌ Auth attempt: FAILED');
        }

        return 0;
    }
}
