<?php

namespace Database\Seeders;

use App\Models\MobileDevice;
use App\Models\MobileSession;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MobileDeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        $customers = Customer::all();
        $users = User::all();

        if ($customers->isEmpty() && $users->isEmpty()) {
            $this->command->warn('No customers or users found. Please run CustomerSeeder and UserSeeder first.');
            return;
        }

        $deviceTypes = ['mobile', 'tablet'];
        $platforms = ['android', 'ios'];
        $osVersions = [
            'android' => ['11', '12', '13', '14'],
            'ios' => ['15.0', '16.0', '17.0', '17.1'],
        ];

        // Create mobile devices for users (since mobile_devices table uses user_id)
        foreach ($users->take(10) as $user) { // Mobile devices for first 10 users
            // Each user might have 1-3 devices
            $deviceCount = $faker->numberBetween(1, 3);

            for ($i = 0; $i < $deviceCount; $i++) {
                $platform = $faker->randomElement($platforms);
                $platformVersion = $faker->randomElement($osVersions[$platform]);

                $device = MobileDevice::create([
                    'user_id' => $user->id,
                    'device_identifier' => Str::uuid(),
                    'device_name' => $this->generateDeviceName($platform, $faker),
                    'device_type' => $faker->randomElement($deviceTypes),
                    'platform' => $platform,
                    'platform_version' => $platformVersion,
                    'app_version' => '1.0.' . $faker->numberBetween(0, 20),
                    'push_token' => 'fcm_' . Str::random(64),
                    'push_notifications_enabled' => $faker->boolean(85),
                    'timezone' => $faker->timezone(),
                    'language' => $faker->randomElement(['en', 'es', 'fr', 'de', 'pt']),
                    'is_active' => $faker->boolean(85), // 85% active
                    'last_seen_at' => $faker->dateTimeBetween('-1 week', 'now'),
                    'last_sync_at' => $faker->dateTimeBetween('-1 day', 'now'),
                    'offline_data_size' => $faker->numberBetween(1024, 10485760), // 1KB to 10MB
                    'sync_version' => $faker->numberBetween(1, 5),
                    'sync_status' => $faker->randomElement(['pending', 'synced', 'failed']),
                ]);

                // Create mobile sessions for this device
                $sessionCount = $faker->numberBetween(3, 10);
                for ($j = 0; $j < $sessionCount; $j++) {
                    $expiresAt = $faker->dateTimeBetween('now', '+30 days');
                    $lastActivity = $faker->dateTimeBetween('-1 week', 'now');

                    MobileSession::create([
                        'user_id' => $user->id,
                        'device_id' => $device->id,
                        'session_token' => Str::random(64),
                        'ip_address' => $faker->ipv4(),
                        'user_agent' => $this->generateUserAgent($platform, $platformVersion),
                        'expires_at' => $expiresAt,
                        'is_active' => $faker->boolean(70),
                        'last_activity_at' => $lastActivity,
                    ]);
                }
            }
        }

        $this->command->info('Mobile devices and sessions seeded successfully!');
    }

    private function generateDeviceName(string $platform, $faker): string
    {
        $androidNames = [
            'Samsung Galaxy S23', 'Google Pixel 7', 'OnePlus 11', 'Xiaomi 13 Pro',
            'Samsung Galaxy Note 20', 'Google Pixel 6a', 'Huawei P50 Pro'
        ];

        $iosNames = [
            'iPhone 14 Pro', 'iPhone 13', 'iPhone 14 Pro Max', 'iPhone 12 Pro',
            'iPad Pro 12.9"', 'iPhone 15', 'iPhone 13 Mini'
        ];

        return $platform === 'android'
            ? $faker->randomElement($androidNames)
            : $faker->randomElement($iosNames);
    }

    private function getManufacturer(string $platform, $faker): string
    {
        return $platform === 'android'
            ? $faker->randomElement(['Samsung', 'Google', 'OnePlus', 'Xiaomi', 'Huawei'])
            : 'Apple';
    }

    private function getDeviceModel(string $platform, $faker): string
    {
        if ($platform === 'android') {
            return $faker->randomElement(['SM-G991B', 'Pixel 7', 'LE2123', 'M2102J20SG', 'ELS-NX9']);
        }
        return $faker->randomElement(['iPhone14,2', 'iPhone13,3', 'iPhone14,3', 'iPad13,1']);
    }

    private function generateUserAgent(string $platform, string $platformVersion): string
    {
        if ($platform === 'android') {
            return "Mozilla/5.0 (Linux; Android {$platformVersion}; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36";
        }
        return "Mozilla/5.0 (iPhone; CPU iPhone OS " . str_replace('.', '_', $platformVersion) . " like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1";
    }
}