<?php

namespace App\Http\Controllers;

use App\Models\ServerPlan;
use Illuminate\Http\Request;

class ServerPlanController extends Controller
{
    // Retrieve a server plan
    public function show(ServerPlan $serverPlan)
    {
        return response()->json($serverPlan);
    }

    // Update a server plan
    public function update(Request $request, ServerPlan $serverPlan)
    {
        // Validate incoming request data here if needed

        // Update Server Plan
        $serverPlan->update($request->all());

        return response()->json($serverPlan);
    }

    // Delete a server plan
    public function destroy(ServerPlan $serverPlan)
    {
        // Delete Server Plan
        $serverPlan->delete();

        return response()->json(['message' => 'Server Plan deleted successfully']);
    }

    // Add a new server plan
    public function store(Request $request)
    {
        // Validate incoming request data here if needed

        // Create Server Plan
        $serverPlan = ServerPlan::create($request->all());

        return response()->json($serverPlan, 201);
    }
}