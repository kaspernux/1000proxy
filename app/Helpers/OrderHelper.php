<?php

namespace App\Helpers;

use App\Models\OrderItem;
use App\Models\Server;
use App\Models\ServerClient;
use App\Models\ServerInbound;
use App\Services\XUIService;

class OrderHelper
{
    /**
     * Handle the creation of server clients and inbounds based on ordered server plans.
     *
     * @param array $orderItems Array of order items.
     * @param Server $server The server where inbounds and clients will be created.
     * @return void
     */
    public static function createClientsAndInbounds(array $orderItems, Server $server)
    {
        $xuiService = new XUIService(); // Initialize XUI service

        foreach ($orderItems as $orderItem) {
            $quantity = $orderItem['quantity'];
            $serverPlan = $orderItem['server_plan'];

            if ($serverPlan->isDedicated()) {
                // Dedicated / single / branded plans create a new inbound per plan
                $inbound = self::createInbound($server, $xuiService);
                self::createClientsForInbound($inbound, $quantity, $xuiService);
            } elseif ($serverPlan->isShared()) {
                // Multiple/shared plans add clients to an existing inbound
                $inbound = $server->inbounds()->first(); // simple default; higher-level code should prefer preferred_inbound
                self::createClientsForInbound($inbound, $quantity, $xuiService);
            } else {
                // Fallback: treat unknown types as shared
                $inbound = $server->inbounds()->first();
                self::createClientsForInbound($inbound, $quantity, $xuiService);
            }
        }
    }

    /**
     * Create a new server inbound for the given server using XUIService.
     *
     * @param Server $server The server to create the inbound for.
     * @param XUIService $xuiService The XUI service instance.
     * @return ServerInbound The created server inbound instance.
     */
    private static function createInbound(Server $server, XUIService $xuiService)
    {
        static $userId = 1;
        $data = [
            'server_id' => $server->id,
            'userId' => $userId++, // Example user ID
            // Add other inbound data as needed
        ];

        $response = $xuiService->createServerInbound($server->id, $data);

        // Handle response or error checking as needed
        if ($response['success'] ?? false) {
            // Inbound created successfully
            return $response['inbound']; // Adjust based on XUIService response structure
        } else {
            // Handle error scenario
            throw new \Exception("Failed to create inbound: " . ($response['message'] ?? 'Unknown error'));
        }
    }

    /**
     * Create server clients for the given inbound using XUIService.
     *
     * @param ServerInbound $inbound The server inbound to create clients for.
     * @param int $quantity The number of clients to create.
     * @param XUIService $xuiService The XUI service instance.
     * @return void
     */
    private static function createClientsForInbound(ServerInbound $inbound, int $quantity, XUIService $xuiService)
    {
        for ($i = 0; $i < $quantity; $i++) {
            $data = [
                'server_inbound_id' => $inbound->id,
                'email' => 'client' . ($i + 1) . '@example.com', // Example email
                'password' => bcrypt('password'), // Example password hashing
                // Add other client data as needed
            ];

            $response = $xuiService->createServerClient($inbound->id, $data);

            // Handle response or error checking as needed
            if (!$response['success'] ?? false) {
                // Handle error scenario
                throw new \Exception("Failed to create client: " . ($response['message'] ?? 'Unknown error'));
            }
        }
    }
}
