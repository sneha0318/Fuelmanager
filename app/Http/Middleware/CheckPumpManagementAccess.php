<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPumpManagementAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Check if user is a super_admin
        if (!$user || $user->user_type !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access pump management.');
        }

        return $next($request);
    }
} 