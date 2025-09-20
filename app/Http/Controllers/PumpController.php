<?php

namespace App\Http\Controllers;

use App\Models\Pump;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PumpController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        if ($user->user_type === 'super_admin') {
            $pumps = Pump::latest()->get();
        } else {
            // For admin users, only show their assigned pumps
            $pumps = Pump::whereHas('pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email);
            })->latest()->get();
        }
        
        return view('pumps.index', compact('pumps'));
    }

    public function create()
    {
        return view('pumps.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pump_name' => 'required|string|max:50|regex:/^[a-zA-Z0-9\s]+$/|unique:pumps,pump_name',
            'location' => 'required|string|max:50|regex:/^[a-zA-Z0-9\s,.]+$/',
            'total_nozzles' => 'required|integer|min:1|max:30',
            'status' => 'required|in:active,inactive',
        ], [
            'pump_name.regex' => 'Pump name can only contain letters, numbers and spaces.',
            'pump_name.max' => 'Pump name cannot exceed 50 characters.',
            'location.regex' => 'Location can only contain letters, numbers, spaces, commas and periods.',
            'location.max' => 'Location cannot exceed 50 characters.',
            'total_nozzles.min' => 'Total nozzles must be at least 1.',
            'total_nozzles.max' => 'Total nozzles cannot exceed 30.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pump = Pump::create($request->all());

        return response()->json([
            'success' => 'Pump added successfully!', 
            'action' => 'create',
            'pump' => $pump
        ]);
    }

    public function edit(Pump $pump)
    {
        return view('pumps.edit', compact('pump'));
    }

    public function update(Request $request, Pump $pump)
    {
        $request->validate([
            'pump_name' => 'required|string|max:50|regex:/^[a-zA-Z0-9\s]+$/',
            'location' => 'required|string|max:50|regex:/^[a-zA-Z0-9\s,.]+$/',
            'total_nozzles' => 'required|integer|min:1|max:30',
            'status' => 'required|in:active,inactive'
        ], [
            'pump_name.regex' => 'Pump name can only contain letters, numbers and spaces.',
            'pump_name.max' => 'Pump name cannot exceed 50 characters.',
            'location.regex' => 'Location can only contain letters, numbers, spaces, commas and periods.',
            'location.max' => 'Location cannot exceed 50 characters.',
            'total_nozzles.min' => 'Total nozzles must be at least 1.',
            'total_nozzles.max' => 'Total nozzles cannot exceed 30.',
        ]);

        $pump->update($request->all());
        return redirect()->route('pumps.index')->with([
            'success' => 'Pump updated successfully',
            'action' => 'edit'
        ]);
    }

    public function destroy(Pump $pump)
    {
        $pump->delete();
        return redirect()->route('pumps.index')->with([
            'success' => 'Pump deleted successfully',
            'action' => 'delete'
        ]);
    }

    public function getTotalNozzles($pumpId)
    {
        $pump = Pump::find($pumpId);

        if (!$pump) {
            return response()->json(['error' => 'Pump not found'], 404);
        }

        return response()->json([
            'total_nozzles' => $pump->total_nozzles
        ]);
    }
}