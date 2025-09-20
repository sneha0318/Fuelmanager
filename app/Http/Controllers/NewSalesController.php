<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Pump;
use App\Models\Nozzle;
use App\Models\Stock;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentItemEntry;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PartyList;
use App\Models\NewSale;
use App\Models\Item;

class NewSalesController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->user_type === 'super_admin') {
            $sales = Sale::with(['pump', 'nozzle'])->latest()->get();
            $pumps = Pump::where('status', 'active')->get();
            $nozzles = Nozzle::with('item')->where('status', 'active')->get();
        } else {
            // For operators and admin users, show all sales but handle permissions differently
            $sales = Sale::with(['pump', 'nozzle'])->latest()->get();

            // Get pumps based on user type
            if ($user->user_type === 'admin') {
                $pumps = Pump::whereHas('pumpUsers', function($query) use ($user) {
                    $query->where('email', $user->email);
                })->where('status', 'active')->get();
            } else {
                // For operators, get pumps from their active shifts
                $pumps = Pump::whereHas('shifts', function($query) use ($user) {
                    $query->whereHas('pumpUser', function($q) use ($user) {
                        $q->where('email', $user->email);
                    })
                    ->where('is_active', true)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now());
                })->where('status', 'active')->get();
            }

            $nozzles = Nozzle::whereIn('pump_id', $pumps->pluck('id'))
                ->with('item')
                ->where('status', 'active')
                ->get();
        }

        $activeShifts = Shift::with(['pump', 'nozzle', 'pumpUser'])
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->get();

        return view('sales.index', compact('sales', 'pumps', 'nozzles', 'activeShifts'));
    }

    /**
     * Check if stock exists for a pump and item combination
     */
    private function hasStockForPumpAndItem($pumpId, $itemId)
    {
        return Stock::where('pump_id', $pumpId)
                   ->where('item_id', $itemId)
                   ->where('quantity', '>', 0)
                   ->exists();
    }    /**
     * Get available stock quantity for a pump and item
     */
    private function getAvailableStock($pumpId, $itemId, $date = null)
    {
        // Get total stock for this pump and item (all time)
        $totalStock = Stock::where('pump_id', $pumpId)
                           ->where('item_id', $itemId)
                           ->sum('quantity');

        // Get total sold quantity from completed sales (all time)
        $soldQuantity = Sale::where('pump_id', $pumpId)
                         ->whereHas('nozzle', function($query) use ($itemId) {
                             $query->where('item_id', $itemId);
                         })
                         ->where('status', 'Completed')
                         ->whereNotNull('net_sale_litre')
                         ->sum('net_sale_litre');

        // Get total variation (all time)
        $variation = Sale::where('pump_id', $pumpId)
                          ->whereHas('nozzle', function($query) use ($itemId) {
                              $query->where('item_id', $itemId);
                          })
                          ->where('status', 'Completed')
                          ->sum('variation');

        // Available = Total Stock - Total Sold - abs(Total Variation)
        return $totalStock - $soldQuantity - abs($variation);
    }

    /**
     * Get detailed stock information for a pump and item
     */
    private function getDetailedStockInfo($pumpId, $itemId)
    {
        // Get total stock for this pump and item
        $totalStock = Stock::where('pump_id', $pumpId)
                          ->where('item_id', $itemId)
                          ->sum('quantity');

        // Get total sold quantity from completed sales
        $soldQuantity = Sale::where('pump_id', $pumpId)
                           ->whereHas('nozzle', function($query) use ($itemId) {
                               $query->where('item_id', $itemId);
                           })
                           ->where('status', 'Completed')
                           ->whereNotNull('net_sale_litre')
                           ->sum('net_sale_litre');

        $availableStock = $totalStock - $soldQuantity;

        return [
            'total_stock' => $totalStock,
            'sold_quantity' => $soldQuantity,
            'available_stock' => $availableStock
        ];
    }

    /**
     * Validate stock availability for opening meter
     */
    private function validateStockForOpeningMeter($pumpId, $itemId, $openingMeter)
    {
        $availableStock = $this->getAvailableStock($pumpId, $itemId);

        if ($openingMeter > $availableStock) {
            return [
                'valid' => false,
                'message' => "Opening meter ({$openingMeter} litres) cannot exceed available stock ({$availableStock} litres) for this pump and fuel type."
            ];
        }

        return ['valid' => true];
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_date' => 'required|date',
            'sale_time' => 'required',
            'pump_id' => 'required|exists:pumps,id',
            'nozzle' => 'required|exists:nozzles,id',
            'open_meter' => 'required|numeric|min:0',
            'test_litre' => 'nullable|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'status' => 'required|in:Pending,Completed'
        ]);

        $user = Auth::user();

        // Check if user has permission to create sale for this pump
        if ($user->user_type === 'operator') {
            $hasPermission = Shift::where('pump_id', $request->pump_id)
                ->whereHas('pumpUser', function($query) use ($user) {
                    $query->where('email', $user->email);
                })
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->exists();

            if (!$hasPermission) {
                return redirect()->back()->with('error', 'You do not have permission to create sales for this pump.');
            }
        }

        // Get nozzle to check the associated item
        $nozzle = Nozzle::with('item')->findOrFail($request->nozzle);

        // Stock validation: Check if stock exists for this pump and item combination
        if (!$this->hasStockForPumpAndItem($request->pump_id, $nozzle->item_id)) {
            return redirect()->back()->with('error', 'No stock available for this pump and fuel type. Please add stock before creating a sale.');
        }

        // Stock validation: Check if opening meter doesn't exceed available stock
        $stockValidation = $this->validateStockForOpeningMeter($request->pump_id, $nozzle->item_id, $request->open_meter);
        if (!$stockValidation['valid']) {
            return redirect()->back()->with('error', $stockValidation['message']);
        }

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Create new sale
            $sale = new Sale();
            $sale->sale_date = $request->sale_date;
            $sale->sale_time = $request->sale_time;
            $sale->pump_id = $request->pump_id;
            $sale->nozzle_id = $request->nozzle;
            $sale->open_meter = $request->open_meter;
            $sale->close_meter = null; // Set close_meter to null initially
            $sale->test_litre = $request->test_litre ?? 0; // Set to 0 if null
            $sale->rate = $request->rate;
            $sale->net_sale_litre = null; // Set net_sale_litre to null initially
            $sale->status = $request->status;
            $sale->save();

            // Create attendance record
            $activeShift = Shift::where('pump_id', $request->pump_id)
                ->whereHas('pumpUser', function($query) use ($user) {
                    $query->where('email', $user->email);
                })
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();

            if ($activeShift) {
                // Check if attendance record already exists
                $existingAttendance = Attendance::where([
                    'operator_id' => $user->id,
                    'shift_id' => $activeShift->id,
                    'date' => $request->sale_date,
                ])->first();

                if (!$existingAttendance) {
                    // Create new attendance record
                    Attendance::create([
                        'operator_id' => $user->id,
                        'shift_id' => $activeShift->id,
                        'date' => $request->sale_date,
                        'opening_meter_time' => $request->sale_date . ' ' . $request->sale_time,
                        'created_by' => $user->id
                    ]);
                }
            }

            // If the sale is completed, create a payment item entry
            if ($request->status === 'Completed') {
                // Get the nozzle
                $nozzle = Nozzle::findOrFail($request->nozzle);

                // Create payment item entry
                PaymentItemEntry::create([
                    'nozzle_id' => $nozzle->id,
                    'payment_item_id' => 1, // Assuming 1 is the ID for regular sales
                    'bill_no' => 'SALE-' . $sale->id,
                    'litre' => $request->test_litre ?? 0,
                    'rate' => $request->rate,
                    'amount' => ($request->test_litre ?? 0) * $request->rate,
                    'status' => 'completed'
                ]);
            }

            // Commit the transaction
            DB::commit();

            return redirect()->route('new-sales.index')->with('success', 'Sale created successfully.');
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();

            // Log the error
            Log::error('Sale Creation Error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to create sale: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        // Start a database transaction
        DB::beginTransaction();

        try {
            // Find the sale
            $sale = Sale::findOrFail($id);
            $user = Auth::user();

            // Check if user has permission to delete this sale
            if ($user->user_type === 'operator') {
                $hasPermission = Shift::where('pump_id', $sale->pump_id)
                    ->whereHas('pumpUser', function($query) use ($user) {
                        $query->where('email', $user->email);
                    })
                    ->where('is_active', true)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->exists();

                if (!$hasPermission) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to delete this sale.'
                    ], 403);
                }
            }

            // Get the nozzle ID and other details before deleting the sale
            $nozzleId = $sale->nozzle_id;
            $saleDate = $sale->sale_date;
            $saleTime = $sale->sale_time;

            // Delete all payment items associated with this nozzle for this sale
            PaymentItemEntry::where('nozzle_id', $nozzleId)
                ->where('bill_no', 'SALE-' . $id)
                ->delete();

            // Delete any other payment items for this nozzle on the same date and time
            PaymentItemEntry::where('nozzle_id', $nozzleId)
                ->whereDate('created_at', $saleDate)
                ->whereTime('created_at', $saleTime)
                ->delete();

            // Delete the sale
            $sale->delete();

            // Clean up any orphaned payment items
            PaymentItemEntry::where('nozzle_id', $nozzleId)
                ->where('bill_no', 'LIKE', 'SALE-%')
                ->whereNotIn('bill_no', function($query) {
                    $query->select(DB::raw("CONCAT('SALE-', id)"))
                        ->from('sales');
                })
                ->delete();

            // Commit the transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale deleted successfully.',
                'sale' => $sale
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();

            // Log the error
            Log::error('Sale Deletion Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sale: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Find the sale first to check its status and permissions
            $sale = Sale::with(['pump', 'nozzle.item'])->findOrFail($id);
            $user = Auth::user();

            // Check if user has permission to edit this sale
            if ($user->user_type === 'operator') {
                // For operators, check if they have an active shift for this pump
                $hasPermission = Shift::where('pump_id', $sale->pump_id)
                    ->whereHas('pumpUser', function($query) use ($user) {
                        $query->where('email', $user->email);
                    })
                    ->where('is_active', true)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->exists();

                if (!$hasPermission) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to edit this sale.'
                    ], 403);
                }
            }

            // Prevent editing completed sales
            if ($sale->status === 'Completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit a completed sale.'
                ], 403);
            }

            $request->validate([
                'sale_date' => 'required|date',
                'sale_time' => 'required',
                'pump_id' => 'required|exists:pumps,id',
                'nozzle' => 'required|exists:nozzles,id',
                'open_meter' => 'required|numeric|min:0',
                'close_meter' => 'nullable|numeric|min:0',
                'test_litre' => 'nullable|numeric|min:0',
                'rate' => 'required|numeric|min:0',
                'status' => 'required|in:Pending,Completed',
                'close_time' => 'nullable|date_format:H:i'
            ]);

            // Start a database transaction
            DB::beginTransaction();            // Calculate net sale litres if close_meter is provided
            $netSaleLitre = null;
            if ($request->close_meter) {
                $netSaleLitre = $request->close_meter - $request->open_meter;
            }

            $closeTime = $request->close_time ? date('H:i:s', strtotime($request->close_time)) : now()->format('H:i:s');
            $closeDate = now()->toDateString();

            // Update sale
            $sale->update([
                'sale_date' => $request->sale_date,
                'sale_time' => $request->sale_time,
                'pump_id' => $request->pump_id,
                'nozzle_id' => $request->nozzle,
                'open_meter' => $request->open_meter,
                'close_meter' => $request->close_meter,
                'test_litre' => $request->test_litre ?? 0,
                'rate' => $request->rate,
                'net_sale_litre' => $netSaleLitre,
                'status' => $request->status,
                'close_time' => $closeTime,
                'close_date' => $closeDate
            ]);

            // If the sale is completed, update the attendance record
            if ($request->status === 'Completed') {
                // Get the active shift for this pump and operator
                $activeShift = Shift::where('pump_id', $request->pump_id)
                    ->whereHas('pumpUser', function($query) use ($user) {
                        $query->where('email', $user->email);
                    })
                    ->where('is_active', true)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->first();

                if ($activeShift) {
                    // Update or create attendance record
                    \App\Models\Attendance::updateOrCreate(
                        [
                        'date' => $request->sale_date,
                            'operator_id' => $user->id,
                            'shift_id' => $activeShift->id,
                        ],
                        [
                            'closing_meter_time' => $closeDate . ' ' . $closeTime,
                            'edited_by' => $user->id
                        ]
                    );
                }
            }

            // Handle payment items
            if ($request->status === 'Completed') {
                // Get the nozzle
                $nozzle = Nozzle::findOrFail($request->nozzle);

                // Check if a payment item entry already exists for this sale
                $existingEntry = PaymentItemEntry::where('bill_no', 'SALE-' . $sale->id)->first();

                if ($existingEntry) {
                    // Update existing payment item entry
                    $existingEntry->update([
                        'litre' => $request->test_litre ?? 0,
                        'rate' => $request->rate,
                        'amount' => ($request->test_litre ?? 0) * $request->rate,
                        'status' => 'completed'
                    ]);
                } else {
                    // Create new payment item entry
                    PaymentItemEntry::create([
                        'nozzle_id' => $nozzle->id,
                        'payment_item_id' => 1, // Assuming 1 is the ID for regular sales
                        'bill_no' => 'SALE-' . $sale->id,
                        'litre' => $request->test_litre ?? 0,
                        'rate' => $request->rate,
                        'amount' => ($request->test_litre ?? 0) * $request->rate,
                        'status' => 'completed'
                    ]);
                }
            } else {
                // If status is changed to Pending, delete any existing payment item entry
                PaymentItemEntry::where('bill_no', 'SALE-' . $sale->id)->delete();
            }

            // Commit the transaction
            DB::commit();

            // Reload the sale with relationships
            $updatedSale = Sale::with(['pump', 'nozzle.item'])->find($id);

            return response()->json([
                'success' => true,
                'message' => 'Sale updated successfully.',
                'sale' => $updatedSale
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();

            // Log the error
            Log::error('Sale Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update sale: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getNozzlesByPump($pumpId)
    {
        try {
            // Get the operator email from the request
            $operatorEmail = request()->query('operator_email');

            // Get all active shifts for the pump
            $activeShifts = Shift::where('pump_id', $pumpId)
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->with('pumpUser')
                ->get();

            // If operator email is provided, filter shifts by operator
            if ($operatorEmail) {
                $activeShifts = $activeShifts->filter(function($shift) use ($operatorEmail) {
                    return $shift->pumpUser && $shift->pumpUser->email === $operatorEmail;
                });
            }

            // Get all nozzles for the selected pump that are assigned to any active shift
            $nozzles = Nozzle::with(['item', 'pump'])
                ->where('pump_id', $pumpId)
                ->where('status', 'active')
                ->whereIn('id', $activeShifts->pluck('nozzle_id'))
                ->get()
                ->map(function ($nozzle) use ($activeShifts) {
                    // Get all shifts for this nozzle
                    $nozzleShifts = $activeShifts->where('nozzle_id', $nozzle->id);
                    $shiftText = $nozzleShifts->map(function($shift) {
                        return $shift->pumpUser->user_name;
                    })->join(', ');

                    return [
                        'id' => $nozzle->id,
                        'nozzle_number' => $nozzle->nozzle_number,
                        'item_id' => $nozzle->item_id,
                        'item_name' => $nozzle->item->name ?? 'N/A',
                        'item_type' => $nozzle->item->item_type ?? 'Litre',
                        'display_text' => "Nozzle #{$nozzle->nozzle_number} - {$nozzle->item->name}",
                        'shift_info' => $nozzleShifts->map(function($shift) {
                            return [
                                'shift_name' => $shift->shift_name,
                                'operator_name' => $shift->pumpUser->user_name ?? 'N/A',
                                'operator_email' => $shift->pumpUser->email ?? 'N/A'
                            ];
                        }),
                        'shift_ids' => $nozzleShifts->pluck('id')->toArray()
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

    public function checkStockAvailability(Request $request)
    {
        $request->validate([
            'pump_id' => 'required|exists:pumps,id',
            'nozzle_id' => 'required|exists:nozzles,id',
            'opening_meter' => 'nullable|numeric|min:0',
        ]);

        try {
            $nozzle = Nozzle::with('item')->findOrFail($request->nozzle_id);
            $date = $request->input('date') ?? now()->format('Y-m-d');
            // Check if stock exists for this pump and item combination
            $hasStock = $this->hasStockForPumpAndItem($request->pump_id, $nozzle->item_id);

            if (!$hasStock) {
                return response()->json([
                    'success' => false,
                    'message' => 'No stock available for this pump and fuel type.',
                    'has_stock' => false,
                    'available_stock' => 0
                ]);
            }

            // Get available stock (pass date)
            $availableStock = $this->getAvailableStock($request->pump_id, $nozzle->item_id, $date);

            $response = [
                'success' => true,
                'has_stock' => true,
                'available_stock' => $availableStock,
                'fuel_type' => $nozzle->item->name,
                'message' => "Available stock: {$availableStock} litres of {$nozzle->item->name}"
            ];

            // If opening meter is provided, validate it against available stock
            if ($request->opening_meter !== null) {
                $stockValidation = $this->validateStockForOpeningMeter($request->pump_id, $nozzle->item_id, $request->opening_meter);

                if (!$stockValidation['valid']) {
                    $response['success'] = false;
                    $response['message'] = $stockValidation['message'];
                    $response['opening_meter_valid'] = false;
                } else {
                    $response['opening_meter_valid'] = true;
                }
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Stock Availability Check Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check stock availability: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkStock(Request $request)
    {
        $request->validate([
            'nozzle_id' => 'required|exists:nozzles,id',
            'litres' => 'required|numeric|min:0',
        ]);

        try {
            $nozzle = Nozzle::with('item')->findOrFail($request->nozzle_id);

            // Calculate the total stock available for the item linked to the nozzle
            $totalStock = Stock::where('item_id', $nozzle->item_id)
                ->where('status', 'active')
                ->sum('quantity');

            // Calculate the total litres already allocated to sales for this nozzle
            $allocatedLitres = Sale::where('nozzle_id', $request->nozzle_id)
                ->where('status', 'Completed')
                ->sum('test_litre');

            // Calculate available stock
            $availableStock = $totalStock - $allocatedLitres;

            return response()->json([
                'success' => true,
                'available_stock' => $availableStock,
                'message' => 'Stock checked successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Stock Check Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check stock: ' . $e->getMessage()
            ], 500);
        }
    }
}
