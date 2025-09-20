<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with('owner')->get();
        return response()->json(['data' => $vehicles], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'registration_number' => 'required|string|unique:vehicles',
            'chassis_number' => 'required|string|unique:vehicles',
            'engine_number' => 'required|string|unique:vehicles',
            'make' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'required|string',
            'status' => 'required|in:active,inactive',
            'owner_id' => 'required|exists:owners,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle = Vehicle::create($request->all());
        return response()->json(['data' => $vehicle], 201);
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load('owner', 'fuelTransactions');
        return response()->json(['data' => $vehicle], 200);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validator = Validator::make($request->all(), [
            'registration_number' => 'string|unique:vehicles,registration_number,' . $vehicle->id,
            'chassis_number' => 'string|unique:vehicles,chassis_number,' . $vehicle->id,
            'engine_number' => 'string|unique:vehicles,engine_number,' . $vehicle->id,
            'make' => 'string',
            'model' => 'string',
            'year' => 'integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'string',
            'status' => 'in:active,inactive',
            'owner_id' => 'exists:owners,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle->update($request->all());
        return response()->json(['data' => $vehicle], 200);
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return response()->json(null, 204);
    }

    public function getByOwner(Owner $owner)
    {
        $vehicles = $owner->vehicles;
        return response()->json(['data' => $vehicles], 200);
    }

    public function getFuelTransactions(Vehicle $vehicle)
    {
        $transactions = $vehicle->fuelTransactions()->with('pump')->get();
        return response()->json(['data' => $transactions], 200);
    }

    public function search(Request $request)
    {
        $query = Vehicle::query();

        if ($request->has('registration_number')) {
            $query->where('registration_number', 'like', '%' . $request->registration_number . '%');
        }

        if ($request->has('chassis_number')) {
            $query->where('chassis_number', 'like', '%' . $request->chassis_number . '%');
        }

        if ($request->has('engine_number')) {
            $query->where('engine_number', 'like', '%' . $request->engine_number . '%');
        }

        if ($request->has('make')) {
            $query->where('make', 'like', '%' . $request->make . '%');
        }

        if ($request->has('model')) {
            $query->where('model', 'like', '%' . $request->model . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $vehicles = $query->with('owner')->get();
        return response()->json(['data' => $vehicles], 200);
    }
} 