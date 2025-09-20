<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'mobile' => 'required|string|max:15|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.unique' => 'This email is already registered. ',
            'mobile.unique' => 'This mobile number is already registered. ',
        ]);

        // Check if this is the first user registration
        $isFirstUser = User::count() === 0;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'user_type' => $isFirstUser ? 'ultra_super_admin' : 'super_admin', // Set as super_admin for new registrations
            'is_active' => $isFirstUser ? true : false, // Only first user is auto-activated
        ]);

        if ($isFirstUser) {
            Auth::login($user);
            return redirect()->route('dashboard');
        } else {
            return redirect()->route('login')->with('success', 'Registration successful. Please wait for admin approval to login.');
        }
    }
}
