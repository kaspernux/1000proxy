<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerCategory;
use App\Models\ServerPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServerController extends Controller
{
    /**
     * Get all servers with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = Server::with(['category', 'brand', 'plans', 'ratings']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by brand
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by location
        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Filter by active status
        $query->where('is_active', true);

        // Sort by rating or price
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'rating':
                    $query->withAvg('ratings', 'rating')
                          ->orderBy('ratings_avg_rating', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $servers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $servers->items(),
            'pagination' => [
                'current_page' => $servers->currentPage(),
                'last_page' => $servers->lastPage(),
                'per_page' => $servers->perPage(),
                'total' => $servers->total(),
            ]
        ]);
    }

    /**
     * Get server by ID
     */
    public function show(int $id): JsonResponse
    {
        $server = Server::with([
            'category', 
            'brand', 
            'plans', 
            'ratings.user',
            'reviews.user'
        ])->findOrFail($id);

        if (!$server->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Server not available'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $server
        ]);
    }

    /**
     * Get server categories
     */
    public function categories(): JsonResponse
    {
        $categories = ServerCategory::withCount('servers')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get server locations
     */
    public function locations(): JsonResponse
    {
        $locations = Server::select('location')
            ->where('is_active', true)
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    /**
     * Get server plans
     */
    public function plans(int $serverId): JsonResponse
    {
        $server = Server::findOrFail($serverId);
        
        if (!$server->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Server not available'
            ], 404);
        }

        $plans = ServerPlan::where('server_id', $serverId)
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Search servers
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $query = $request->get('query');

        $servers = Server::with(['category', 'brand', 'plans'])
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('location', 'like', '%' . $query . '%')
                  ->orWhere('description', 'like', '%' . $query . '%')
                  ->orWhereHas('category', function ($categoryQuery) use ($query) {
                      $categoryQuery->where('name', 'like', '%' . $query . '%');
                  });
            })
            ->orderBy('name')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $servers->items(),
            'pagination' => [
                'current_page' => $servers->currentPage(),
                'last_page' => $servers->lastPage(),
                'per_page' => $servers->perPage(),
                'total' => $servers->total(),
            ]
        ]);
    }

    /**
     * Get featured servers
     */
    public function featured(): JsonResponse
    {
        $servers = Server::with(['category', 'brand', 'plans'])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $servers
        ]);
    }

    /**
     * Get server statistics
     */
    public function stats(int $serverId): JsonResponse
    {
        $server = Server::findOrFail($serverId);
        
        if (!$server->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Server not available'
            ], 404);
        }

        $stats = [
            'total_clients' => $server->clients()->count(),
            'active_clients' => $server->clients()->where('status', 'active')->count(),
            'total_orders' => $server->orders()->count(),
            'average_rating' => $server->ratings()->avg('rating') ?? 0,
            'total_reviews' => $server->reviews()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
