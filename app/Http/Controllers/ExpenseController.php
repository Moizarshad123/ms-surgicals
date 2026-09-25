<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::with('category')->latest('expense_date')->paginate(15);
        $totalExpenses = Expense::sum('amount');
        $thisMonthExpenses = Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');
        $todayExpenses = Expense::whereDate('expense_date', today())->sum('amount');
        $categoriesCount = ExpenseCategory::count();
        $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->get();

        return view('expenses.index', compact(
            'expenses',
            'totalExpenses',
            'thisMonthExpenses',
            'todayExpenses',
            'categoriesCount',
            'categories'
        ));
    }

    public function create()
    {
        $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->get();

        return view('expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => ['nullable', 'exists:expense_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        Expense::create($validated);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return back()->with('success', 'Expense deleted.');
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);

        ExpenseCategory::create($request->only('name'));

        return back()->with('success', 'Expense category added.');
    }
}
