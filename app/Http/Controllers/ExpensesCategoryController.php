<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ExpensesCategory;
use App\Models\PartyList;

class ExpensesCategoryController extends Controller
{
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'pump_id' => 'nullable|exists:pumps,id'
            ]);

            DB::beginTransaction();

            // Get pump name for consistent naming
            $pumpName = null;
            if ($request->pump_id) {
                $pump = \App\Models\Pump::find($request->pump_id);
                $pumpName = $pump ? $pump->pump_name : null;
            }

            // Create consistent display name that includes pump info if available
            $categoryDisplayName = $pumpName
                ? $request->name . ' (' . $pumpName . ')'
                : $request->name;

            // Check if category with same name and pump already exists
            $existingCategory = ExpensesCategory::where('category_name', $categoryDisplayName)->first();
            if ($existingCategory) {
                DB::rollback();
                return redirect()->route('expenses.index')->with('error', 'A category with this name already exists for the selected pump.');
            }

            // Create expense category
            $category = ExpensesCategory::create([
                'category_name' => $categoryDisplayName,
                'pump_id' => $request->pump_id ?: null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Note: No longer creating party list entry for expense categories
            // Individual expenses will create their own party list entries

            DB::commit();

            return redirect()->route('expenses.index')->with([
                'success' => 'Category added successfully.',
                'action' => 'create'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating category: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return redirect()->route('expenses.index')->with('error', 'Error creating category: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $category = ExpensesCategory::findOrFail($id);

            // Note: No longer deleting party list entries for categories
            // Only individual expense party list entries will be deleted when expenses are deleted
            // The expenses table has a foreign key constraint that will handle related data cleanup

            // Delete the category (this will also delete related expenses due to foreign key constraint)
            $category->delete();

            DB::commit();

            return redirect()->route('expenses.index')->with([
                'success' => 'Category and related data deleted successfully.',
                'action' => 'delete'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('expenses.index')->with('error', 'Error deleting category: ' . $e->getMessage());
        }
    }
}
