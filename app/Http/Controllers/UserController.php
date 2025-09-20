<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserLogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // Show login form
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Show register form
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function show()
    {
        $user = Auth::user();
        return view('profile', compact('user'));
    }
}

