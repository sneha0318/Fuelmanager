<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Determine if login is email or mobile
        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
        $credentials[$field] = $request->login;
        unset($credentials['login']);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Check if user is activated (except for ultra super admin)
            if ($user->user_type !== 'ultra_super_admin' && !$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'login' => 'Your account is not activated yet. Please contact the administrator. ',
                ]);
            }

            $request->session()->regenerate();
            return redirect()->intended($request->input('redirect_to', 'dashboard'));
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ]);
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }
}
