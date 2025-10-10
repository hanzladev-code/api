<?php

namespace App\Http\Controllers\CRM\Authenticated;

use App\Http\Controllers\Controller;
use App\Models\Network;
use Illuminate\Http\Request;

class NetworksController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Network::query();

            $networks = $query->with('tracker')->get();

            return response()->json([
                'data' => $networks,
                'pagination' => [
                    'total' => $networks->count(),
                    'per_page' => 10,
                    'current_page' => 1,
                    'last_page' => 1,
                    'from' => 1,
                    'to' => $networks->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Networks not found']);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'tracker' => 'required|exists:trackers,id',
            'status' => 'required|string|in:active,inactive',
        ]);
        try {
            $user = $request->user();
            $network = Network::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'tracker' => $request->tracker,
                'status' => $request->status,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Network created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Network creation failed']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'tracker' => 'required|exists:trackers,id',
            'status' => 'required|string|in:active,inactive',
        ]);
        try {
            $network = Network::find($id);
            $network->update($request->all());
            return response()->json(['status' => 'success', 'message' => 'Network updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Network update failed']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
