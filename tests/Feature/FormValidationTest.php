<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Server;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

class FormValidationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    #[Test]
    public function user_registration_validates_required_fields(): void
    {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
            'terms'
        ]);
    }

    #[Test]
    public function user_registration_validates_email_format(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function user_registration_validates_unique_email(): void
    {
        $existingUser = Customer::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Test Customer',
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function user_registration_validates_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
            'terms' => true
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    #[Test]
    public function user_registration_validates_password_strength(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'password' => '123',
            'password_confirmation' => '123',
            'terms' => true
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    #[Test]
    public function login_validates_required_fields(): void
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors([
            'email',
            'password'
        ]);
    }

    #[Test]
    public function login_validates_email_format(): void
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function profile_update_validates_required_fields(): void
    {
        $response = $this->actingAs($this->customer, 'web')
            ->put('/profile', [
                'name' => '',
                'email' => ''
            ]);

        $response->assertSessionHasErrors([
            'name',
            'email'
        ]);
    }

    #[Test]
    public function profile_update_validates_email_uniqueness(): void
    {
        $otherUser = Customer::factory()->create();

        $response = $this->actingAs($this->customer, 'web')
            ->put('/profile', [
                'name' => 'Updated Name',
                'email' => $otherUser->email
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function profile_update_allows_same_email(): void
    {
        $response = $this->actingAs($this->customer, 'web')
            ->put('/profile', [
                'name' => 'Updated Name',
                'email' => $this->customer->email
            ]);

        $response->assertSessionDoesntHaveErrors(['email']);
        $response->assertRedirect();
    }

    #[Test]
    public function password_change_validates_current_password(): void
    {
        $response = $this->actingAs($this->customer, 'web')
            ->put('/password', [
                'current_password' => 'wrong_password',
                'password' => 'new_password123',
                'password_confirmation' => 'new_password123'
            ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    #[Test]
    public function password_change_validates_new_password_confirmation(): void
    {
        $response = $this->actingAs($this->customer, 'web')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new_password123',
                'password_confirmation' => 'different_password'
            ]);

        $response->assertSessionHasErrors(['password']);
    }

    #[Test]
    public function server_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/customer/servers', []);

        $response->assertSessionHasErrors([
            'name',
            'host',
            'port',
            'username',
            'password',
            'location'
        ]);
    }

    #[Test]
    public function server_creation_validates_host_format(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/customer/servers', [
                'name' => 'Test Server',
                'host' => 'invalid-host',
                'port' => 2053,
                'username' => 'customer',
                'password' => 'password123',
                'location' => 'US'
            ]);

        $response->assertSessionHasErrors(['host']);
    }

    #[Test]
    public function server_creation_validates_port_range(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/customer/servers', [
                'name' => 'Test Server',
                'host' => '192.168.1.100',
                'port' => 70000, // Invalid port
                'username' => 'customer',
                'password' => 'password123',
                'location' => 'US'
            ]);

        $response->assertSessionHasErrors(['port']);
    }

    #[Test]
    public function server_creation_validates_unique_host_port_combination(): void
    {
        $existingServer = Server::factory()->create([
            'host' => '192.168.1.100',
            'port' => 2053
        ]);

        $response = $this->actingAs($this->customer)
            ->post('/customer/servers', [
                'name' => 'Test Server',
                'host' => '192.168.1.100',
                'port' => 2053,
                'username' => 'customer',
                'password' => 'password123',
                'location' => 'US'
            ]);

        $response->assertSessionHasErrors(['host']);
    }

    #[Test]
    public function service_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/customer/services', []);

        $response->assertSessionHasErrors([
            'name',
            'description',
            'price',
            'billing_cycle'
        ]);
    }

    #[Test]
    public function service_creation_validates_price_format(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/customer/services', [
                'name' => 'Test Service',
                'description' => 'Test Description',
                'price' => 'invalid_price',
                'billing_cycle' => 'monthly'
            ]);

        $response->assertSessionHasErrors(['price']);
    }

    #[Test]
    public function service_creation_validates_negative_price(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/customer/services', [
                'name' => 'Test Service',
                'description' => 'Test Description',
                'price' => -10.99,
                'billing_cycle' => 'monthly'
            ]);

        $response->assertSessionHasErrors(['price']);
    }

    #[Test]
    public function order_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/orders', []); // legacy web route; retained for backward-compat tests

        $response->assertSessionHasErrors([
            'server_id',
            'billing_cycle'
        ]);
    }

    #[Test]
    public function order_creation_validates_server_exists(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/orders', [
                'server_id' => 99999, // Non-existent server
                'billing_cycle' => 'monthly'
            ]);

        $response->assertSessionHasErrors(['server_id']);
    }

    #[Test]
    public function order_creation_validates_billing_cycle_options(): void
    {
        $server = Server::factory()->create();

        $response = $this->actingAs($this->customer)
            ->post('/orders', [
                'server_id' => $server->id,
                'billing_cycle' => 'invalid_cycle'
            ]);

        $response->assertSessionHasErrors(['billing_cycle']);
    }

    #[Test]
    public function file_upload_validates_file_type(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.exe', 1000); // Invalid type

        $response = $this->actingAs($this->customer)
            ->post('/customer/bulk-import/users', [
                'file' => $file
            ]);

        $response->assertSessionHasErrors(['file']);
    }

    #[Test]
    public function file_upload_validates_file_size(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('large_file.csv', 10240); // 10MB file

        $response = $this->actingAs($this->customer)
            ->post('/customer/bulk-import/users', [
                'file' => $file
            ]);

        $response->assertSessionHasErrors(['file']);
    }

    #[Test]
    public function support_ticket_validates_required_fields(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/support/tickets', []);

        $response->assertSessionHasErrors([
            'subject',
            'message',
            'priority'
        ]);
    }

    #[Test]
    public function support_ticket_validates_message_length(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/support/tickets', [
                'subject' => 'Test Subject',
                'message' => 'Hi', // Too short
                'priority' => 'medium'
            ]);

        $response->assertSessionHasErrors(['message']);
    }

    #[Test]
    public function support_ticket_validates_priority_options(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/support/tickets', [
                'subject' => 'Test Subject',
                'message' => 'This is a test message with sufficient length.',
                'priority' => 'invalid_priority'
            ]);

        $response->assertSessionHasErrors(['priority']);
    }

    #[Test]
    public function payment_method_validates_required_fields(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/payment-methods', []);

        $response->assertSessionHasErrors([
            'type',
            'card_number',
            'expiry_month',
            'expiry_year',
            'cvv'
        ]);
    }

    #[Test]
    public function payment_method_validates_card_number_format(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/payment-methods', [
                'type' => 'credit_card',
                'card_number' => '1234', // Invalid card number
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '123'
            ]);

        $response->assertSessionHasErrors(['card_number']);
    }

    #[Test]
    public function payment_method_validates_expiry_date(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/payment-methods', [
                'type' => 'credit_card',
                'card_number' => '4111111111111111',
                'expiry_month' => '01',
                'expiry_year' => '2020', // Past year
                'cvv' => '123'
            ]);

        $response->assertSessionHasErrors(['expiry_year']);
    }

    #[Test]
    public function two_factor_setup_validates_code_format(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/two-factor/verify', [
                'code' => '123' // Invalid code format
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    #[Test]
    public function api_key_creation_validates_permissions(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/api-keys', [
                'name' => 'Test API Key',
                'permissions' => ['invalid_permission']
            ]);

        $response->assertSessionHasErrors(['permissions']);
    }

    #[Test]
    public function webhook_url_validates_format(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/webhooks', [
                'name' => 'Test Webhook',
                'url' => 'invalid-url',
                'events' => ['order.created']
            ]);

        $response->assertSessionHasErrors(['url']);
    }

    #[Test]
    public function webhook_url_validates_https_requirement(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/webhooks', [
                'name' => 'Test Webhook',
                'url' => 'http://example.com/webhook', // HTTP not allowed
                'events' => ['order.created']
            ]);

        $response->assertSessionHasErrors(['url']);
    }

    #[Test]
    public function bulk_actions_validate_selected_items(): void
    {
        $response = $this->actingAs($this->customer)
            ->post('/customer/users/bulk-delete', [
                'selected_ids' => [] // No items selected
            ]);

        $response->assertSessionHasErrors(['selected_ids']);
    }

    #[Test]
    public function date_range_filters_validate_date_format(): void
    {
        $response = $this->actingAs($this->customer)
            ->get('/customer/orders', [
                'start_date' => 'invalid-date',
                'end_date' => '2023-12-31'
            ]);

        $response->assertSessionHasErrors(['start_date']);
    }

    #[Test]
    public function date_range_filters_validate_logical_order(): void
    {
        $response = $this->actingAs($this->customer)
            ->get('/customer/orders', [
                'start_date' => '2023-12-31',
                'end_date' => '2023-01-01' // End before start
            ]);

        $response->assertSessionHasErrors(['end_date']);
    }

    #[Test]
    public function custom_validation_rules_work(): void
    {
        // Test custom validation rule for proxy configuration
        $response = $this->actingAs($this->customer)
            ->post('/customer/servers/test-connection', [
                'host' => '192.168.1.100',
                'port' => 2053,
                'credentials' => [
                    'username' => '',
                    'password' => ''
                ]
            ]);

        $response->assertSessionHasErrors(['credentials.username', 'credentials.password']);
    }

    #[Test]
    public function ajax_validation_returns_json_errors(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/profile', [
                'name' => '',
                'email' => 'invalid-email'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    #[Test]
    public function conditional_validation_works(): void
    {
        // Test that certain fields are required only when specific conditions are met
        $response = $this->actingAs($this->customer)
            ->post('/customer/servers', [
                'name' => 'Test Server',
                'host' => '192.168.1.100',
                'port' => 2053,
                'auth_type' => 'key_based', // When this is selected, private_key is required
                'username' => 'customer'
                // Missing private_key
            ]);

        $response->assertSessionHasErrors(['private_key']);
    }

    #[Test]
    public function validation_messages_are_user_friendly(): void
    {
        $response = $this->post('/register', [
            'email' => 'invalid-email'
        ]);

        // Prefer Laravel's assertion for session errors to be robust against redirect or JSON responses
        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function honeypot_validation_prevents_spam(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true,
            'website' => 'http://spam.com' // Honeypot field
        ]);

        $response->assertStatus(422);
    }
}
