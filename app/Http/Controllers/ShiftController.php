<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Pump;
use App\Models\PumpUser;
use App\Models\Nozzle;
use App\Models\Rate;
use App\Models\Item;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        
        $query = Shift::with(['pump', 'pumpUser', 'nozzle']);
        $filterDate = $request->input('shift_date', \Carbon\Carbon::now()->format('Y-m-d'));
        if ($filterDate) {
            $query->whereDate('start_date', $filterDate);
        }
        $shifts = $query->get();
        

        if ($user->user_type === 'super_admin') {
            $pumps = Pump::where('status', 'active')->get();
            $pumpUsers = PumpUser::where('status', 'active')
                ->where('user_type', 'operator')
                ->get();
            $nozzles = Nozzle::with('item')->get();
        } else {
            // For admin users, only show their assigned pumps
            $pumps = Pump::whereHas('pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email);
            })->where('status', 'active')->get();


            $shifts = Shift::whereIn('pump_id', $pumps->pluck('id'))
                ->with(['pump', 'pumpUser', 'nozzle'])
                ->get();

            $pumpUsers = PumpUser::whereIn('pump_id', $pumps->pluck('id'))
                ->where('status', 'active')
                ->where('user_type', 'operator')
                ->get();

            $nozzles = Nozzle::whereIn('pump_id', $pumps->pluck('id'))
                ->with('item')
                ->get();
        }

        $rates = Rate::with(['pump', 'item'])
            ->whereHas('item', function($query) {
                $query->where('status', 'Active');
            })
            ->get();

        return view('shifts.index', compact('shifts', 'pumps', 'pumpUsers', 'nozzles', 'rates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();

        if ($user->user_type === 'super_admin') {
            $pumps = Pump::where('status', 'active')->get();
            $pumpUsers = PumpUser::where('status', 'active')
                ->where('user_type', 'operator')
                ->get();
            $nozzles = Nozzle::with('item')->get();
        } else {
            // For admin users, only show their assigned pumps
            $pumps = Pump::whereHas('pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email);
            })->where('status', 'active')->get();

            $pumpUsers = PumpUser::whereIn('pump_id', $pumps->pluck('id'))
                ->where('status', 'active')
                ->where('user_type', 'operator')
                ->get();

            $nozzles = Nozzle::whereIn('pump_id', $pumps->pluck('id'))
                ->with('item')
                ->get();
        }

        return view('shifts.create', compact('pumps', 'pumpUsers', 'nozzles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pump_id' => 'required|exists:pumps,id',
            'pump_user_id' => 'required|exists:pump_users,id',
            'nozzle_id' => 'required|exists:nozzles,id',
            'shift_name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'is_active' => 'boolean'
        ]);

        // Check if a shift already exists for this pump with the same name
        $existingShift = Shift::where('pump_id', $request->pump_id)
            ->where('shift_name', $request->shift_name)
            ->first();

        if ($existingShift) {
            // Update the existing shift instead of creating a new one
            $existingShift->update([
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'is_active' => $request->has('is_active') ? true : false
            ]);

            return redirect()->route('shifts.index')
                ->with('success', 'Shift updated successfully.');
        }

        // Create a new shift if none exists
        Shift::create($validated);

        return redirect()->route('shifts.index')
            ->with('success', 'Shift created successfully.');
    }

    /**
     * Store multiple shifts at once.
     */
    public function bulkStore(Request $request)
    {
        $data = $request->all();

        // Use the provided start_date or default to current date
        $startDate = $request->start_date ?? now()->format('Y-m-d');
        $endDate = $request->end_date ?? now()->format('Y-m-d');

        try {
            $request->validate([
                'pumps' => 'required|array',
                'pumps.*' => 'exists:pumps,id',
                'pump_users' => 'required|array',
                'pump_users.*' => 'exists:pump_users,id',
                'nozzles' => 'required|array',
                'nozzles.*' => 'exists:nozzles,id',
                'shift_name' => 'required|string|max:255',
                'start_time' => 'required|date_format:H:i:s',
                'end_time' => 'required|date_format:H:i:s',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'is_active' => 'boolean'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Create new shifts
            foreach ($request->pumps as $pumpId) {
                foreach ($request->pump_users as $pumpUserId) {
                    foreach ($request->nozzles as $nozzleId) {
                        // Check if a shift already exists for this combination
                        $existingShift = Shift::where('pump_id', $pumpId)
                            ->where('pump_user_id', $pumpUserId)
                            ->where('nozzle_id', $nozzleId)
                            ->where('shift_name', $request->shift_name)
                            ->where('start_date', $startDate)
                            ->where('end_date', $endDate)
                            ->first();

                        if (!$existingShift) {
                            // Create new shift only if it doesn't exist
                            Shift::create([
                                'pump_id' => $pumpId,
                                'pump_user_id' => $pumpUserId,
                                'nozzle_id' => $nozzleId,
                                'shift_name' => $request->shift_name,
                                'start_time' => $request->start_time,
                                'end_time' => $request->end_time,
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'is_active' => $request->is_active ?? true
                            ]);
                        } else {
                            // Update existing shift
                            $existingShift->update([
                                'start_time' => $request->start_time,
                                'end_time' => $request->end_time,
                                'is_active' => $request->is_active ?? true
                            ]);
                        }
                    }
                }
            }

            // Commit the transaction
            DB::commit();

            // Check if the request expects JSON (AJAX request)
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Shifts created/updated successfully.'
                ]);
            }

            return redirect()->route('shifts.index')
                ->with('success', 'Shifts created/updated successfully.');
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();

            // Log the error
            \Log::error('Bulk Shift Creation Error: ' . $e->getMessage());

            // Check if the request expects JSON (AJAX request)
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create/update shifts: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create/update shifts: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Shift $shift)
    {
        $pumps = Pump::all();
        return view('shifts.edit', compact('shift', 'pumps'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'pump_id' => 'required|exists:pumps,id',
            'pump_user_id' => 'required|exists:pump_users,id',
            'nozzle_id' => 'required|exists:nozzles,id',
            'shift_name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'required|boolean',
        ]);

        $shift = Shift::findOrFail($id);

        $shift->update([
            'pump_id' => $request->pump_id,
            'pump_user_id' => $request->pump_user_id,
            'nozzle_id' => $request->nozzle_id,
            'shift_name' => $request->shift_name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift updated successfully!'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shift $shift)
    {
        try {
            // Store shift name for response message
            $shiftName = $shift->shift_name;
            $pumpName = $shift->pump->pump_name ?? 'Unknown';
            
            $shift->delete();
            
            // For AJAX requests, return JSON response
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Shift '$shiftName' for pump '$pumpName' was deleted successfully."
                ]);
            }
            
            // For regular requests, redirect with a flash message
            return redirect()->route('shifts.index')
                ->with('success', 'Shift deleted successfully.');
        } catch (\Exception $e) {
            // For AJAX requests, return JSON error
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting shift: ' . $e->getMessage()
                ], 500);
            }
            
            // For regular requests, redirect with error message
            return redirect()->route('shifts.index')
                ->with('error', 'Error deleting shift: ' . $e->getMessage());
        }
    }

    /**
     * Get shifts for a specific pump
     */
    public function getPumpShifts(Pump $pump)
    {
        $shifts = Shift::where('pump_id', $pump->id)
            ->where('is_active', true)
            ->get();

        return response()->json($shifts);
    }

    /**
     * Store a newly created rate resource in storage.
     */
    public function storeRate(Request $request)
    {
        $request->validate([
            'pump_id' => 'required|exists:pumps,id',
            'item_id' => 'required|exists:items,id',
            'rate' => 'required|numeric|min:0',
            'rate_date' => 'nullable|date'
        ]);

        // Check if the item is active
        $item = Item::find($request->item_id);
        if (!$item || $item->status !== 'Active') {
            return redirect()->route('shifts.index')
                ->with('error', 'Cannot create rate for inactive item.');
        }

        $rateDate = $request->rate_date ?? now()->format('Y-m-d');

        // Check if a rate already exists for this pump and item on the same date
        $existingRate = Rate::where('pump_id', $request->pump_id)
            ->where('item_id', $request->item_id)
            ->where('rate_date', $rateDate)
            ->first();

        if ($existingRate) {
            // Update the existing rate instead of creating a new one
            $existingRate->update([
                'rate' => $request->rate,
                'is_active' => $request->has('is_active') ? true : false
            ]);
            
            return redirect()->route('shifts.index')
                ->with('success', 'Rate updated successfully.');
        }

        // Create a new rate if none exists
        Rate::create([
            'pump_id' => $request->pump_id,
            'item_id' => $request->item_id,
            'rate' => $request->rate,
            'rate_date' => $rateDate,
            'is_active' => $request->has('is_active') ? true : false
        ]);

        return redirect()->route('shifts.index')
            ->with('success', 'Rate created successfully.');
    }

    /**
     * Store multiple rates at once.
     */
    public function bulkStoreRate(Request $request)
    {
        // Log the request data for debugging
        Log::info('Bulk store request data:', $request->all());
        
        $request->validate([
            'pump_id' => 'required|exists:pumps,id',
            'rate_date' => 'required|date',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.rate' => 'nullable|numeric|min:0',
        ]);

        $pumpId = $request->pump_id;
        $rateDate = Carbon::parse($request->rate_date);
        
        // Get pump name for success message
        $pumpName = Pump::find($pumpId)->pump_name;

        // Get all active items
        $activeItemIds = Item::where('status', 'Active')->pluck('id')->toArray();
        
        // Filter out inactive items
        $items = collect($request->items)->filter(function($item) use ($activeItemIds) {
            return in_array($item['item_id'], $activeItemIds);
        })->all();
        
        if (empty($items)) {
            return redirect()->route('shifts.index')
                ->with('error', 'No active items found to create rates for.');
        }
        
        // Check if at least one rate is provided
        $hasAnyRate = collect($items)->contains(function($item) {
            return isset($item['rate']) && $item['rate'] !== '' && $item['rate'] !== null;
        });
        
        if (!$hasAnyRate) {
            return redirect()->route('shifts.index')
                ->with('error', 'Please provide at least one rate value.');
        }
        
        // Log the processed data
        Log::info('Processed data:', [
            'pump_id' => $pumpId,
            'rate_date' => $rateDate,
            'items_count' => count($items)
        ]);
        
        // Create rates for each item
        foreach ($items as $itemId => $itemData) {
            // Skip if rate is not provided
            if (!isset($itemData['rate']) || $itemData['rate'] === '' || $itemData['rate'] === null) {
                continue;
            }
            
            // Log each item being processed
            Log::info('Processing item:', [
                'item_id' => $itemId,
                'item_data' => $itemData
            ]);
            
            // Check if a rate already exists for this pump and item on the same date
            $existingRate = Rate::where('pump_id', $pumpId)
                ->where('item_id', $itemData['item_id'])
                ->where('rate_date', $rateDate->format('Y-m-d'))
                ->first();

            if ($existingRate) {
                // Update the existing rate
                $existingRate->update([
                    'rate' => $itemData['rate'],
                    'rate_date' => $rateDate->format('Y-m-d'),
                    'updated_at' => now()
                ]);
                Log::info('Updated existing rate:', ['rate_id' => $existingRate->id]);
            } else {
                // Create a new rate
                $newRate = Rate::create([
                    'pump_id' => $pumpId,
                    'item_id' => $itemData['item_id'],
                    'rate' => $itemData['rate'],
                    'rate_date' => $rateDate->format('Y-m-d'),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Log::info('Created new rate:', ['rate_id' => $newRate->id]);
            }
        }

        return redirect()->route('shifts.index')
            ->with('success', 'Rates updated successfully for ' . $pumpName . ' on ' . $rateDate->format('d M Y'));
    }

    /**
     * Update the specified rate resource in storage.
     */
    public function updateRate(Request $request, Rate $rate)
    {
        $request->validate([
            'pump_id' => 'required|exists:pumps,id',
            'item_id' => 'required|exists:items,id',
            'rate' => 'required|numeric|min:0',
            'rate_date' => 'nullable|date'
        ]);

        // Check if the item is active
        $item = Item::find($request->item_id);
        if (!$item || $item->status !== 'Active') {
            return redirect()->route('shifts.index')
                ->with('error', 'Cannot update rate for inactive item.');
        }

        $rateDate = $request->rate_date ?? $rate->rate_date ?? now()->format('Y-m-d');

        // Update the rate with all fields including is_active and rate_date
        $rate->update([
            'pump_id' => $request->pump_id,
            'item_id' => $request->item_id,
            'rate' => $request->rate,
            'rate_date' => $rateDate,
            'is_active' => $request->has('is_active') ? true : false
        ]);

        return redirect()->route('shifts.index')
            ->with('success', 'Rate updated successfully.');
    }

    /**
     * Remove the specified rate resource from storage.
     */
    public function destroyRate(Rate $rate)
    {
        $rate->delete();

        return redirect()->route('shifts.index')
            ->with('success', 'Rate deleted successfully.');
    }

    /**
     * Get items for a specific pump
     */
    public function getPumpItems(Pump $pump)
    {
        // Get only active items that are available for this pump
        $items = Item::where('status', 'Active')->get();
        
        return response()->json($items);
    }

    /**
     * Get items with their current rates for a specific pump
     */
    public function getPumpItemsWithRates(Pump $pump)
    {
        // Get active items that are assigned to this pump's nozzles
        $items = Item::where('status', 'Active')
            ->whereHas('nozzles', function($query) use ($pump) {
                $query->where('pump_id', $pump->id)
                      ->where('status', 'active');
            })
            ->get();

        $itemsWithRates = [];
        
        foreach ($items as $item) {
            $currentRate = Rate::where('pump_id', $pump->id)
                ->where('item_id', $item->id)
                ->where('is_active', true)
                ->latest()
                ->first();
                
            $itemsWithRates[] = [
                'id' => $item->id,
                'name' => $item->name,
                'current_rate' => $currentRate ? $currentRate->rate : null,
                'nozzle_number' => $item->nozzles()
                    ->where('pump_id', $pump->id)
                    ->where('status', 'active')
                    ->value('nozzle_number')
            ];
        }
        
        return response()->json($itemsWithRates);
    }

    /**
     * Get the rate for an item on a specific date
     */
    public function getItemRate(Request $request, $pumpId, $itemId)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        
        // Check if the item is active
        $item = Item::find($itemId);
        if (!$item || $item->status !== 'Active') {
            return response()->json([
                'rate' => null
            ]);
        }
        
        $rate = Rate::where('pump_id', $pumpId)
            ->where('item_id', $itemId)
            ->where('is_active', true)
            ->whereDate('created_at', '<=', $date)
            ->latest()
            ->first();
            
        return response()->json([
            'rate' => $rate ? $rate->rate : null
        ]);
    }

    /**
     * Get all pumps with their items and current rates
     */
    public function getAllPumpsWithItemsAndRates(Request $request)
    {
        $user = Auth::user();
        $requestDate = $request->input('date', now()->format('Y-m-d'));
        
        // Get pumps based on user access
        if ($user->user_type === 'super_admin') {
            $pumps = Pump::where('status', 'active')->get();
        } else {
            // For admin users, only show their assigned pumps
            $pumps = Pump::whereHas('pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email);
            })->where('status', 'active')->get();
        }
        
        $result = [];
        
        foreach ($pumps as $pump) {
            $pumpData = [
                'id' => $pump->id,
                'pump_name' => $pump->pump_name,
                'items' => []
            ];
            
            // Get active items that are assigned to this pump's nozzles
            $items = Item::where('status', 'Active')
                ->whereHas('nozzles', function($query) use ($pump) {
                    $query->where('pump_id', $pump->id)
                          ->where('status', 'active');
                })
                ->get();
                
            foreach ($items as $item) {
                // Get the most recent rate for the specified date or before
                $currentRate = Rate::where('pump_id', $pump->id)
                    ->where('item_id', $item->id)
                    ->where('rate_date', '<=', $requestDate)
                    ->orderBy('rate_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->first();
                    
                $pumpData['items'][] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'rate' => $currentRate ? [
                        'id' => $currentRate->id,
                        'rate' => $currentRate->rate,
                        'rate_date' => $currentRate->rate_date,
                        'is_active' => $currentRate->is_active,
                        'created_at' => $currentRate->created_at
                    ] : null,
                    'nozzle_number' => $item->nozzles()
                        ->where('pump_id', $pump->id)
                        ->where('status', 'active')
                        ->value('nozzle_number')
                ];
            }
            
            $result[] = $pumpData;
        }
        
        return response()->json(['pumps' => $result]);
    }

    /**
     * Save rates for multiple items in multiple pumps via API
     */
    public function bulkSaveRates(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'rates' => 'required|array',
                'rates.*.pump_id' => 'required|exists:pumps,id',
                'rates.*.item_id' => 'required|exists:items,id',
                'rates.*.rate' => 'required|numeric|min:0',
                'rates.*.is_active' => 'required|boolean',
                'rates.*.date' => 'required|date',
            ]);

            $successCount = 0;
            $errorCount = 0;
            
            DB::beginTransaction();
            
            foreach ($request->rates as $rateData) {
                $pumpId = $rateData['pump_id'];
                $itemId = $rateData['item_id'];
                $rateValue = $rateData['rate'];
                $isActive = $rateData['is_active'];
                $rateDate = Carbon::parse($rateData['date']);
                $rateId = isset($rateData['rate_id']) ? $rateData['rate_id'] : null;
                
                try {
                    if ($rateId && $rateId !== 'new') {
                        // Update existing rate
                        $rate = Rate::find($rateId);
                        
                        if ($rate) {
                            $rate->update([
                                'rate' => $rateValue,
                                'rate_date' => $rateDate->format('Y-m-d'),
                                'is_active' => $isActive,
                                'updated_at' => now()
                            ]);
                            
                            $successCount++;
                        } else {
                            $errorCount++;
                        }
                    } else {
                        // Check if a rate already exists for this pump, item, and date
                        $existingRate = Rate::where('pump_id', $pumpId)
                            ->where('item_id', $itemId)
                            ->where('rate_date', $rateDate->format('Y-m-d'))
                            ->first();

                        if ($existingRate) {
                            // Update existing rate for the same date
                            $existingRate->update([
                                'rate' => $rateValue,
                                'is_active' => $isActive,
                                'updated_at' => now()
                            ]);
                            $successCount++;
                        } else {
                            // Create new rate
                            Rate::create([
                                'pump_id' => $pumpId,
                                'item_id' => $itemId,
                                'rate' => $rateValue,
                                'rate_date' => $rateDate->format('Y-m-d'),
                                'is_active' => $isActive,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $successCount++;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error saving rate: ' . $e->getMessage(), [
                        'pump_id' => $pumpId,
                        'item_id' => $itemId
                    ]);
                    $errorCount++;
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully saved $successCount rates" . ($errorCount > 0 ? " ($errorCount errors)" : "")
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk save rates error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error saving rates: ' . $e->getMessage()
            ], 500);
        }
    }

}
