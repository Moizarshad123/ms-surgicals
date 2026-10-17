<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BankTransactionController extends Controller
{
    /**
     * Display a listing of bank accounts and transactions with filters and balances.
     */
    public function index(Request $request)
    {
        $bankId = $request->get('bank_id');
        $type = $request->get('type');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $search = $request->get('search');

        $banks = Bank::with('transactions')->get();

        $query = BankTransaction::with(['bank', 'user']);

        if ($bankId && $bankId !== 'all') {
            $query->where('bank_id', $bankId);
        }

        if ($type && in_array($type, ['credit', 'debit'])) {
            $query->where('type', $type);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString(),
            ]);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('person_name', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest('transaction_date')
            ->latest('transaction_time')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // Calculate KPI Metrics
        $totalBankBalance = $banks->sum(fn($b) => $b->current_balance);

        $bankIslami = $banks->firstWhere('name', 'Bank Islami');
        $meezanBank = $banks->firstWhere('name', 'Meezan Bank');

        // Period filter totals
        $periodQuery = clone $query;
        $periodTransactions = $periodQuery->get();
        $totalPeriodCredit = $periodTransactions->where('type', 'credit')->sum('amount');
        $totalPeriodDebit  = $periodTransactions->where('type', 'debit')->sum('amount');

        return view('banks.index', compact(
            'banks',
            'transactions',
            'bankId',
            'type',
            'startDate',
            'endDate',
            'search',
            'totalBankBalance',
            'bankIslami',
            'meezanBank',
            'totalPeriodCredit',
            'totalPeriodDebit'
        ));
    }

    /**
     * Store a newly created bank transaction in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_id' => ['required', 'exists:banks,id'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['nullable', 'date_format:H:i'],
            'type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:1'],
            'person_name' => ['required', 'string', 'max:150'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['user_id'] = auth()->id();
        if (empty($validated['transaction_time'])) {
            $validated['transaction_time'] = now()->format('H:i');
        }

        BankTransaction::create($validated);

        return redirect()->route('banks.index')->with('success', 'Bank transaction recorded successfully.');
    }

    /**
     * Update an existing bank transaction in storage.
     */
    public function update(Request $request, BankTransaction $bankTransaction)
    {
        $validated = $request->validate([
            'bank_id' => ['required', 'exists:banks,id'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['nullable', 'date_format:H:i'],
            'type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:1'],
            'person_name' => ['required', 'string', 'max:150'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($validated['transaction_time'])) {
            $validated['transaction_time'] = now()->format('H:i');
        }

        $bankTransaction->update($validated);

        return redirect()->route('banks.index')->with('success', 'Bank transaction updated successfully.');
    }

    /**
     * Remove the specified bank transaction from storage.
     */
    public function destroy(BankTransaction $bankTransaction)
    {
        $bankTransaction->delete();

        return redirect()->route('banks.index')->with('success', 'Bank transaction deleted successfully.');
    }

    /**
     * Update bank account details (account number, title, opening balance, notes).
     */
    public function updateBank(Request $request, Bank $bank)
    {
        $validated = $request->validate([
            'account_title' => ['nullable', 'string', 'max:150'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:150'],
            'opening_balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $bank->update($validated);

        return redirect()->route('banks.index')->with('success', $bank->name . ' account details updated successfully.');
    }
}

