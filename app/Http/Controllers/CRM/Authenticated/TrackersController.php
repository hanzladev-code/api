<?php

namespace App\Http\Controllers\CRM\Authenticated;

use App\Http\Controllers\Controller;
use App\Models\Tracker;
use Illuminate\Http\Request;

class TrackersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $domains = Tracker::all();
        return response()->json([
            'data' => $domains,
            'pagination' => [
                'total' => $domains->count(),
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => $domains->count()
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:trackers,name',
            'param' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'source' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'rate' => 'required|integer',
            'status' => 'required|string|in:active,inactive',
        ]);
        try {
            $validated['user_id'] = $user->id;
            $tracker = Tracker::create($validated);
            return response()->json(['status' => 'success', 'message' => 'Tracker created successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $tracker = Tracker::find($id);
            return response()->json(['status' => 'success', 'data' => $tracker]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Tracker not found']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:trackers,name,' . $id,
            'param' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'source' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'rate' => 'required|integer',
            'status' => 'required|string|in:active,inactive',
        ]);
        try {
            $tracker = Tracker::find($id);
            $tracker->update($validated);
            return response()->json(['status' => 'success', 'message' => 'Tracker updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Tracker not found']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $tracker = Tracker::find($id);
            $tracker->delete();
            return response()->json(['status' => 'success', 'message' => 'Tracker deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Tracker not found']);
        }
    }
}
