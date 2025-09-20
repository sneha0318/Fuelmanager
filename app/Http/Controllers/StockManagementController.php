<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\Item;
use App\Models\Pump;
use App\Models\PaymentItemEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockManagementController extends Controller
{
    /**
     * Display a listing of the stock.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->user_type === 'super_admin') {
            // Super admin can see all stocks
            $stocks = Stock::with(['item', 'pump'])->get();
            $items = Item::all();
            $pumps = Pump::where('status', 'active')->get();
        } else {
            // For admin users, only show stocks for their assigned pumps
            $userPumpIds = DB::table('pump_users')
                ->where('email', $user->email)
                ->where('status', 'active')
                ->pluck('pump_id')
                ->toArray();

            $stocks = Stock::with(['item', 'pump'])
                ->whereIn('pump_id', $userPumpIds)
                ->get();

            $items = Item::whereHas('activePumps', function($query) use ($userPumpIds) {
                $query->whereIn('pump_id', $userPumpIds);
            })->get();

            $pumps = Pump::whereIn('id', $userPumpIds)
                ->where('status', 'active')
                ->get();
        }

        return view('stock-management.index', compact('stocks', 'items', 'pumps'));
    }

    /**
     * Show the form for creating a new stock.
     */
    public function create()
    {
        $user = Auth::user();

        if ($user->user_type === 'super_admin') {
            $items = Item::all();
            $pumps = Pump::where('status', 'active')->get();
        } else {
            // For admin users, only show items and pumps for their assigned pumps
            $userPumpIds = DB::table('pump_users')
                ->where('email', $user->email)
                ->where('status', 'active')
                ->pluck('pump_id')
                ->toArray();

            $items = Item::whereHas('activePumps', function($query) use ($userPumpIds) {
                $query->whereIn('pump_id', $userPumpIds);
            })->get();

            $pumps = Pump::whereIn('id', $userPumpIds)
                ->where('status', 'active')
                ->get();
        }

        return view('stock-management.create', compact('items', 'pumps'));
    }

    /**
     * Store a newly created stock in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'pump_id' => 'required|exists:pumps,id',
            'quantity' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        $user = Auth::user();

        // Check if user has permission to create stock for this pump
        if ($user->user_type !== 'super_admin') {
            $hasAccess = DB::table('pump_users')
                ->where('email', $user->email)
                ->where('pump_id', $request->pump_id)
                ->where('status', 'active')
                ->exists();

            if (!$hasAccess) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to manage stock for this pump.');
            }
        }

        // Check if stock exists for this item and pump on the same date
        $existingStock = Stock::where('item_id', $request->item_id)
            ->where('pump_id', $request->pump_id)
            ->whereDate('date', $request->date)
            ->first();

        // Check if we're updating existing stock (from show page or index page) or creating new
        $referer = request()->headers->get('referer');
        $isFromShowPage = $referer && (strpos($referer, 'stock-management/' . $existingStock?->id) !== false);
        $isFromIndexPage = $referer && strpos($referer, 'stock-management') !== false && strpos($referer, 'stock-management/') === false;

        if ($existingStock && ($isFromShowPage || $isFromIndexPage)) {
            // We're adding stock from the show page or index page, update the existing record
            $oldQuantity = $existingStock->quantity;
            $existingStock->quantity += $request->quantity;
            $existingStock->save();

            // Create stock history entry for the addition
            \App\Models\StockHistory::create([
                'stock_id' => $existingStock->id,
                'previous_quantity' => $oldQuantity,
                'added_quantity' => $request->quantity,
                'new_quantity' => $existingStock->quantity,
                'date' => now(),
                'created_by' => Auth::id()
            ]);
            
            return redirect()->back()
                ->with([
                    'success' => 'Stock updated successfully. New quantity: ' . $existingStock->quantity,
                    'action' => 'edit'
                ]);
        } elseif ($existingStock) {
            return redirect()->back()->with('error', 'Stock for this item and pump already exists for this date. Please edit the existing entry.');
        }

        // Create new stock entry
        $stock = Stock::create($request->all());

        // Create initial stock history entry
        \App\Models\StockHistory::create([
            'stock_id' => $stock->id,
            'previous_quantity' => 0,
            'added_quantity' => $request->quantity,
            'new_quantity' => $request->quantity,
            'date' => now(),
            'created_by' => Auth::id()
        ]);

        return redirect()->back()
            ->with([
                'success' => 'New stock added successfully.',
                'action' => 'create'
            ]);
    }

    /**
     * Show the form for editing the specified stock.
     */
    public function edit(Stock $stock)
    {
        $user = Auth::user();

        // Check if user has permission to edit this stock
        if ($user->user_type !== 'super_admin') {
            $hasAccess = DB::table('pump_users')
                ->where('email', $user->email)
                ->where('pump_id', $stock->pump_id)
                ->where('status', 'active')
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('stock-management.index')
                    ->with('error', 'You do not have permission to edit this stock.');
            }
        }

        if ($user->user_type === 'super_admin') {
            $items = Item::all();
            $pumps = Pump::where('status', 'active')->get();
        } else {
            // For admin users, only show items and pumps for their assigned pumps
            $userPumpIds = DB::table('pump_users')
                ->where('email', $user->email)
                ->where('status', 'active')
                ->pluck('pump_id')
                ->toArray();

            $items = Item::whereHas('activePumps', function($query) use ($userPumpIds) {
                $query->whereIn('pump_id', $userPumpIds);
            })->get();

            $pumps = Pump::whereIn('id', $userPumpIds)
                ->where('status', 'active')
                ->get();
        }

        return view('stock-management.edit', compact('stock', 'items', 'pumps'));
    }

    /**
     * Update the specified stock in storage.
     */
    public function update(Request $request, Stock $stock)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'pump_id' => 'required|exists:pumps,id',
            'quantity' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        $user = Auth::user();

        // Check if user has permission to update this stock
        if ($user->user_type !== 'super_admin') {
            $hasAccess = DB::table('pump_users')
                ->where('email', $user->email)
                ->where('pump_id', $stock->pump_id)
                ->where('status', 'active')
                ->exists();

            if (!$hasAccess) {
                if ($request->ajax()) {
                    return response()->json(['error' => 'You do not have permission to update this stock.'], 403);
                }
                return redirect()->route('stock-management.index')
                    ->with('error', 'You do not have permission to update this stock.');
            }

            // Also check if user has access to the new pump_id if it's being changed
            if ($request->pump_id != $stock->pump_id) {
                $hasNewPumpAccess = DB::table('pump_users')
                    ->where('email', $user->email)
                    ->where('pump_id', $request->pump_id)
                    ->where('status', 'active')
                    ->exists();

                if (!$hasNewPumpAccess) {
                    if ($request->ajax()) {
                        return response()->json(['error' => 'You do not have permission to move stock to this pump.'], 403);
                    }
                    return redirect()->route('stock-management.index')
                        ->with('error', 'You do not have permission to move stock to this pump.');
                }
            }
        }

        // Prevent updating to a duplicate entry for the same day, pump, and item
        $duplicateStock = Stock::where('item_id', $request->item_id)
            ->where('pump_id', $request->pump_id)
            ->whereDate('date', $request->date)
            ->where('id', '!=', $stock->id)
            ->exists();
        if ($duplicateStock) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Stock for this item and pump already exists for this date. Please edit the existing entry.'], 422);
            }
            return redirect()->route('stock-management.index')
                ->with('error', 'Stock for this item and pump already exists for this date. Please edit the existing entry.');
        }

        $data = $request->all();
        $data['updated_by'] = Auth::id();
        $stock->update($data);

        if ($request->ajax()) {
            return response()->json(['success' => 'Stock updated successfully.']);
        }

        return redirect()->route('stock-management.index')
            ->with([
                'success' => 'Stock updated successfully.',
                'action' => 'edit'
            ]);
    }

    /**
     * Remove the specified stock from storage.
     */
    public function destroy(Stock $stock)
    {
        $user = Auth::user();

        // Check if user has permission to delete this stock
        if ($user->user_type !== 'super_admin') {
            $hasAccess = DB::table('pump_users')
                ->where('email', $user->email)
                ->where('pump_id', $stock->pump_id)
                ->where('status', 'active')
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('stock-management.index')
                    ->with('error', 'You do not have permission to delete this stock.');
            }
        }

        $stock->delete();

        return redirect()->route('stock-management.index')
            ->with([
                'success' => 'Stock deleted successfully.',
                'action' => 'delete'
            ]);
    }

    public function show($id)
    {
        $user = Auth::user();
        $stock = Stock::with(['item', 'pump'])->findOrFail($id);

        // Check if user has permission to view this stock
        if ($user->user_type !== 'super_admin') {
            $hasAccess = DB::table('pump_users')
                ->where('email', $user->email)
                ->where('pump_id', $stock->pump_id)
                ->where('status', 'active')
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('stock-management.index')
                    ->with('error', 'You do not have permission to view this stock.');
            }
        }

        // Get date range from request
        $startDate = request('start_date');
        $endDate = request('end_date');

        // Get all sales for this item/pump combination
        $allSalesQuery = PaymentItemEntry::whereHas('nozzle', function($query) use ($stock) {
            $query->where('item_id', $stock->item_id)
                  ->where('pump_id', $stock->pump_id)
                  ->where('status', 'active'); // Only active nozzles
        });

        // Apply date range filter if provided
        if ($startDate && $endDate) {
            $allSalesQuery->whereBetween('created_at', [
                \Carbon\Carbon::parse($startDate)->startOfDay(),
                \Carbon\Carbon::parse($endDate)->endOfDay()
            ]);
        } else {
            // If no date range specified, get sales from this stock's creation date onwards
            $allSalesQuery->where('created_at', '>=', $stock->created_at);
        }

        // Get all relevant sales
        $allSales = $allSalesQuery->where('status', '!=', 'cancelled') // Exclude cancelled sales
                                 ->where('amount', '>', 0) // Only positive amounts
                                 ->where('litre', '>', 0) // Only positive quantities
                                 ->with(['paymentItem', 'vehicle', 'nozzle'])
                                 ->orderBy('created_at', 'desc')
                                 ->get();

        // Now filter sales to only include those that should be attributed to this specific stock
        // This is a more sophisticated approach to ensure we only count sales from the current stock period
        $stockStartDate = $stock->created_at;
        
        // Find the next stock entry for the same item/pump (if any) to limit our date range
        $nextStock = Stock::where('item_id', $stock->item_id)
                         ->where('pump_id', $stock->pump_id)
                         ->where('created_at', '>', $stockStartDate)
                         ->orderBy('created_at', 'asc')
                         ->first();

        // Filter sales to only those within this stock's period
        $sales = $allSales->filter(function ($sale) use ($stockStartDate, $nextStock, $startDate, $endDate) {
            $saleDate = $sale->created_at;
            
            // Sale must be after this stock was created
            if ($saleDate < $stockStartDate) {
                return false;
            }
            
            // If there's a next stock, sale must be before that stock was created
            if ($nextStock && $saleDate >= $nextStock->created_at) {
                return false;
            }
            
            // If date range is specified, sale must be within that range
            if ($startDate && $endDate) {
                $filterStart = \Carbon\Carbon::parse($startDate)->startOfDay();
                $filterEnd = \Carbon\Carbon::parse($endDate)->endOfDay();
                if ($saleDate < $filterStart || $saleDate > $filterEnd) {
                    return false;
                }
            }
            
            return true;
        });

        // Get stock history with date range filter
        $stockHistory = \App\Models\StockHistory::where('stock_id', $stock->id)
            ->when($startDate && $endDate, function($query) use ($startDate, $endDate) {
                return $query->whereBetween('date', [
                    \Carbon\Carbon::parse($startDate)->startOfDay(),
                    \Carbon\Carbon::parse($endDate)->endOfDay()
                ]);
            })
            ->with('creator')
            ->orderBy('date', 'desc')
            ->get();

        // Calculate total stock added (current stock quantity already includes all additions)
        $totalStockAdded = $stock->quantity;
        
        // Calculate remaining quantity more accurately
        $totalSold = $sales->sum('litre');
        $remainingQuantity = $totalStockAdded - $totalSold;

        return view('stock-management.show', compact('stock', 'sales', 'stockHistory', 'totalStockAdded', 'remainingQuantity'));
    }
}
