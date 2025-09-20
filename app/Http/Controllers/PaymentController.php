<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Pump; // Import Pump model
use App\Models\PartyList; // Import PartyList model
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->user_type === 'super_admin') {
            $payments = Payment::with('pump')->orderBy('created_on', 'asc')->get();
            $pumps = Pump::where('status', 'active')->get();
        } else {
            // For admin users, only show their assigned pumps
            $pumps = Pump::whereHas('pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email);
            })->where('status', 'active')->get();
            
            $payments = Payment::whereIn('petrol_pump', $pumps->pluck('id'))
                ->with('pump')
                ->orderBy('created_on', 'asc')
                ->get();
        }
        
        return view('payment.index', compact('payments', 'pumps'));
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'petrol_pump' => 'required|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            DB::beginTransaction();

            // Create the payment
            $payment = Payment::create([
                'item_name' => $request->item_name,
                'petrol_pump' => $request->petrol_pump,
                'status' => $request->status,
                'created_on' => now(),
                'created_by' => Auth::id(),
                'edited_by' => Auth::id()
            ]);

            // Get pump name for unique ledger section
            $pump = Pump::find($request->petrol_pump);
            $pumpName = $pump ? $pump->pump_name : 'Pump ' . $request->petrol_pump;

            // Also create a corresponding Party List entry with unique ledger section
            PartyList::create([
                'group_type' => 'Payment',
                'ledger_section' => $request->item_name . ' (' . $pumpName . ')', // Include pump name for uniqueness
                'status' => 'active',
                'created_by' => Auth::id(),
                'edited_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('payment.index')->with([
                'success' => 'Payment record added successfully and also added to Party List Management.',
                'action' => 'create'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('payment.index')->with('error', 'Error creating payment: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified payment.
     */
    public function edit($id)
    {
        $payment = Payment::findOrFail($id);
        return response()->json($payment);
    }

    /**
     * Update the specified payment in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'petrol_pump' => 'required|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            DB::beginTransaction();

            $payment = Payment::findOrFail($id);
            $oldItemName = $payment->item_name;
            $oldPumpId = $payment->petrol_pump;
            
            $payment->update([
                'item_name' => $request->item_name,
                'petrol_pump' => $request->petrol_pump,
                'status' => $request->status,
                'edited_on' => now(),
                'edited_by' => Auth::id()
            ]);

            // Get old and new pump names for updating Party List entry
            $oldPump = Pump::find($oldPumpId);
            $newPump = Pump::find($request->petrol_pump);
            $oldPumpName = $oldPump ? $oldPump->pump_name : 'Pump ' . $oldPumpId;
            $newPumpName = $newPump ? $newPump->pump_name : 'Pump ' . $request->petrol_pump;

            // Also update the corresponding Party List entry
            $partyList = PartyList::where('group_type', 'Payment')
                ->where('ledger_section', $oldItemName . ' (' . $oldPumpName . ')')
                ->first();
                
            if ($partyList) {
                $partyList->update([
                    'ledger_section' => $request->item_name . ' (' . $newPumpName . ')',
                    'edited_by' => Auth::id()
                ]);
            }

            DB::commit();

            return redirect()->route('payment.index')->with([
                'success' => 'Payment record updated successfully and also updated in Party List Management.',
                'action' => 'edit'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('payment.index')->with('error', 'Error updating payment: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified payment from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $payment = Payment::findOrFail($id);
            
            // Get pump name for finding the correct Party List entry
            $pump = Pump::find($payment->petrol_pump);
            $pumpName = $pump ? $pump->pump_name : 'Pump ' . $payment->petrol_pump;
            
            // Also delete the corresponding Party List entry
            $partyList = PartyList::where('group_type', 'Payment')
                ->where('ledger_section', $payment->item_name . ' (' . $pumpName . ')')
                ->first();
                
            if ($partyList) {
                $partyList->delete();
            }
            
            $payment->delete();
            
            DB::commit();
            
            return redirect()->route('payment.index')->with([
                'success' => 'Payment record deleted successfully and also removed from Party List Management.',
                'action' => 'delete'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('payment.index')->with('error', 'Error deleting payment: ' . $e->getMessage());
        }
    }
}
