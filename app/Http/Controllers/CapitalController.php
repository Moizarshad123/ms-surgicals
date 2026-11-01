<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\BusinessCapital;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CapitalController extends Controller
{
    /**
     * Display listing of initial capital and capital entries.
     */
    public function index()
    {
        $capitals = BusinessCapital::with('user')->latest('capital_date')->latest('id')->paginate(15);
        $totalCapital = (float) BusinessCapital::sum('amount');
        $initialCapitalCount = BusinessCapital::count();

        // Calculate current live cash in hand
        $totalCashCapital      = (float) BusinessCapital::sum('amount');
        $cashSalesInflow       = (float) SalePayment::where('payment_method', 'cash')->sum('amount');

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

        // Capital transferred to bank (tagged with is_capital_transfer = true)
        $capitalTransferredToBank = (float) BankTransaction::where('is_capital_transfer', true)
            ->where('type', 'credit')
            ->sum('amount');

        $cashPurchases = (float) PurchasePayment::where('payment_method', 'cash')->sum('amount');
        $cashExpenses  = (float) Expense::where(function ($q) {
            $q->where('payment_method', 'cash')->orWhereNull('payment_method');
        })->sum('amount');
        $cashRefunds   = (float) SaleReturn::sum('refund_amount');

        $cashInHand = ($totalCashCapital + $cashSalesInflow + $cashWithdrawalsFromBank)
                    - ($cashPurchases + $cashExpenses + $cashDepositsToBank + $capitalTransferredToBank + $cashRefunds);
        $cashInHand = round($cashInHand, 2);

        // Available banks for the transfer modal
        $banks = Bank::where('status', 'active')->orderBy('name')->get();

        return view('capital.index', compact(
            'capitals',
            'totalCapital',
            'totalCashCapital',
            'initialCapitalCount',
            'cashInHand',
            'cashSalesInflow',
            'cashPurchases',
            'cashExpenses',
            'cashDepositsToBank',
            'capitalTransferredToBank',
            'banks'
        ));
    }

    /**
     * Store a newly created capital entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'capital_date' => ['required', 'date'],
            'type'         => ['required', 'in:initial_cash,additional_investment'],
            'source'       => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string', 'max:500'],
        ]);

        $validated['user_id'] = auth()->id();

        BusinessCapital::create($validated);

        return redirect()->route('capital.index')->with('success', 'Capital amount of Rs. ' . number_format($validated['amount'], 2) . ' successfully recorded.');
    }

    /**
     * Update the specified capital entry.
     */
    public function update(Request $request, BusinessCapital $capital)
    {
        $validated = $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'capital_date' => ['required', 'date'],
            'type'         => ['required', 'in:initial_cash,additional_investment'],
            'source'       => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string', 'max:500'],
        ]);

        $capital->update($validated);

        return redirect()->route('capital.index')->with('success', 'Capital record updated successfully.');
    }

    /**
     * Remove the specified capital entry.
     */
    public function destroy(BusinessCapital $capital)
    {
        $capital->delete();

        return redirect()->route('capital.index')->with('success', 'Capital record deleted successfully.');
    }

    /**
     * Transfer capital cash into a bank account.
     * Creates a tagged BankTransaction (is_capital_transfer = true) so it is
     * excluded from income calculations and deducted from cash-in-hand.
     */
    public function transferToBank(Request $request)
    {
        $validated = $request->validate([
            'bank_id'       => ['required', 'exists:banks,id'],
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'transfer_date' => ['required', 'date'],
            'description'   => ['nullable', 'string', 'max:500'],
        ]);

        // Check that we have enough cash in hand
        $totalCashCapital      = (float) BusinessCapital::sum('amount');
        $cashSalesInflow       = (float) SalePayment::where('payment_method', 'cash')->sum('amount');
        $cashWithdrawalsFromBank = (float) BankTransaction::where('type', 'debit')
            ->where('is_capital_transfer', false)
            ->where(function ($q) {
                $q->where('category', 'like', '%cash withdrawal%')
                  ->orWhere('description', 'like', '%cash withdrawal%');
            })->sum('amount');
        $cashDepositsToBank    = (float) BankTransaction::where('type', 'credit')
            ->where('is_capital_transfer', false)
            ->where(function ($q) {
                $q->where('category', 'like', '%cash deposit%')
                  ->orWhere('description', 'like', '%cash deposit%');
            })->sum('amount');
        $cashPurchases         = (float) PurchasePayment::where('payment_method', 'cash')->sum('amount');
        $cashExpenses          = (float) Expense::where(function ($q) {
            $q->where('payment_method', 'cash')->orWhereNull('payment_method');
        })->sum('amount');
        $cashRefunds           = (float) SaleReturn::sum('refund_amount');
        $capitalTransferred    = (float) BankTransaction::where('is_capital_transfer', true)->where('type', 'credit')->sum('amount');

        $availableCash = ($totalCashCapital + $cashSalesInflow + $cashWithdrawalsFromBank)
                       - ($cashPurchases + $cashExpenses + $cashDepositsToBank + $capitalTransferred + $cashRefunds);

        if ((float) $validated['amount'] > $availableCash) {
            return back()->withErrors(['amount' => 'Insufficient cash in hand. Available: Rs. ' . number_format($availableCash, 2)])->withInput();
        }

        DB::transaction(function () use ($validated) {
            $bank = Bank::findOrFail($validated['bank_id']);

            BankTransaction::create([
                'bank_id'              => $validated['bank_id'],
                'transaction_date'     => $validated['transfer_date'],
                'transaction_time'     => now()->format('H:i'),
                'type'                 => 'credit',
                'amount'               => $validated['amount'],
                'person_name'          => 'Capital Transfer',
                'reference_no'         => null,
                'category'             => 'Capital Transfer',
                'description'          => $validated['description'] ?? 'Cash transferred from Capital to ' . $bank->name,
                'is_capital_transfer'  => true,
                'capital_source_id'    => null,
                'user_id'              => auth()->id(),
            ]);
        });

        return redirect()->route('capital.index')->with('success', 'Rs. ' . number_format($validated['amount'], 2) . ' transferred to bank successfully.');
    }
}
