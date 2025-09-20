<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    public function index()
    {
        // Only ultra super admin can access this
        if (Auth::user()->user_type !== 'ultra_super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $users = User::where('user_type', 'super_admin')
                    ->latest()
                    ->get();

        return view('registered-users.index', compact('users'));
    }

    public function activate(User $user)
    {
        if (Auth::user()->user_type !== 'ultra_super_admin') {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        if ($user->user_type !== 'super_admin') {
            return redirect()->back()->with('error', 'Can only activate super admin users.');
        }

        try {
            $user->is_active = true;
            $user->save();
            return redirect()->back()->with('success', 'Super admin activated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to activate super admin. Please try again.');
        }
    }

    public function deactivate(User $user)
    {
        if (Auth::user()->user_type !== 'ultra_super_admin') {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        if ($user->user_type !== 'super_admin') {
            return redirect()->back()->with('error', 'Can only deactivate super admin users.');
        }

        try {
            $user->is_active = false;
            $user->save();
            return redirect()->back()->with('success', 'Super admin deactivated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to deactivate super admin. Please try again.');
        }
    }
}
