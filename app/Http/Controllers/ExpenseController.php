<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Expense;
use App\Models\ExpensesCategory;
use App\Models\Pump;
use App\Models\PartyList;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->user_type === 'super_admin') {
            $pumps = Pump::where('status', 'active')->get();
            $categories = ExpensesCategory::with(['expenses', 'pump'])->get();
        } else {
            // For admin users, only show their assigned pumps
            $pumps = Pump::whereHas('pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email);
            })->where('status', 'active')->get();

            // Get user's pump IDs
            $userPumpIds = DB::table('pump_users')
                ->where('email', $user->email)
                ->where('status', 'active')
                ->pluck('pump_id')
                ->toArray();

            // Filter categories based on user's pump access or categories they created
            $categories = ExpensesCategory::with(['expenses', 'pump'])
                ->where(function($query) use ($userPumpIds, $user) {
                    $query->whereIn('pump_id', $userPumpIds)
                          ->orWhere('created_by', $user->id)
                          ->orWhereNull('pump_id'); // Include categories not assigned to any pump
                })
                ->get();
        }

        return view('expenses.index', compact('categories', 'pumps'));
    }

    public function create(Request $request)
    {
        $category_id = $request->get('category_id');
        $category = null;
        $expenses = [];
        if ($category_id) {
            $category = ExpensesCategory::find($category_id);
            $expenses = Expense::where('category_id', $category_id)->with('category')->get();
        }
        return view('expenses.create', compact('category', 'expenses'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'date' => 'required|date',
                'description' => 'nullable|string|max:255',
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|string|max:50',
                'category_id' => 'required|exists:expenses_categories,id',
            ]);

            $expense = Expense::create([
                'name' => $request->name,
                'date' => $request->date,
                'description' => $request->description,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'category_id' => $request->category_id,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Also create a party list entry for this expense
            $category = ExpensesCategory::find($request->category_id);
            $partyListEntry = null;
            if ($category) {
                $partyListEntry = PartyList::create([
                    'group_type' => 'Expense', // Changed to match new naming
                    'ledger_section' => $request->name,
                    'expense_id' => $expense->id,
                    'status' => 'active',
                    'created_by' => Auth::id(),
                    'edited_by' => Auth::id()
                ]);
            }

            // Check if this is an AJAX request
            if ($request->expectsJson() || $request->ajax()) {
                // Load the expense relationship for the party list entry
                if ($partyListEntry) {
                    $partyListEntry->load(['expense', 'expense.category']);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Expense added successfully.',
                    'data' => $expense,
                    'party_list' => $partyListEntry, // Changed from party_list_entry to party_list for consistency
                    'expense_amount' => $expense->amount
                ]);
            }

            // For regular form submissions, redirect back to the same category's create page
            return redirect()->route('expenses.create', ['category_id' => $request->category_id])
                ->with([
                    'success' => 'Expense added successfully.',
                    'action' => 'create'
                ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            Log::error('Validation error creating expense: ' . json_encode($e->errors()));
            
            if ($request->expectsJson() || $request->ajax()) {
                $errorMessages = [];
                foreach ($e->errors() as $field => $messages) {
                    $errorMessages = array_merge($errorMessages, $messages);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $errorMessages),
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error creating expense: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating expense: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->withErrors(['error' => 'Error creating expense. Please try again.']);
        }
    }

    public function show($id)
    {
        $expense = Expense::with('category')->findOrFail($id);
        return view('expenses.show', compact('expense'));
    }

    public function edit($id)
    {
        $expense = Expense::findOrFail($id);
        $categories = ExpensesCategory::all();
        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric',
            'payment_method' => 'required|string|max:50',
            'category_id' => 'required|exists:expenses_categories,id',
        ]);

        $expense = Expense::findOrFail($id);
        $expense->update([
            'name' => $request->name,
            'date' => $request->date,
            'description' => $request->description,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'category_id' => $request->category_id,
            'updated_by' => Auth::id(),
        ]);

        // Update the corresponding PartyList entry
        $partyList = PartyList::where('expense_id', $expense->id)->first();
        if ($partyList) {
            $partyList->update([
                'ledger_section' => $request->name, // Just use expense name, not category name
                'edited_by' => Auth::id(),
            ]);
        }

        return redirect()->route('expenses.create', ['category_id' => $request->category_id])
            ->with([
                'success' => 'Expense updated successfully.',
                'action' => 'edit'
            ]);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $expense = Expense::with('category')->findOrFail($id);
            $categoryId = $expense->category_id;

            // Delete the expense - the corresponding party list entry will be automatically deleted by foreign key constraint
            $expense->delete();

            DB::commit();
            return redirect()->route('expenses.create', ['category_id' => $categoryId])
                ->with([
                    'success' => 'Expense deleted successfully. The associated party list entry has also been removed.',
                    'action' => 'delete'
                ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error deleting expense: ' . $e->getMessage());
            return redirect()->route('expenses.index')
                ->with('error', 'Error deleting expense: ' . $e->getMessage());
        }
    }
}
