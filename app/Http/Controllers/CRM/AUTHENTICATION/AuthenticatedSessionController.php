<?php

namespace App\Http\Controllers\CRM\AUTHENTICATION;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticatedSessionController extends Controller
{
    public function checkCredentials(Request $request)
    {
        $payload = $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
        ]);
        try {
            if (!Auth::attempt($payload)) {
                return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 401);
            }

            return response()->json(['status' => 'success', 'message' => 'Login successful'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    public function login(Request $request)
    {
        $payload = $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
        ]);
        try {
            if (!Auth::attempt($payload)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }
            $user = User::where('email', $payload['email'])->first();

            // Delete existing tokens before creating a new one
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;
            $user->token = $token;
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'token' => $token,
            ];
            return response()->json(['status' => 'success', 'message' => 'Login successful', 'user' => $data]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred during login'], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout successful'], 200);
    }

    public function register(Request $request)
    {
        $payload = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
        ]);

        // Hash the password before creating the user
        $payload['password'] = Hash::make($payload['password']);

        $user = User::create($payload);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['message' => 'Registration successful', 'token' => $token], 200);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user = User::with(['role', 'status'])->find($user->id);

        // Get permissions separately to avoid the addEagerConstraints error
        $permissions = $user->getAllPermissions();

        // Get permitted routes for the sidebar navigation
        $routes = $user->getPermittedRoutes();

        // Format permissions for frontend use
        $formattedPermissions = $permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
                'description' => $permission->description,
                'status' => $permission->status,
                'created_at' => $permission->created_at,
                'updated_at' => $permission->updated_at,
                'pivot' => $permission->pivot ? [
                    'role_id' => $permission->pivot->role_id ?? null,
                    'permission_id' => $permission->pivot->permission_id ?? null,
                    'status' => $permission->pivot->status ?? null,
                    'created_by' => $permission->pivot->created_by ?? null,
                    'updated_by' => $permission->pivot->updated_by ?? null,
                    'created_at' => $permission->pivot->created_at ?? null,
                    'updated_at' => $permission->pivot->updated_at ?? null,
                ] : null
            ];
        });

        return response()->json([
            'user' => $user,
            'permissions' => $formattedPermissions,
            'routes' => $routes
        ], 200);
    }


    /**
     * Get the sidebar navigation routes for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sidebar(Request $request)
    {
        $user = $request->user();
        $routes = $user->getPermittedRoutes();

        return response()->json([
            'status' => 'success',
            'data' => $routes
        ], 200);
    }
}
