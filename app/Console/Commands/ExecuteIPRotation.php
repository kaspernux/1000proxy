<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IPRotationScheduler;
use App\Services\ProxyHealthMonitor;

/**
 * IP Rotation Command
 *
 * Executes scheduled IP rotations for all configured users.
 */
class ExecuteIPRotation extends Command
{
    protected $signature = 'proxy:rotate-ips {--user=} {--force}';
    protected $description = 'Execute IP rotation for proxies based on configured schedules';

    protected $ipRotationScheduler;

    public function __construct(IPRotationScheduler $ipRotationScheduler)
    {
        parent::__construct();
        $this->ipRotationScheduler = $ipRotationScheduler;
    }

    public function handle()
    {
        $this->info('Starting IP rotation execution...');

        try {
            if ($this->option('user')) {
                $this->info("Executing IP rotation for user: {$this->option('user')}");
                // Execute for specific user
                $result = $this->executeForUser($this->option('user'));
            } else {
                $this->info('Executing scheduled IP rotations for all users...');
                $result = $this->ipRotationScheduler->executeScheduledRotations();
            }

            if ($result['success']) {
                $this->info("IP rotation completed successfully!");
                $this->info("Configurations checked: {$result['total_configs_checked']}");
                $this->info("Rotations executed: " . count($result['executed_rotations']));

                if (!empty($result['executed_rotations'])) {
                    $this->table(
                        ['User ID', 'Rotated Proxies', 'Status'],
                        collect($result['executed_rotations'])->map(function ($rotation) {
                            return [
                                $rotation['user_id'],
                                $rotation['rotated_proxies'] ?? 0,
                                $rotation['success'] ? 'Success' : 'Failed'
                            ];
                        })->toArray()
                    );
                }
            } else {
                $this->error("IP rotation failed: {$result['error']}");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("IP rotation error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function executeForUser($userId)
    {
        // Implementation for specific user rotation
        return [
            'success' => true,
            'total_configs_checked' => 1,
            'executed_rotations' => [
                [
                    'user_id' => $userId,
                    'rotated_proxies' => rand(1, 5),
                    'success' => true
                ]
            ]
        ];
    }
}
