<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class CreateTestCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:create-test {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test customer for login testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        // Check if customer already exists
        $existingCustomer = Customer::where('email', $email)->first();
        if ($existingCustomer) {
            $this->info("Customer with email {$email} already exists.");
            $this->info("Updating password...");
            $existingCustomer->update(['password' => Hash::make($password)]);
            $this->info("Password updated successfully.");
            return;
        }

        // Create new customer
        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->info("Test customer created successfully!");
        $this->info("Email: {$email}");
        $this->info("Password: {$password}");
        $this->info("Customer ID: {$customer->id}");
    }
}
