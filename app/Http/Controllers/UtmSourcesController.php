<?php

namespace App\Http\Controllers;

use App\Models\UtmSources;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UtmSourcesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $utmSources = UtmSources::all();
            return response()->json([
                'status' => 'success',
                'data' => $utmSources
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve UTM sources',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:utm_sources',
            'status' => 'string|in:active,inactive',
        ]);
        $user = $request->user();

        // Auto generate slug from name
        $validated['slug'] = Str::slug($validated['name']);

        if (!isset($validated['status'])) {
            $validated['status'] = true;
        }

        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        try {
            $utmSource = UtmSources::create($validated);
            return response()->json([
                'status' => 'success',
                'message' => 'UTM source created successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create UTM source',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UtmSources $utmSource)
    {
        try {
            if (!$utmSource) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'UTM source not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $utmSource
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve UTM source',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UtmSources $utmSource)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:utm_sources,name,' . $utmSource->id,
                'status' => 'string|in:active,inactive',
            ]);

            $user = $request->user();
            $validated['updated_by'] = $user->id;

            // Always auto generate slug if name is provided
            if (isset($validated['name'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $utmSource->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'UTM source updated successfully',
                'data' => $utmSource
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'UTM source update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UtmSources $utmSource)
    {
        try {
            if (!$utmSource) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'UTM source not found'
                ], 404);
            }

            $utmSource->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'UTM source deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'UTM source deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
