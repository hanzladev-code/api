<?php

namespace App\Http\Controllers\CRM\Authenticated;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UsersOffers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $staff = User::get();
            return response()->json([
                'data' => $staff,
                'pagination' => [
                    'total' => $staff->count(),
                    'per_page' => 10,
                    'current_page' => 1,
                    'last_page' => 1,
                    'from' => 1,
                    'to' => $staff->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch staff list']);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|min:8',
            'role' => 'required|string|in:admin,user',
            'status' => 'required|string|in:active,inactive',
            'domain' => 'nullable|string',
            'payout' => 'required|string',
            'skype' => 'nullable|string',
            'description' => 'nullable|string',
            'gender' => 'nullable|string',
            'notification' => 'boolean',
            'offers' => 'nullable|string',
            'notes' => 'nullable|string',
            'image' => 'nullable|string'
        ]);

        try {
            // Generate username from name
            $username = strtolower(str_replace(' ', '', $request->name));

            // Add number if username exists
            $count = 1;
            $originalUsername = $username;
            while (User::where('username', $username)->exists()) {
                $username = $originalUsername . $count;
                $count++;
            }

            $staff = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'username' => $username,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => $request->status,
                'domain_id' => $request->domain,
                'payout' => $request->payout,
                'skype' => $request->skype,
                'description' => $request->description,
                'gender' => $request->gender,
                'notification' => $request->notification,
                'notes' => $request->notes,
                'image' => $request->image
            ]);

            // Create user offers if provided
            if ($request->has('offers')) {
                UsersOffers::updateOrCreate(
                    ['user_id' => $staff->id],
                    ['offer_ids' => $request->offers]
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Staff created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Staff creation failed' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $staff = User::find($id);
            $offers = UsersOffers::select('offer_ids')->where('user_id', $id)->first();
            $staff->offers = $offers->offer_ids;
            if (!$staff) {
                return response()->json(['status' => 'error', 'message' => 'Staff not found'], 404);
            }
            return response()->json($staff);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch staff details']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $id,
            'phone' => 'string|unique:users,phone,' . $id,
            'password' => 'min:8',
            'role' => 'string|in:admin,user',
            'status' => 'string|in:active,inactive',
            'domain_id' => 'nullable|string',
            'payout' => 'required|string',
            'skype' => 'nullable|string',
            'description' => 'nullable|string',
            'gender' => 'nullable|string',
            'notification' => 'boolean',
            'offers' => 'nullable|string',
            'notes' => 'nullable|string',
            'image' => 'nullable|string'
        ]);

        try {
            $staff = User::find($id);
            if (!$staff) {
                return response()->json(['status' => 'error', 'message' => 'Staff not found'], 404);
            }

            $updateData = $request->except(['password', 'offers']);
            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $staff->update($updateData);

            // Update or create user offers
            if ($request->has('offers')) {
                UsersOffers::updateOrCreate(
                    ['user_id' => $staff->id],
                    ['offer_ids' => $request->offers]
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Staff updated successfully',
                'data' => $staff->load('userOffers')
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Staff update failed']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $staff = User::find($id);

            if (!$staff) {
                return response()->json(['status' => 'error', 'message' => 'Staff not found'], 404);
            }

            // Associated user offers will be automatically deleted due to cascade delete
            $staff->delete();
            return response()->json(['status' => 'success', 'message' => 'Staff deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Staff deletion failed']);
        }
    }
}
