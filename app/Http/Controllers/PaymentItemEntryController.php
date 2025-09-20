<?php

namespace App\Http\Controllers;

use App\Models\PaymentItemEntry;
use App\Models\Payment;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentItemEntryController extends Controller
{
    public function edit($id)
    {
        $entry = PaymentItemEntry::with('paymentItem')->findOrFail($id);
        $paymentItems = Payment::where('status', 1)
            ->select('id', 'item_name as name', 'item_desc')
            ->orderBy('item_name')
            ->get();

        return view('loans.show', compact('entry', 'paymentItems'));
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $entry = PaymentItemEntry::findOrFail($id);
            $loan = Loan::where('name', $entry->customer_name)->first();

            if (!$loan) {
                throw new \Exception('Loan not found');
            }

            // If it's a payment (negative amount)
            if ($entry->amount < 0) {
                $request->validate([
                    'amount' => 'required|numeric|min:0',
                    'payment_method' => 'required|string'
                ]);

                $paymentItem = Payment::where('item_name', $request->payment_method)->first();
                if (!$paymentItem) {
                    throw new \Exception('Payment method not found');
                }

                // Calculate the difference in payment amount
                $oldAmount = abs($entry->amount);
                $newAmount = $request->amount;
                $difference = $newAmount - $oldAmount;

                // Update the entry
                $entry->update([
                    'amount' => -$newAmount, // Keep it negative for payments
                    'payment_item_id' => $paymentItem->id
                ]);

                // Update the loan's total amount
                $loan->total_amount += $difference;
                $loan->save();
            } 
            // If it's a load (positive amount)
            else {
                $request->validate([
                    'litre' => 'required|numeric|min:0',
                    'rate' => 'required|numeric|min:0',
                    'driver_name' => 'nullable|string',
                    'vehicle_number' => 'nullable|string',
                    'vehicle_name' => 'nullable|string'
                ]);

                $newAmount = $request->litre * $request->rate;
                $oldAmount = $entry->amount;
                $difference = $newAmount - $oldAmount;

                // Update the entry
                $entry->update([
                    'litre' => $request->litre,
                    'rate' => $request->rate,
                    'amount' => $newAmount,
                    'driver_name' => $request->driver_name,
                    'vehicle_number' => $request->vehicle_number,
                    'vehicle_name' => $request->vehicle_name
                ]);

                // Update the loan's total amount
                $loan->total_amount += $difference;
                $loan->save();
            }

            DB::commit();
            return redirect()->back()->with('success', 'Entry updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating payment entry: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error updating entry: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $entry = PaymentItemEntry::findOrFail($id);
            $loan = Loan::where('name', $entry->customer_name)->first();

            if (!$loan) {
                throw new \Exception('Loan not found');
            }

            // If it's a payment (negative amount), add back to total amount
            if ($entry->amount < 0) {
                $loan->total_amount += abs($entry->amount);
            } 
            // If it's a load (positive amount), subtract from total amount
            else {
                $loan->total_amount -= $entry->amount;
            }

            $loan->save();
            $entry->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Entry deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting payment entry: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error deleting entry: ' . $e->getMessage());
        }
    }
} 