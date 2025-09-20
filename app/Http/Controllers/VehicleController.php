<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * Display a listing of the vehicles.
     */
    public function index()
    {
        $vehicles = Vehicle::with('loan')->get();
        return view('vehicles.index', compact('vehicles'));
    }

    /**
     * Store a newly created vehicle in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'loan_id' => 'required|exists:loans,id',
                'driver_name' => 'required|string|max:255',
                'mobile_number' => 'required|string|size:10|regex:/^[0-9]{10}$/',
                'vehicle_name' => 'required|string|max:255',
                'vehicle_number' => 'required|string|max:50',
                'bill_no' => 'required|string|max:50',
                'rate' => 'required|numeric|min:0',
                'litre' => 'required|numeric|min:0',
                'amount' => 'required|numeric|min:0'
            ], [
                'mobile_number.required' => 'Mobile number is required.',
                'mobile_number.size' => 'Mobile number must be exactly 10 digits.',
                'mobile_number.regex' => 'Mobile number must contain only digits.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $vehicle = Vehicle::create([
                'loan_id' => $request->loan_id,
                'driver_name' => $request->driver_name,
                'mobile_number' => $request->mobile_number,
                'vehicle_name' => $request->vehicle_name,
                'vehicle_number' => $request->vehicle_number,
                'bill_no' => $request->bill_no,
                'rate' => $request->rate,
                'litre' => $request->litre,
                'amount' => $request->amount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle information saved successfully',
                'vehicle' => $vehicle
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Vehicle creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save vehicle information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified vehicle.
     */
    public function show(Vehicle $vehicle)
    {
        return response()->json(['vehicle' => $vehicle]);
    }

    /**
     * Update the specified vehicle in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'driver_name' => 'required|string|max:255',
            'mobile_number' => 'required|string|size:10|regex:/^[0-9]{10}$/',
            'vehicle_name' => 'required|string|max:255',
            'vehicle_number' => 'required|string|max:50',
            'bill_no' => 'required|string|max:50',
            'rate' => 'required|numeric|min:0',
            'litre' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0'
        ], [
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.size' => 'Mobile number must be exactly 10 digits.',
            'mobile_number.regex' => 'Mobile number must contain only digits.'
        ]);

        $vehicle->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Vehicle information updated successfully',
            'vehicle' => $vehicle
        ]);
    }

    /**
     * Remove the specified vehicle from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle information deleted successfully'
        ]);
    }

    /**
     * Get vehicle information for a specific loan.
     */
    public function getByLoan($loanId)
    {
        $vehicles = Vehicle::where('loan_id', $loanId)->get();

        return response()->json([
            'success' => true,
            'vehicles' => $vehicles
        ]);
    }
}
