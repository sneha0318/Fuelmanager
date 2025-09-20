<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Pump;
use App\Models\Rate;
use App\Models\Nozzle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    /**
     * Get items for a specific pump (AJAX)
     */
    public function getPumpItems(Pump $pump)
    {
        // Get items associated with the specified pump
        $items = Item::whereHas('nozzles', function($query) use ($pump) {
            $query->where('pump_id', $pump->id);
        })->select('id', 'name', 'item_type')->get();

        return response()->json(['items' => $items]);
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->user_type === 'super_admin') {
            $items = Item::where('name', '!=', 'Unassigned')
                        ->with(['activePumps', 'nozzles.pump'])
                        ->latest()
                        ->get();
            $pumps = Pump::where('status', 'active')->get();
        } else {
            // For admin users, only show items for their assigned pumps
            $pumps = Pump::whereHas('pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email);
            })->where('status', 'active')->get();

            $items = Item::where('name', '!=', 'Unassigned')
                        ->whereHas('activePumps', function($query) use ($pumps) {
                            $query->whereIn('pump_id', $pumps->pluck('id'));
                        })
                        ->with(['activePumps', 'nozzles.pump'])
                        ->latest()
                        ->get();
        }

        return view('items.index', compact('items', 'pumps'));
    }

    public function create()
    {
        $pumps = Pump::where('status', 'active')->get();
        return view('items.create', compact('pumps'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'pump_id' => 'required|exists:pumps,id',
            'nozzle_id' => 'required|integer|min:1',
            'status' => 'required|in:Active,Inactive',
            'item_type' => 'required|in:Litre,KG'
        ]);

        DB::transaction(function () use ($request) {
            // Create the item
            $item = Item::create([
                'name' => $request->name,
                'status' => $request->status,
                'item_type' => $request->item_type
            ]);

            // Create initial rate for the pump-item combination
            Rate::create([
                'pump_id' => $request->pump_id,
                'item_id' => $item->id,
                'rate' => 0, // Default rate
                'is_active' => true
            ]);

            // Create or update the nozzle
            Nozzle::updateOrCreate(
                [
                    'pump_id' => $request->pump_id,
                    'nozzle_number' => $request->nozzle_id
                ],
                [
                    'item_id' => $item->id,
                    'status' => 'active'
                ]
            );

            // Create initial stock entry with 0 quantity
            \App\Models\Stock::create([
                'item_id' => $item->id,
                'pump_id' => $request->pump_id,
                'quantity' => 0,
                'date' => now()->format('Y-m-d'),
                'created_by' => Auth::id()
            ]);
        });

        return redirect()->route('items.index')->with([
            'success' => 'Item created successfully',
            'action' => 'create'
        ]);
    }

    public function edit(Item $item)
    {
        $pumps = Pump::where('status', 'active')->get();
        $assignedPumps = $item->activePumps()->pluck('pump_id')->toArray();
        return view('items.edit', compact('item', 'pumps', 'assignedPumps'));
    }

    public function update(Request $request, Item $item)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'pump_id' => 'required|exists:pumps,id',
            'nozzle_id' => 'required|integer|min:1',
            'status' => 'required|in:Active,Inactive',
            'item_type' => 'required|in:Litre,KG'
        ]);

        DB::transaction(function () use ($request, $item) {
            // Update item details
            $item->update([
                'name' => $request->name,
                'status' => $request->status,
                'item_type' => $request->item_type
            ]);

            // Get or create a default "Unassigned" item for orphaned nozzles
            $unassignedItem = Item::firstOrCreate(
                ['name' => 'Unassigned'],
                ['status' => 'Inactive']
            );

            // First, find all current nozzles assigned to this item
            $currentNozzles = Nozzle::where('item_id', $item->id)->get();

            // Find the target nozzle (pump + nozzle_number combination)
            $targetNozzle = Nozzle::where('pump_id', $request->pump_id)
                                 ->where('nozzle_number', $request->nozzle_id)
                                 ->first();

            // Check if target nozzle exists and what it's currently assigned to
            if (!$targetNozzle) {
                // Target nozzle doesn't exist, create it
                $targetNozzle = Nozzle::create([
                    'pump_id' => $request->pump_id,
                    'nozzle_number' => $request->nozzle_id,
                    'item_id' => $item->id,
                    'status' => 'active'
                ]);
            } else {
                // Target nozzle exists, assign it to our item
                $targetNozzle->update([
                    'item_id' => $item->id,
                    'status' => 'active'
                ]);
            }

            // Now handle all other nozzles that were previously assigned to this item
            foreach ($currentNozzles as $nozzle) {
                if ($nozzle->id != $targetNozzle->id) {
                    // This nozzle was previously assigned to our item but is no longer needed
                    // Assign it to the "Unassigned" item and make it inactive
                    $nozzle->update([
                        'item_id' => $unassignedItem->id,
                        'status' => 'inactive'
                    ]);
                }
            }

            // Remove all existing rates for this item and recreate
            Rate::where('item_id', $item->id)->delete();

            // Create new rate for the pump-item combination
            Rate::create([
                'pump_id' => $request->pump_id,
                'item_id' => $item->id,
                'rate' => 0, // Default rate
                'is_active' => true
            ]);
        });

        return redirect()->route('items.index')->with([
            'success' => 'Item updated successfully',
            'action' => 'edit'
        ]);
    }

    public function destroy(Item $item)
    {
        $item->delete();
        return redirect()->route('items.index')->with([
            'success' => 'Item deleted successfully',
            'action' => 'delete'
        ]);
    }

    public function getItemsByPump($pumpId)
    {
        $items = Item::whereHas('rates', function ($query) use ($pumpId) {
            $query->where('pump_id', $pumpId)
                  ->where('is_active', true);
        })->with('activePumps')->get();

        return response()->json($items);
    }

    public function getAssignedPumps($itemId)
    {
        $item = Item::with('activePumps')->findOrFail($itemId);
        return response()->json($item->activePumps);
    }
}
