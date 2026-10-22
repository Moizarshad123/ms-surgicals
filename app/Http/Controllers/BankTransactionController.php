<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Sale;
use App\Models\SalePayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Return suppliers (unique supplier_name from purchases) with outstanding balance > 0.
     * Used by the Add Transaction modal via AJAX.
     */
    public function suppliersWithDue()
    {
        $suppliers = Purchase::whereIn('payment_status', ['partial', 'due'])
            ->whereRaw('paid_amount < total')
            ->selectRaw('supplier_name, SUM(total - paid_amount) as total_due')
            ->groupBy('supplier_name')
            ->having('total_due', '>', 0)
            ->orderBy('supplier_name')
            ->get()
            ->map(fn($row) => [
                'name'      => $row->supplier_name,
                'total_due' => round((float) $row->total_due, 2),
            ]);

        return response()->json($suppliers);
    }

    /**
     * Return customers with outstanding credit sale balance > 0.
     * Used by the Add Transaction modal via AJAX.
     */
    public function customersWithDue()
    {
        $customers = Customer::whereHas('sales', function ($q) {
                $q->whereIn('payment_status', ['partial', 'due'])
                  ->whereRaw('paid_amount < total');
            })
            ->with(['sales' => function ($q) {
                $q->whereIn('payment_status', ['partial', 'due'])
                  ->whereRaw('paid_amount < total');
            }])
            ->get()
            ->map(fn($customer) => [
                'id'        => $customer->id,
                'name'      => $customer->name,
                'total_due' => round(
                    $customer->sales->sum(fn($s) => (float)$s->total - (float)$s->paid_amount),
                    2
                ),
            ])
            ->filter(fn($c) => $c['total_due'] > 0)
            ->values();

        return response()->json($customers);
    }

    /**
     * Store a newly created bank transaction in storage.
     * When category is 'Supplier Payment' (debit) or 'Customer Receipt' (credit),
     * automatically create linked PurchasePayment / SalePayment records.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_id'          => ['required', 'exists:banks,id'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['nullable', 'date_format:H:i'],
            'type'             => ['required', 'in:credit,debit'],
            'amount'           => ['required', 'numeric', 'min:1'],
            'person_name'      => ['required', 'string', 'max:150'],
            'reference_no'     => ['nullable', 'string', 'max:100'],
            'category'         => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string', 'max:500'],
            // Linked entity fields (optional)
            'linked_supplier'  => ['nullable', 'string', 'max:150'],
            'linked_customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);

        $validated['user_id'] = auth()->id();
        if (empty($validated['transaction_time'])) {
            $validated['transaction_time'] = now()->format('H:i');
        }

        DB::transaction(function () use ($validated, $request) {
            // Remove helper fields before creating the bank transaction
            $txData = collect($validated)->except(['linked_supplier', 'linked_customer_id'])->toArray();
            BankTransaction::create($txData);

            $amount   = (float) $validated['amount'];
            $date     = $validated['transaction_date'];
            $category = strtolower(trim($validated['category'] ?? ''));

            // ── Supplier Payment (Debit) ──────────────────────────────────
            if ($category === 'supplier payment'
                && $validated['type'] === 'debit'
                && !empty($validated['linked_supplier'])
            ) {
                $supplierName = $validated['linked_supplier'];

                // Find all pending purchases for this supplier, oldest first
                $pendingPurchases = Purchase::where('supplier_name', $supplierName)
                    ->whereIn('payment_status', ['partial', 'due'])
                    ->whereRaw('paid_amount < total')
                    ->orderBy('purchase_date')
                    ->orderBy('id')
                    ->get();

                $remaining = $amount;

                foreach ($pendingPurchases as $purchase) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $due        = (float) $purchase->due_amount;
                    $toApply    = min($remaining, $due);
                    $remaining -= $toApply;

                    PurchasePayment::create([
                        'purchase_id'    => $purchase->id,
                        'amount'         => $toApply,
                        'payment_date'   => $date,
                        'payment_method' => 'bank_transfer',
                        'note'           => 'Bank Transfer – ' . ($validated['reference_no'] ?? 'via bank'),
                        'user_id'        => $validated['user_id'],
                    ]);

                    $newPaid   = (float) $purchase->paid_amount + $toApply;
                    $newStatus = $newPaid >= (float) $purchase->total ? 'paid' : 'partial';
                    $purchase->update([
                        'paid_amount'    => $newPaid,
                        'payment_status' => $newStatus,
                    ]);
                }
            }

            // ── Customer Receipt (Credit) ─────────────────────────────────
            if ($category === 'customer receipt'
                && $validated['type'] === 'credit'
                && !empty($validated['linked_customer_id'])
            ) {
                $customerId = (int) $validated['linked_customer_id'];

                // Find all pending sales for this customer, oldest first
                $pendingSales = Sale::where('customer_id', $customerId)
                    ->whereIn('payment_status', ['partial', 'due'])
                    ->whereRaw('paid_amount < total')
                    ->orderBy('id')
                    ->get();

                $remaining = $amount;

                foreach ($pendingSales as $sale) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $due        = (float) $sale->due_amount;
                    $toApply    = min($remaining, $due);
                    $remaining -= $toApply;

                    SalePayment::create([
                        'sale_id'        => $sale->id,
                        'amount'         => $toApply,
                        'payment_date'   => $date,
                        'payment_method' => 'bank_transfer',
                        'note'           => 'Bank Transfer – ' . ($validated['reference_no'] ?? 'via bank'),
                        'user_id'        => $validated['user_id'],
                    ]);

                    $newPaid   = (float) $sale->paid_amount + $toApply;
                    $newStatus = $newPaid >= (float) $sale->total ? 'paid' : 'partial';
                    $sale->update([
                        'paid_amount'    => $newPaid,
                        'payment_status' => $newStatus,
                    ]);
                }
            }
        });

        return redirect()->route('banks.index')->with('success', 'Bank transaction recorded successfully.');
    }

    /**
     * Update an existing bank transaction in storage.
     */
    public function update(Request $request, BankTransaction $bankTransaction)
    {
        $validated = $request->validate([
            'bank_id'          => ['required', 'exists:banks,id'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['nullable', 'date_format:H:i'],
            'type'             => ['required', 'in:credit,debit'],
            'amount'           => ['required', 'numeric', 'min:1'],
            'person_name'      => ['required', 'string', 'max:150'],
            'reference_no'     => ['nullable', 'string', 'max:100'],
            'category'         => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string', 'max:500'],
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
            'account_title'   => ['nullable', 'string', 'max:150'],
            'account_number'  => ['nullable', 'string', 'max:100'],
            'branch'          => ['nullable', 'string', 'max:150'],
            'opening_balance' => ['required', 'numeric'],
            'notes'           => ['nullable', 'string', 'max:500'],
        ]);

        $bank->update($validated);

        return redirect()->route('banks.index')->with('success', $bank->name . ' account details updated successfully.');
    }
}

