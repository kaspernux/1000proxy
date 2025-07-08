<?php

namespace App\Http\Controllers;

use App\Models\ServerPlan;
use App\Services\XUIService;
use App\Http\Requests\StoreServerPlanRequest;
use App\Http\Requests\UpdateServerPlanRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ServerPlanController extends Controller
{
    protected $xuiService;

    public function __construct(XUIService $xuiService)
    {
        $this->xuiService = $xuiService;
    }

    /**
     * Retrieve a server plan
     */
    public function show(ServerPlan $serverPlan): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $serverPlan->load('server')
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving server plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve server plan'
            ], 500);
        }
    }

    /**
     * Update a server plan
     */
    public function update(UpdateServerPlanRequest $request, ServerPlan $serverPlan): JsonResponse
    {
        try {
            $serverPlan->update($request->validated());

            return response()->json([
                'success' => true,
                'data' => $serverPlan->fresh(),
                'message' => 'Server plan updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating server plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update server plan'
            ], 500);
        }
    }

    /**
     * Delete a server plan
     */
    public function destroy(ServerPlan $serverPlan): JsonResponse
    {
        try {
            $serverPlan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Server plan deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting server plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete server plan'
            ], 500);
        }
    }

    /**
     * Add a new server plan
     */
    public function store(StoreServerPlanRequest $request): JsonResponse
    {
        try {
            $serverPlan = ServerPlan::create($request->validated());

            return response()->json([
                'success' => true,
                'data' => $serverPlan->load('server'),
                'message' => 'Server plan created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating server plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create server plan'
            ], 500);
        }
    }
}

        return response()->json($serverPlan, 201);
    }
}