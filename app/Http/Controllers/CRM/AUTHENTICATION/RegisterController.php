<?php

namespace App\Http\Controllers\CRM\AUTHENTICATION;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:staff,email',
            'phone' => 'required|string|unique:staff,phone',
            'password' => 'required|min:8',
        ]);

        // Generate username from name
        $username = strtolower(str_replace(' ', '', $request->name));

        // Add number if username exists
        $count = 1;
        $originalUsername = $username;
        while (Staff::where('username', $username)->exists()) {
            $username = $originalUsername . $count;
            $count++;
        }

        $staff = Staff::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'username' => $username,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'status' => 'active',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful',
            'data' => $staff,
        ]);
    }
}
