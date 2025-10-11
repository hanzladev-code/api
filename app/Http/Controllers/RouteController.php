<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RouteController extends Controller
{

    public function index(Request $request)
    {
        $path = $request->query('path');
        $routes = Route::with(['parent', 'children', 'permission', 'createdBy', 'updatedBy'])
            ->orderBy('order')
            ->where('path', $path)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $routes
        ]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:routes',
            'method' => 'nullable|string|max:10',
            'path' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'parent_id' => 'nullable|exists:routes,id',
            'permission_id' => 'nullable|exists:permissions,id',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
        ]);

        try {



            $route = Route::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'path' => $request->path,
                'icon' => $request->icon,
                'order' => $request->order ?? 0,
                'parent_id' => $request->parent_id,
                'permission_id' => $request->permission_id,
                'description' => $request->description,
                'status' => $request->status,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Route created successfully',
                'data' => $route->load(['parent', 'permission'])
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Route  $route
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Route $route): JsonResponse
    {
        $route->load(['parent', 'children', 'permission', 'createdBy', 'updatedBy']);

        return response()->json([
            'status' => 'success',
            'data' => $route
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Route  $route
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Route $route): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:routes,slug,' . $route->id,
            'method' => 'nullable|string|max:10',
            'path' => 'sometimes|required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'parent_id' => 'nullable|exists:routes,id',
            'permission_id' => 'nullable|exists:permissions,id',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $route->update(array_merge(
            $request->all(),
            ['updated_by' => Auth::id()]
        ));

        return response()->json([
            'status' => 'success',
            'message' => 'Route updated successfully',
            'data' => $route->fresh()->load(['parent', 'permission'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Route  $route
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Route $route): JsonResponse
    {
        // Check if route has children
        if ($route->children()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete route with children. Delete children first.'
            ], 422);
        }

        $route->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Route deleted successfully'
        ]);
    }

    /**
     * Get all routes as a tree structure.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tree(): JsonResponse
    {
        $routes = Route::with(['children', 'permission'])
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $routes
        ]);
    }

    /**
     * Get available permissions for routes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::where('status', true)->get();

        return response()->json([
            'status' => 'success',
            'data' => $permissions
        ]);
    }
}
