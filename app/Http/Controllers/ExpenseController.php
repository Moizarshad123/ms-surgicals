<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::with(['category', 'bank'])->latest('expense_date')->paginate(15);
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
        $banks = Bank::where('status', 'active')->orderBy('name')->get();
        $cashInHand = $this->getCashInHand();

        return view('expenses.create', compact('categories', 'banks', 'cashInHand'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => ['nullable', 'exists:expense_categories,id'],
            'title'               => ['required', 'string', 'max:255'],
            'amount'              => ['required', 'numeric', 'min:0'],
            'expense_date'        => ['required', 'date'],
            'note'                => ['nullable', 'string'],
            'payment_method'      => ['required', 'string', 'in:cash,bank,easypaisa,jazzcash,cheque,other'],
            'bank_id'             => ['nullable', 'required_if:payment_method,bank', 'exists:banks,id'],
        ]);

        DB::transaction(function () use ($validated) {
            $expense = Expense::create($validated);

            // If paid via bank, create a debit bank transaction to deduct from bank balance
            if ($validated['payment_method'] === 'bank' && !empty($validated['bank_id'])) {
                BankTransaction::create([
                    'bank_id'             => $validated['bank_id'],
                    'transaction_date'    => $validated['expense_date'],
                    'transaction_time'    => now()->format('H:i'),
                    'type'                => 'debit',
                    'amount'              => $validated['amount'],
                    'person_name'         => 'Expense: ' . $validated['title'],
                    'reference_no'        => 'EXP-' . $expense->id,
                    'category'            => 'Expense',
                    'description'         => 'Expense: ' . $validated['title'] . ($validated['note'] ? ' — ' . $validated['note'] : ''),
                    'is_capital_transfer' => false,
                    'user_id'             => auth()->id(),
                ]);
            }
        });

        return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully.');
    }

    public function edit(Expense $expense)
    {
        $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->get();
        $banks = Bank::where('status', 'active')->orderBy('name')->get();
        $cashInHand = $this->getCashInHand();

        return view('expenses.edit', compact('expense', 'categories', 'banks', 'cashInHand'));
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'expense_category_id' => ['nullable', 'exists:expense_categories,id'],
            'title'               => ['required', 'string', 'max:255'],
            'amount'              => ['required', 'numeric', 'min:0'],
            'expense_date'        => ['required', 'date'],
            'note'                => ['nullable', 'string'],
            'payment_method'      => ['required', 'string', 'in:cash,bank,easypaisa,jazzcash,cheque,other'],
            'bank_id'             => ['nullable', 'required_if:payment_method,bank', 'exists:banks,id'],
        ]);

        DB::transaction(function () use ($expense, $validated) {
            $oldTitle = $expense->title;
            $oldBankId = $expense->bank_id;
            $expense->update($validated);

            // Find existing bank transaction linked to this expense
            $existingTx = BankTransaction::where('reference_no', 'EXP-' . $expense->id)
                ->orWhere(function ($q) use ($expense, $oldBankId, $oldTitle) {
                    if ($oldBankId) {
                        $q->where('bank_id', $oldBankId)
                          ->where('category', 'Expense')
                          ->where('description', 'like', 'Expense: ' . $oldTitle . '%');
                    }
                })->first();

            if ($validated['payment_method'] === 'bank' && !empty($validated['bank_id'])) {
                if ($existingTx) {
                    $existingTx->update([
                        'bank_id'          => $validated['bank_id'],
                        'transaction_date' => $validated['expense_date'],
                        'type'             => 'debit',
                        'amount'           => $validated['amount'],
                        'person_name'      => 'Expense: ' . $validated['title'],
                        'reference_no'     => 'EXP-' . $expense->id,
                        'category'         => 'Expense',
                        'description'      => 'Expense: ' . $validated['title'] . ($validated['note'] ? ' — ' . $validated['note'] : ''),
                        'user_id'          => auth()->id(),
                    ]);
                } else {
                    BankTransaction::create([
                        'bank_id'             => $validated['bank_id'],
                        'transaction_date'    => $validated['expense_date'],
                        'transaction_time'    => now()->format('H:i'),
                        'type'                => 'debit',
                        'amount'              => $validated['amount'],
                        'person_name'         => 'Expense: ' . $validated['title'],
                        'reference_no'        => 'EXP-' . $expense->id,
                        'category'            => 'Expense',
                        'description'         => 'Expense: ' . $validated['title'] . ($validated['note'] ? ' — ' . $validated['note'] : ''),
                        'is_capital_transfer' => false,
                        'user_id'             => auth()->id(),
                    ]);
                }
            } else {
                // If payment method changed away from bank, remove the debit bank transaction
                if ($existingTx) {
                    $existingTx->delete();
                }
            }
        });

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        DB::transaction(function () use ($expense) {
            BankTransaction::where('reference_no', 'EXP-' . $expense->id)
                ->orWhere(function ($q) use ($expense) {
                    if ($expense->payment_method === 'bank' && $expense->bank_id) {
                        $q->where('bank_id', $expense->bank_id)
                          ->where('category', 'Expense')
                          ->where('description', 'like', 'Expense: ' . $expense->title . '%');
                    }
                })->delete();

            $expense->delete();
        });

        return back()->with('success', 'Expense deleted.');
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);

        ExpenseCategory::create($request->only('name'));

        return back()->with('success', 'Expense category added.');
    }

    /**
     * Calculate current live cash in hand.
     */
    protected function getCashInHand(): float
    {
        $totalCashCapital = (float) \App\Models\BusinessCapital::sum('amount');
        $cashSalesInflow = (float) \App\Models\SalePayment::where('payment_method', 'cash')->sum('amount');
        $cashWithdrawalsFromBank = (float) BankTransaction::where('type', 'debit')
            ->where('is_capital_transfer', false)
            ->where(function ($q) {
                $q->where('category', 'like', '%cash withdrawal%')
                  ->orWhere('description', 'like', '%cash withdrawal%');
            })->sum('amount');

        $cashDepositsToBank = (float) BankTransaction::where('type', 'credit')
            ->where('is_capital_transfer', false)
            ->where(function ($q) {
                $q->where('category', 'like', '%cash deposit%')
                  ->orWhere('description', 'like', '%cash deposit%');
            })->sum('amount');

        $capitalTransferredToBank = (float) BankTransaction::where('is_capital_transfer', true)
            ->where('type', 'credit')
            ->sum('amount');

        $cashPurchases = (float) \App\Models\PurchasePayment::where('payment_method', 'cash')->sum('amount');
        $cashExpenses = (float) Expense::where(function ($q) {
            $q->where('payment_method', 'cash')->orWhereNull('payment_method');
        })->sum('amount');
        $cashRefunds = (float) \App\Models\SaleReturn::sum('refund_amount');

        $cashInHand = ($totalCashCapital + $cashSalesInflow + $cashWithdrawalsFromBank)
                    - ($cashPurchases + $cashExpenses + $cashDepositsToBank + $capitalTransferredToBank + $cashRefunds);

        return round($cashInHand, 2);
    }
}
