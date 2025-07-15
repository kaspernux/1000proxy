<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Server;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FormValidationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function user_registration_validates_required_fields()
    {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
            'terms'
        ]);
    }

    /** @test */
    public function user_registration_validates_email_format()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function user_registration_validates_unique_email()
    {
        $existingUser = User::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function user_registration_validates_password_confirmation()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
            'terms' => true
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function user_registration_validates_password_strength()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '123',
            'password_confirmation' => '123',
            'terms' => true
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function login_validates_required_fields()
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors([
            'email',
            'password'
        ]);
    }

    /** @test */
    public function login_validates_email_format()
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function profile_update_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->put('/profile', [
                'name' => '',
                'email' => ''
            ]);

        $response->assertSessionHasErrors([
            'name',
            'email'
        ]);
    }

    /** @test */
    public function profile_update_validates_email_uniqueness()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->put('/profile', [
                'name' => 'Updated Name',
                'email' => $otherUser->email
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function profile_update_allows_same_email()
    {
        $response = $this->actingAs($this->user)
            ->put('/profile', [
                'name' => 'Updated Name',
                'email' => $this->user->email
            ]);

        $response->assertSessionDoesntHaveErrors(['email']);
        $response->assertRedirect();
    }

    /** @test */
    public function password_change_validates_current_password()
    {
        $response = $this->actingAs($this->user)
            ->put('/password', [
                'current_password' => 'wrong_password',
                'password' => 'new_password123',
                'password_confirmation' => 'new_password123'
            ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    /** @test */
    public function password_change_validates_new_password_confirmation()
    {
        $response = $this->actingAs($this->user)
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new_password123',
                'password_confirmation' => 'different_password'
            ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function server_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/servers', []);

        $response->assertSessionHasErrors([
            'name',
            'host',
            'port',
            'username',
            'password',
            'location'
        ]);
    }

    /** @test */
    public function server_creation_validates_host_format()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/servers', [
                'name' => 'Test Server',
                'host' => 'invalid-host',
                'port' => 2053,
                'username' => 'admin',
                'password' => 'password123',
                'location' => 'US'
            ]);

        $response->assertSessionHasErrors(['host']);
    }

    /** @test */
    public function server_creation_validates_port_range()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/servers', [
                'name' => 'Test Server',
                'host' => '192.168.1.100',
                'port' => 70000, // Invalid port
                'username' => 'admin',
                'password' => 'password123',
                'location' => 'US'
            ]);

        $response->assertSessionHasErrors(['port']);
    }

    /** @test */
    public function server_creation_validates_unique_host_port_combination()
    {
        $existingServer = Server::factory()->create([
            'host' => '192.168.1.100',
            'port' => 2053
        ]);

        $response = $this->actingAs($this->admin)
            ->post('/admin/servers', [
                'name' => 'Test Server',
                'host' => '192.168.1.100',
                'port' => 2053,
                'username' => 'admin',
                'password' => 'password123',
                'location' => 'US'
            ]);

        $response->assertSessionHasErrors(['host']);
    }

    /** @test */
    public function service_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/services', []);

        $response->assertSessionHasErrors([
            'name',
            'description',
            'price',
            'billing_cycle'
        ]);
    }

    /** @test */
    public function service_creation_validates_price_format()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/services', [
                'name' => 'Test Service',
                'description' => 'Test Description',
                'price' => 'invalid_price',
                'billing_cycle' => 'monthly'
            ]);

        $response->assertSessionHasErrors(['price']);
    }

    /** @test */
    public function service_creation_validates_negative_price()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/services', [
                'name' => 'Test Service',
                'description' => 'Test Description',
                'price' => -10.99,
                'billing_cycle' => 'monthly'
            ]);

        $response->assertSessionHasErrors(['price']);
    }

    /** @test */
    public function order_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->post('/orders', []);

        $response->assertSessionHasErrors([
            'server_id',
            'billing_cycle'
        ]);
    }

    /** @test */
    public function order_creation_validates_server_exists()
    {
        $response = $this->actingAs($this->user)
            ->post('/orders', [
                'server_id' => 99999, // Non-existent server
                'billing_cycle' => 'monthly'
            ]);

        $response->assertSessionHasErrors(['server_id']);
    }

    /** @test */
    public function order_creation_validates_billing_cycle_options()
    {
        $server = Server::factory()->create();

        $response = $this->actingAs($this->user)
            ->post('/orders', [
                'server_id' => $server->id,
                'billing_cycle' => 'invalid_cycle'
            ]);

        $response->assertSessionHasErrors(['billing_cycle']);
    }

    /** @test */
    public function file_upload_validates_file_type()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.exe', 1000); // Invalid type

        $response = $this->actingAs($this->admin)
            ->post('/admin/bulk-import/users', [
                'file' => $file
            ]);

        $response->assertSessionHasErrors(['file']);
    }

    /** @test */
    public function file_upload_validates_file_size()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('large_file.csv', 10240); // 10MB file

        $response = $this->actingAs($this->admin)
            ->post('/admin/bulk-import/users', [
                'file' => $file
            ]);

        $response->assertSessionHasErrors(['file']);
    }

    /** @test */
    public function support_ticket_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->post('/support/tickets', []);

        $response->assertSessionHasErrors([
            'subject',
            'message',
            'priority'
        ]);
    }

    /** @test */
    public function support_ticket_validates_message_length()
    {
        $response = $this->actingAs($this->user)
            ->post('/support/tickets', [
                'subject' => 'Test Subject',
                'message' => 'Hi', // Too short
                'priority' => 'medium'
            ]);

        $response->assertSessionHasErrors(['message']);
    }

    /** @test */
    public function support_ticket_validates_priority_options()
    {
        $response = $this->actingAs($this->user)
            ->post('/support/tickets', [
                'subject' => 'Test Subject',
                'message' => 'This is a test message with sufficient length.',
                'priority' => 'invalid_priority'
            ]);

        $response->assertSessionHasErrors(['priority']);
    }

    /** @test */
    public function payment_method_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->post('/payment-methods', []);

        $response->assertSessionHasErrors([
            'type',
            'card_number',
            'expiry_month',
            'expiry_year',
            'cvv'
        ]);
    }

    /** @test */
    public function payment_method_validates_card_number_format()
    {
        $response = $this->actingAs($this->user)
            ->post('/payment-methods', [
                'type' => 'credit_card',
                'card_number' => '1234', // Invalid card number
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '123'
            ]);

        $response->assertSessionHasErrors(['card_number']);
    }

    /** @test */
    public function payment_method_validates_expiry_date()
    {
        $response = $this->actingAs($this->user)
            ->post('/payment-methods', [
                'type' => 'credit_card',
                'card_number' => '4111111111111111',
                'expiry_month' => '01',
                'expiry_year' => '2020', // Past year
                'cvv' => '123'
            ]);

        $response->assertSessionHasErrors(['expiry_year']);
    }

    /** @test */
    public function two_factor_setup_validates_code_format()
    {
        $response = $this->actingAs($this->user)
            ->post('/two-factor/verify', [
                'code' => '123' // Invalid code format
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    /** @test */
    public function api_key_creation_validates_permissions()
    {
        $response = $this->actingAs($this->user)
            ->post('/api-keys', [
                'name' => 'Test API Key',
                'permissions' => ['invalid_permission']
            ]);

        $response->assertSessionHasErrors(['permissions']);
    }

    /** @test */
    public function webhook_url_validates_format()
    {
        $response = $this->actingAs($this->user)
            ->post('/webhooks', [
                'name' => 'Test Webhook',
                'url' => 'invalid-url',
                'events' => ['order.created']
            ]);

        $response->assertSessionHasErrors(['url']);
    }

    /** @test */
    public function webhook_url_validates_https_requirement()
    {
        $response = $this->actingAs($this->user)
            ->post('/webhooks', [
                'name' => 'Test Webhook',
                'url' => 'http://example.com/webhook', // HTTP not allowed
                'events' => ['order.created']
            ]);

        $response->assertSessionHasErrors(['url']);
    }

    /** @test */
    public function bulk_actions_validate_selected_items()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/users/bulk-delete', [
                'selected_ids' => [] // No items selected
            ]);

        $response->assertSessionHasErrors(['selected_ids']);
    }

    /** @test */
    public function date_range_filters_validate_date_format()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/orders', [
                'start_date' => 'invalid-date',
                'end_date' => '2023-12-31'
            ]);

        $response->assertSessionHasErrors(['start_date']);
    }

    /** @test */
    public function date_range_filters_validate_logical_order()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/orders', [
                'start_date' => '2023-12-31',
                'end_date' => '2023-01-01' // End before start
            ]);

        $response->assertSessionHasErrors(['end_date']);
    }

    /** @test */
    public function custom_validation_rules_work()
    {
        // Test custom validation rule for proxy configuration
        $response = $this->actingAs($this->admin)
            ->post('/admin/servers/test-connection', [
                'host' => '192.168.1.100',
                'port' => 2053,
                'credentials' => [
                    'username' => '',
                    'password' => ''
                ]
            ]);

        $response->assertSessionHasErrors(['credentials.username', 'credentials.password']);
    }

    /** @test */
    public function ajax_validation_returns_json_errors()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/profile', [
                'name' => '',
                'email' => 'invalid-email'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    /** @test */
    public function conditional_validation_works()
    {
        // Test that certain fields are required only when specific conditions are met
        $response = $this->actingAs($this->admin)
            ->post('/admin/servers', [
                'name' => 'Test Server',
                'host' => '192.168.1.100',
                'port' => 2053,
                'auth_type' => 'key_based', // When this is selected, private_key is required
                'username' => 'admin'
                // Missing private_key
            ]);

        $response->assertSessionHasErrors(['private_key']);
    }

    /** @test */
    public function validation_messages_are_user_friendly()
    {
        $response = $this->post('/register', [
            'email' => 'invalid-email'
        ]);

        $errors = session('errors');
        $this->assertStringContainsString('valid email address', $errors->first('email'));
    }

    /** @test */
    public function honeypot_validation_prevents_spam()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true,
            'website' => 'http://spam.com' // Honeypot field
        ]);

        $response->assertStatus(422);
    }
}
