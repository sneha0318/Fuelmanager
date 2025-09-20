<?php

namespace App\Http\Controllers;

use App\Models\Nozzle;
use App\Models\Pump;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NozzleController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->user_type === 'super_admin') {
            $nozzles = Nozzle::with(['pump', 'item', 'creator'])->latest()->get();
            $pumps = Pump::where('status', 'active')->get();
            $items = Item::where('status', 'active')->get();
        } else {
            // For admin users, only show nozzles for their assigned pumps
            $pumps = Pump::whereHas('pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email);
            })->where('status', 'active')->get();
            
            $nozzles = Nozzle::whereIn('pump_id', $pumps->pluck('id'))
                            ->with(['pump', 'item', 'creator'])
                            ->latest()
                            ->get();
                            
            $items = Item::whereHas('activePumps', function($query) use ($pumps) {
                $query->whereIn('pump_id', $pumps->pluck('id'));
            })->where('status', 'active')->get();
        }
        
        return view('nozzles.index', compact('nozzles', 'pumps', 'items'));
    }

    public function create()
    {
        $pumps = Pump::where('status', 'active')->get();
        $items = Item::where('status', 'active')->get();
        return view('nozzles.create', compact('pumps', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pump_id' => 'required|exists:pumps,id',
            'nozzle_number' => 'required|integer|min:1',
            'item_id' => 'required|exists:items,id',
            'status' => 'required|in:active,inactive'
        ]);

        // Check if the nozzle number is already used for this pump
        $existingNozzle = Nozzle::where('pump_id', $request->pump_id)
            ->where('nozzle_number', $request->nozzle_number)
            ->first();

        if ($existingNozzle) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['nozzle_number' => 'This nozzle number is already in use for this pump.']);
        }

        Nozzle::create($request->all());

        return redirect()->route('nozzles.index')->with('success', 'Nozzle created successfully');
    }

    public function edit(Nozzle $nozzle)
    {
        $pumps = Pump::where('status', 'active')->get();
        $items = Item::where('status', 'active')->get();
        return view('nozzles.edit', compact('nozzle', 'pumps', 'items'));
    }

    public function update(Request $request, Nozzle $nozzle)
    {
        $request->validate([
            'pump_id' => 'required|exists:pumps,id',
            'nozzle_number' => 'required|integer|min:1',
            'item_id' => 'required|exists:items,id',
            'status' => 'required|in:active,inactive'
        ]);

        // Check if the nozzle number is already used for this pump by another nozzle
        $existingNozzle = Nozzle::where('pump_id', $request->pump_id)
            ->where('nozzle_number', $request->nozzle_number)
            ->where('id', '!=', $nozzle->id)
            ->first();

        if ($existingNozzle) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['nozzle_number' => 'This nozzle number is already in use for this pump.']);
        }

        $nozzle->update($request->all());
        return redirect()->route('nozzles.index')->with('success', 'Nozzle updated successfully');
    }

    public function destroy(Nozzle $nozzle)
    {
        $nozzle->delete();
        return redirect()->route('nozzles.index')->with('success', 'Nozzle deleted successfully');
    }

    public function getAvailableNozzleNumbers(Request $request)
    {
        try {
            $pumpId = $request->input('pump_id');
            $currentItemId = $request->input('current_item_id'); // ID of item being edited
            
            if (!$pumpId) {
                return response()->json([
                    'error' => 'Pump ID is required',
                    'numbers' => [],
                    'total_nozzles' => 0
                ], 400);
            }
            
            $pump = Pump::findOrFail($pumpId);
            
            // Get all possible nozzle numbers (1 to total_nozzles)
            $allPossibleNumbers = range(1, $pump->total_nozzles);
            
            // Get all nozzles for this pump to check which numbers are in use
            $nozzlesQuery = Nozzle::where('pump_id', $pumpId)
                ->whereHas('item', function($query) {
                    $query->where('name', '!=', 'Unassigned');
                });
            
            // If we're editing an item, we need to find its current nozzle assignment
            $currentItemNozzleNumber = null;
            if ($currentItemId) {
                $currentItemNozzle = Nozzle::where('pump_id', $pumpId)
                    ->where('item_id', $currentItemId)
                    ->first();
                
                if ($currentItemNozzle) {
                    $currentItemNozzleNumber = $currentItemNozzle->nozzle_number;
                }
            }
            
            $existingNozzleNumbers = $nozzlesQuery->pluck('nozzle_number')->toArray();
            
            // Create a response with all numbers and mark which ones are already used
            $numbers = [];
            foreach ($allPossibleNumbers as $number) {
                $isUsed = in_array($number, $existingNozzleNumbers);
                
                // If this is the current item's nozzle, it should not be marked as "used"
                if ($currentItemNozzleNumber && $number == $currentItemNozzleNumber) {
                    $isUsed = false;
                }
                
                $numbers[] = [
                    'value' => $number,
                    'text' => $number,
                    'used' => $isUsed
                ];
            }
            
            return response()->json([
                'numbers' => $numbers,
                'total_nozzles' => $pump->total_nozzles
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in getAvailableNozzleNumbers: ' . $e->getMessage());
            return response()->json([
                'error' => 'An error occurred while fetching nozzle numbers',
                'numbers' => [],
                'total_nozzles' => 0
            ], 500);
        }
    }

    public function getNozzlesByPump($pumpId)
    {
        try {
            $nozzles = Nozzle::where('pump_id', $pumpId)
                ->where('status', 'active')
                ->with('item')
                ->get()
                ->map(function ($nozzle) {
                    return [
                        'id' => $nozzle->id,
                        'nozzle_number' => $nozzle->nozzle_number,
                        'item_id' => $nozzle->item_id,
                        'item_name' => $nozzle->item->name ?? 'N/A',
                        'display_text' => "Nozzle #{$nozzle->nozzle_number} - {$nozzle->item->name}"
                    ];
                });
            
            return response()->json($nozzles);
        } catch (\Exception $e) {
            Log::error('Error in getNozzlesByPump: ' . $e->getMessage());
            return response()->json([
                'error' => 'An error occurred while fetching nozzles'
            ], 500);
        }
    }

    public function getItemsByPump($pumpId)
    {
        try {
            $items = Item::whereHas('rates', function ($query) use ($pumpId) {
                $query->where('pump_id', $pumpId)
                      ->where('is_active', true);
            })->where('status', 'active')->get();
            
            return response()->json($items);
        } catch (\Exception $e) {
            Log::error('Error in getItemsByPump: ' . $e->getMessage());
            return response()->json([
                'error' => 'An error occurred while fetching items'
            ], 500);
        }
    }

    public function getNozzlesByUser($userId)
    {
        $pumpUser = \App\Models\PumpUser::find($userId);

        if (!$pumpUser || !$pumpUser->pump_id) {
            return response()->json([]);
        }

        $nozzles = \App\Models\Nozzle::where('pump_id', $pumpUser->pump_id)
            ->with(['pump', 'item'])
            ->get()
            ->map(function ($nozzle) {
                return [
                    'id' => $nozzle->id,
                    'nozzle_number' => $nozzle->nozzle_number,
                    'pump_name' => $nozzle->pump->pump_name ?? 'N/A',
                    'item_name' => $nozzle->item->name ?? 'N/A'
                ];
            });

        return response()->json($nozzles);
    }

    public function availableByPump($pumpId)
    {
        // Debugging log
        Log::info("Fetching nozzles for pump ID: $pumpId");

        $pump = Pump::find($pumpId);

        if (!$pump) {
            Log::error("Pump not found for ID: $pumpId");
            return response()->json(['error' => 'Pump not found'], 404);
        }

        // Fetch all nozzles for the pump
        $nozzles = Nozzle::where('pump_id', $pumpId)->get(['id', 'nozzle_number']);

        // Debugging log
        Log::info("Nozzles fetched for pump ID $pumpId: ", $nozzles->toArray());

        // Return the total number of nozzles and the nozzles list
        return response()->json([
            'total_nozzles' => $pump->total_nozzles,
            'nozzles' => $nozzles
        ]);
    }

    public function getAllNozzlesByPump($pumpId)
    {
        try {
            $user = Auth::user();
            $isAdmin = in_array($user->user_type, ['super_admin', 'admin']);

            $nozzles = Nozzle::where('pump_id', $pumpId)
                ->where('status', 'active')
                ->with(['item', 'shifts' => function($query) {
                    $query->where('is_active', true)
                        ->whereDate('start_date', '<=', now())
                        ->whereDate('end_date', '>=', now())
                        ->with(['pumpUser' => function($query) {
                            $query->select('id', 'user_name', 'email');
                        }]);
                }])
                ->get()
                ->map(function ($nozzle) use ($isAdmin, $user) {
                    // Get shift and operator information
                    $shiftInfo = $nozzle->shifts->map(function ($shift) {
                        return [
                            'shift_name' => $shift->shift_name,
                            'operator_name' => $shift->pumpUser->user_name ?? 'N/A',
                            'operator_email' => $shift->pumpUser->email ?? 'N/A'
                        ];
                    });

                    // For admin users (both super_admin and admin), show all nozzles with operator info
                    if ($isAdmin) {
                        $displayText = "Nozzle #{$nozzle->nozzle_number} - {$nozzle->item->name}";
                        if ($shiftInfo->isNotEmpty()) {
                            $operatorInfo = $shiftInfo->map(function($info) {
                                return "{$info['shift_name']} ({$info['operator_name']})";
                            })->join(', ');
                            $displayText .= " [{$operatorInfo}]";
                        } else {
                            $displayText .= " [Not Assigned]";
                        }
                    } else {
                        // For operators, only show their assigned nozzles
                        $userShifts = $shiftInfo->filter(function($info) use ($user) {
                            return $info['operator_email'] === $user->email;
                        });

                        if ($userShifts->isEmpty()) {
                            return null; // Skip nozzles not assigned to this operator
                        }

                        $displayText = "Nozzle #{$nozzle->nozzle_number} - {$nozzle->item->name}";
                    }

                    return [
                        'id' => $nozzle->id,
                        'nozzle_number' => $nozzle->nozzle_number,
                        'item_id' => $nozzle->item_id,
                        'item_name' => $nozzle->item->name ?? 'N/A',
                        'item_type' => $nozzle->item->item_type ?? 'Litre',
                        'display_text' => $displayText,
                        'shift_info' => $shiftInfo,
                        'shift_ids' => $nozzle->shifts->pluck('id')->toArray()
                    ];
                })
                ->filter() // Remove null values (nozzles not assigned to operator)
                ->values(); // Re-index array
            
            return response()->json($nozzles);
        } catch (\Exception $e) {
            Log::error('Error in getAllNozzlesByPump: ' . $e->getMessage());
            return response()->json([
                'error' => 'An error occurred while fetching nozzles'
            ], 500);
        }
    }
}