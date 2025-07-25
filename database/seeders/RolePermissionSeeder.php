<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Customer;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding Roles and Permissions...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Customer Management
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',
            
            // Server Management
            'view servers',
            'create servers',
            'edit servers',
            'delete servers',
            'manage server clients',
            'manage server inbounds',
            
            // Order Management
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',
            'process orders',
            
            // Payment Management
            'view payments',
            'process payments',
            'manage payment methods',
            
            // Invoice Management
            'view invoices',
            'create invoices',
            'edit invoices',
            'delete invoices',
            
            // System Settings
            'view settings',
            'edit settings',
            'manage system',
            
            // Reports and Analytics
            'view reports',
            'export data',
            
            // Customer Panel Access
            'access customer panel',
            'view own orders',
            'view own invoices',
            'manage own account',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Super Admin - has all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions(Permission::all());

        // Admin - has most permissions except system management
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'view users', 'create users', 'edit users',
            'view customers', 'create customers', 'edit customers',
            'view servers', 'create servers', 'edit servers', 'manage server clients', 'manage server inbounds',
            'view orders', 'create orders', 'edit orders', 'process orders',
            'view payments', 'process payments', 'manage payment methods',
            'view invoices', 'create invoices', 'edit invoices',
            'view settings',
            'view reports', 'export data',
        ]);

        // Staff - limited permissions for day-to-day operations
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->syncPermissions([
            'view customers', 'edit customers',
            'view servers', 'manage server clients',
            'view orders', 'edit orders', 'process orders',
            'view payments',
            'view invoices',
            'view reports',
        ]);

        // Support - customer support permissions
        $support = Role::firstOrCreate(['name' => 'support']);
        $support->syncPermissions([
            'view customers', 'edit customers',
            'view servers', 'manage server clients',
            'view orders', 'edit orders',
            'view invoices',
        ]);

        // Customer - basic customer permissions
        $customer = Role::firstOrCreate(['name' => 'customer']);
        $customer->syncPermissions([
            'access customer panel',
            'view own orders',
            'view own invoices',
            'manage own account',
        ]);

        // Assign roles to existing users
        $adminUser = User::where('email', 'admin@1000proxy.io')->first();
        if ($adminUser) {
            $adminUser->assignRole('super-admin');
            $this->command->info("✅ Assigned super-admin role to {$adminUser->email}");
        }

        // Create guard for customers if needed
        $customerUser = Customer::where('email', 'nook@1000proxy.io')->first();
        if ($customerUser) {
            // Note: You might need to configure the guard for customers in the permission config
            // For now, we'll skip assigning roles to customers as they use a different guard
            $this->command->info("ℹ️ Customer roles will need to be configured separately with proper guard setup");
        }

        $this->command->info('✅ Roles and permissions seeded successfully!');
        $this->command->info('📋 Created roles: super-admin, admin, staff, support, customer');
        $this->command->info('🔑 Created ' . count($permissions) . ' permissions');
    }
}
