<?php

namespace App\Http\Controllers;

use App\Models\PumpUser;
use App\Models\Pump;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PumpUserController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->user_type === 'super_admin') {
            $users = PumpUser::with('pump')->latest()->get();
            $pumps = Pump::where('status', 'active')->get();
        } else {
            // For admin users, only show users for their assigned pumps
            $pumps = Pump::whereHas('pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email);
            })->where('status', 'active')->get();

            $users = PumpUser::whereIn('pump_id', $pumps->pluck('id'))
                            ->with('pump')
                            ->latest()
                            ->get();
        }

        return view('pump-users.index', compact('users', 'pumps'));
    }

    public function create()
    {
        $pumps = Pump::where('status', 'active')->get();
        return view('pump-users.index', compact('pumps'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string|max:255',
            'email' => 'required|email|unique:pump_users,email|unique:users,email',
            'mobile_number' => 'required|string|size:10|regex:/^[0-9]{10}$/|unique:users,mobile|unique:pump_users,mobile_number',
            'user_type' => 'required|string',
            'password' => 'required|string|min:6',
            'pump_id' => 'required|exists:pumps,id',
            'status' => 'required|in:active,inactive',
        ]);

        $pumpUser = PumpUser::create([
            'user_name' => $request->user_name,
            'email' => $request->email,
            'mobile_number' => $request->mobile_number,
            'user_type' => $request->user_type,
            'password' => bcrypt($request->password),
            'pump_id' => $request->pump_id,
            'pump_name' => Pump::find($request->pump_id)->pump_name,
            'status' => $request->status,
        ]);

        // Insert into users table with is_active set to true
        User::create([
            'name' => $request->user_name,
            'email' => $request->email,
            'mobile' => $request->mobile_number,
            'password' => bcrypt($request->password),
            'user_type' => $request->user_type,
            'is_active' => true // Automatically activate the user
        ]);

        return redirect()->route('pump-users.index')->with([
            'success' => 'User created and activated successfully.',
            'action' => 'create'
        ]);
    }

    public function edit(PumpUser $pumpUser)
    {
        $pumps = Pump::where('status', 'active')->get();
        return view('pump-users.index', compact('pumpUser', 'pumps'));
    }

    public function update(Request $request, PumpUser $pumpUser)
    {
        // Find the corresponding user in users table for validation
        $user = User::where('email', $pumpUser->email)->first();
        $userId = $user ? $user->id : null;

        $request->validate([
            'user_name' => 'required|string|max:255',
            'email' => 'required|email|unique:pump_users,email,' . $pumpUser->id . '|unique:users,email,' . $userId,
            'mobile_number' => 'required|string|size:10|regex:/^[0-9]{10}$/|unique:users,mobile,' . $userId,
            'user_type' => 'required|string|max:50',
            'pump_id' => 'required|exists:pumps,id',
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,inactive'
        ]);

        $data = $request->only(['user_name', 'email', 'mobile_number', 'user_type', 'pump_id', 'status']);

        // Add pump name
        $data['pump_name'] = Pump::find($request->pump_id)->pump_name;

        // Handle password
        if (!empty($request->password)) {
            $data['password'] = bcrypt($request->password);
        }

        $pumpUser->update($data);

        // Update users table
        if ($user) {
            $userData = [
                'name' => $request->user_name,
                'email' => $request->email,
                'mobile' => $request->mobile_number,
                'user_type' => $request->user_type,
                'is_active' => true // Keep the user activated
            ];

            // Only update password if provided
            if (!empty($request->password)) {
                $userData['password'] = bcrypt($request->password);
            }

            $user->update($userData);
        }

        return redirect()->route('pump-users.index')->with([
            'success' => 'User updated successfully',
            'action' => 'edit'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            // Begin a database transaction to ensure both records are deleted or none
            DB::beginTransaction();

            // Find the pump user record
            $pumpUser = PumpUser::findOrFail($id);

            // Find the corresponding user record by email
            $user = User::where('email', $pumpUser->email)->first();

            // Delete both records if they exist
            if ($user) {
                $user->delete();
            }

            $pumpUser->delete();

            // Commit the transaction
            DB::commit();

            return redirect()->route('pump-users.index')
                ->with([
                    'success' => 'User deleted successfully',
                    'action' => 'delete'
                ]);
        } catch (\Exception $e) {
            // Rollback the transaction in case of errors
            DB::rollBack();

            return redirect()->route('pump-users.index')
                ->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }
}
