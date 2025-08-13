<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting 1000proxy Database Seeding...');

        // Clear any existing data first to avoid constraint violations
        $this->command->info('�️ Clearing existing data...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear tables in reverse dependency order
        $clearTables = [
            'server_clients',
            'server_inbounds',
            'order_items',
            'orders',
            'invoices',
            'wallets',
            'server_plans',
            'servers',
            'customers',
            'server_brands',
            'server_categories',
            'payment_methods',
            'settings',
            'users'
        ];

        foreach ($clearTables as $table) {
            try {
                DB::table($table)->truncate();
            } catch (Exception $e) {
                $this->command->warn("Could not clear table {$table}: " . $e->getMessage());
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Step 1: Independent core data (no foreign keys)
        $this->command->info('� Seeding independent core data...');
        $this->call([
            SettingSeeder::class,
            UserSeeder::class,
            PaymentMethodSeeder::class,
            ServerBrandSeeder::class,
            ServerCategorySeeder::class,
        ]);

        // Step 2: Servers (depends on brands and categories)
        $this->command->info('🖥️ Seeding servers...');
        $this->call([
            ServerSeeder::class,
        ]);

        // Step 3: Customers (independent)
        $this->command->info('👥 Seeding customers...');
        $this->call([
            CustomerSeeder::class,
        ]);

        // Step 4: Wallets (depends on customers)
        $this->command->info('💰 Seeding wallets...');
        $this->call([
            WalletSeeder::class,
        ]);

        // Step 5: Orders (depends on customers and payment methods)
        $this->command->info('💳 Seeding orders...');
        $this->call([
            OrderSeeder::class,
        ]);

        // Step 6: Invoices (depends on orders)
        $this->command->info('📄 Seeding invoices...');
        $this->call([
            InvoiceSeeder::class,
        ]);

        // Step 7: Server infrastructure (depends on servers)
        $this->command->info('🔧 Seeding server infrastructure...');
        $this->call([
            ServerInboundSeeder::class,
            ServerClientSeeder::class,
        ]);

        // Step 8: Additional features (least dependent)
        $this->command->info('✨ Seeding additional features...');
        $this->call([
            ServerTagSeeder::class,
            ServerReviewSeeder::class,
            SubscriptionSeeder::class,
            MobileDeviceSeeder::class,
        ]);

        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->info('🎯 Your 1000proxy application is ready to use.');

        // Display test credentials
        $this->displayTestCredentials();

            $this->call([
                NotificationTemplateSeeder::class,
            ]);
    }

    private function displayTestCredentials()
    {
        $this->command->info('');
        $this->command->info('🔑 Test Account Credentials:');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('👑 Admin Account:');
        $this->command->info('   Email: admin@1000proxy.io');
        $this->command->info('   Password: P@ssw0rd!Adm1n2024$');
        $this->command->info('');
        $this->command->info('👑 Support Team:');
        $this->command->info('   Email: support@1000proxy.io');
        $this->command->info('   Password: Supp0rt#Mgr!2024&');
        $this->command->info('');
        $this->command->info('👑 Sales Team:');
        $this->command->info('   Email: sales@1000proxy.io');
        $this->command->info('   Password: S@les#Team!2024*');
        $this->command->info('');
        $this->command->info('👤 Demo Customer Account:');
        $this->command->info('   Email: demo@1000proxy.io');
        $this->command->info('   Password: D3m0#Cust0mer!2024$');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
