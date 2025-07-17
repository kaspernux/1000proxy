<?php

namespace Database\Seeders;

use App\Models\ServerTag;
use App\Models\Server;
use Illuminate\Database\Seeder;

class ServerTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servers = Server::all();
        
        if ($servers->isEmpty()) {
            $this->command->warn('No servers found. Please run ServerSeeder first.');
            return;
        }

        $tagNames = [
            'High Speed',
            'Low Latency', 
            'Ultra Fast',
            'Military Grade',
            'Zero Logs',
            'Encrypted',
            'Unlimited',
            'Multi-Protocol',
            'IPv6 Ready',
            '24/7 Support',
            'Gaming',
            'Streaming',
            'Business',
            'Social Media',
            'Global Network',
            'Regional',
            'Premium',
            'Basic',
            'Advanced',
            'Standard'
        ];

        // Assign random tags to each server
        foreach ($servers as $server) {
            // Each server gets 2-5 random tags
            $numberOfTags = rand(2, 5);
            $selectedTags = array_rand(array_flip($tagNames), $numberOfTags);
            
            if (!is_array($selectedTags)) {
                $selectedTags = [$selectedTags];
            }
            
            foreach ($selectedTags as $tagName) {
                ServerTag::create([
                    'name' => $tagName,
                    'server_id' => $server->id,
                ]);
            }
        }

        $this->command->info('Server tags seeded successfully!');
    }
}
