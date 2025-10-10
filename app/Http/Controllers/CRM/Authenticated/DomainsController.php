<?php

namespace App\Http\Controllers\CRM\Authenticated;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Http\Request;

class DomainsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $domains = Domain::all();
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
        $validated = $request->validate([
            'name' => 'required|url|max:255|unique:domains,name',
            'hosted_at' => 'required|string',
            'expires_at' => 'required|date',
            'status' => 'required|string|in:active,inactive',
        ]);
        try {
            $domain = Domain::create($validated);
            return response()->json(['status' => 'success', 'message' => 'Domain created successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Domain creation failed']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $domain = Domain::find($id);
            return response()->json($domain);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Domain not found']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hosted_at' => 'required|string',
            'expires_at' => 'required|date',
            'status' => 'required|string|in:active,inactive',
        ]);
        try {
            $domain = Domain::find($id);
            $domain->update($validated);
            return response()->json(['status' => 'success', 'message' => 'Domain updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Domain not found']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $domain = Domain::find($id);
            $domain->delete();
            return response()->json(['status' => 'success', 'message' => 'Domain deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Domain not found']);
        }
    }
}
