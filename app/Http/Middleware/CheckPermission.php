<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // Get all permissions for the user (both direct and via role)
        $userPermissions = $user->getAllPermissions();
        
        // Check if the user has the required permission
        $hasPermission = $userPermissions->contains('slug', $permission);
        
        if (!$hasPermission) {
            return response()->json([
                'message' => 'You do not have permission to access this resource.',
                'required_permission' => $permission
            ], 403);
        }
        
        return $next($request);
    }
}
