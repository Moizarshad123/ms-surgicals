<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\SupplierAdvance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditPurchaseController extends Controller
{
    /**
     * Display credit / installment purchases list with KPI metrics and filtering.
     */
    public function index(Request $request)
    {
        $status    = $request->get('status', 'pending');
        $search    = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate   = $request->get('end_date');

        $query = Purchase::with(['payments.user', 'items.product']);

        if ($status === 'pending') {
            $query->whereIn('payment_status', ['partial', 'due'])
                  ->whereRaw('paid_amount < total');
        } elseif ($status === 'cleared') {
            $query->where('payment_status', 'paid')
                  ->whereHas('payments', function ($q) {
                      $q->havingRaw('count(*) > 1');
                  });
        } elseif ($status === 'overdue') {
            $query->whereIn('payment_status', ['partial', 'due'])
                  ->whereRaw('paid_amount < total')
                  ->whereNotNull('due_date')
                  ->where('due_date', '<', now()->toDateString());
        } else {
            $query->where(function ($q) {
                $q->whereIn('payment_status', ['partial', 'due'])
                  ->orWhereHas('payments');
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        if ($startDate && $endDate) {
            $query->whereBetween('purchase_date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString(),
            ]);
        }

        $purchases = $query->latest('purchase_date')->latest('id')->paginate(15)->withQueryString();

        $totalCreditPurchasesCount = Purchase::whereIn('payment_status', ['partial', 'due'])
            ->whereRaw('paid_amount < total')->count();

        $totalCreditBilled = (float) Purchase::whereIn('payment_status', ['partial', 'due'])->sum('total');
        $totalCreditPaid   = (float) Purchase::whereIn('payment_status', ['partial', 'due'])->sum('paid_amount');
        $totalOutstandingDue = (float) Purchase::whereIn('payment_status', ['partial', 'due'])
            ->sum(DB::raw('total - paid_amount'));

        $totalOverdueCount = Purchase::whereIn('payment_status', ['partial', 'due'])
            ->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count();

        return view('credit_purchases.index', compact(
            'purchases', 'status', 'search', 'startDate', 'endDate',
            'totalCreditPurchasesCount', 'totalCreditBilled', 'totalCreditPaid',
            'totalOutstandingDue', 'totalOverdueCount'
        ));
    }

    /**
     * Grouped view: one row per supplier with aggregated totals.
     */
    public function suppliersList(Request $request)
    {
        $search       = $request->get('search');
        $statusFilter = $request->get('status', 'pending'); // pending, all

        $query = Purchase::selectRaw('
            supplier_name,
            COUNT(*) as total_invoices,
            SUM(total) as total_billed,
            SUM(LEAST(paid_amount, total)) as total_paid,
            SUM(GREATEST(total - paid_amount, 0)) as balance_due,
            MAX(purchase_date) as last_purchase_date
        ');

        if ($statusFilter === 'pending') {
            $query->whereIn('payment_status', ['partial', 'due'])->whereRaw('paid_amount < total');
        }

        if ($search) {
            $query->where('supplier_name', 'like', "%{$search}%");
        }

        $suppliers = $query->groupBy('supplier_name')
            ->orderByRaw('SUM(GREATEST(total - paid_amount, 0)) DESC')
            ->paginate(20)
            ->withQueryString();

        // KPIs
        $totalSuppliers   = Purchase::distinct('supplier_name')->count('supplier_name');
        $totalOutstanding = (float) Purchase::whereIn('payment_status', ['partial', 'due'])->sum(DB::raw('total - paid_amount'));
        $totalAdvances    = (float) SupplierAdvance::sum('advance_balance');
        $banks            = Bank::where('status', 'active')->orderBy('name')->get();

        return view('credit_purchases.suppliers', compact(
            'suppliers', 'search', 'statusFilter',
            'totalSuppliers', 'totalOutstanding', 'totalAdvances', 'banks'
        ));
    }

    /**
     * Detail page: all purchases + payments for one specific supplier.
     */
    public function supplierDetail(Request $request, $supplierName)
    {
        $supplierName = urldecode($supplierName);

        $purchases = Purchase::with(['items.product', 'payments.user'])
            ->where('supplier_name', $supplierName)
            ->latest('purchase_date')
            ->get();

        $totalBilled  = (float) $purchases->sum('total');
        $balanceDue   = (float) $purchases->sum(fn($p) => $p->due_amount);
        $totalPaid    = max(0, round($totalBilled - $balanceDue, 2));

        // Get advance balance for this supplier
        $advanceRecord  = SupplierAdvance::where('supplier_name', $supplierName)->first();
        $advanceBalance = $advanceRecord ? (float) $advanceRecord->advance_balance : 0.0;

        // All payments across all invoices, sorted by date
        $allPayments = PurchasePayment::whereIn('purchase_id', $purchases->pluck('id'))
            ->with(['purchase', 'user'])
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalInvoices   = $purchases->count();
        $paidInvoices    = $purchases->where('payment_status', 'paid')->count();
        $pendingInvoices = $purchases->whereIn('payment_status', ['partial', 'due'])->count();
        $banks           = Bank::where('status', 'active')->orderBy('name')->get();

        return view('credit_purchases.supplier_detail', compact(
            'supplierName', 'purchases', 'allPayments',
            'totalBilled', 'totalPaid', 'balanceDue', 'advanceBalance',
            'totalInvoices', 'paidInvoices', 'pendingInvoices', 'banks'
        ));
    }

    /**
     * Record payment made to a supplier (across all unpaid purchase invoices).
     * If paymentMethod is bank, creates a debit BankTransaction so bank balance decreases.
     * Cash payments decrease cash-in-hand directly via PurchasePayment payment_method=cash.
     * Excess payment is stored as supplier advance_balance.
     */
    public function storeSupplierPayment(Request $request)
    {
        $validated = $request->validate([
            'supplier_name'  => ['required', 'string', 'max:150'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:cash,bank,easypaisa,jazzcash,cheque,other'],
            'bank_id'        => ['nullable', 'required_if:payment_method,bank', 'exists:banks,id'],
            'reference_no'   => ['nullable', 'string', 'max:100'],
            'note'           => ['nullable', 'string', 'max:255'],
        ]);

        $supplierName  = trim($validated['supplier_name']);
        $paymentAmount = (float) $validated['amount'];
        $date          = $validated['payment_date'];
        $method        = $validated['payment_method'];

        DB::transaction(function () use ($validated, $supplierName, $paymentAmount, $date, $method) {
            $bankTx = null;
            // 1. If bank payment, record debit in bank_transactions to decrement bank balance
            if ($method === 'bank' && !empty($validated['bank_id'])) {
                $bank = Bank::findOrFail($validated['bank_id']);
                $bankTx = BankTransaction::create([
                    'bank_id'             => $bank->id,
                    'transaction_date'    => $date,
                    'transaction_time'    => now()->format('H:i'),
                    'type'                => 'debit',
                    'amount'              => $paymentAmount,
                    'person_name'         => $supplierName,
                    'reference_no'        => $validated['reference_no'] ?? null,
                    'category'            => 'Supplier Payment',
                    'linked_supplier'     => $supplierName,
                    'description'         => 'Paid to ' . $supplierName . ' (' . ($validated['note'] ?: 'Ledger Payment') . ')',
                    'is_capital_transfer' => false,
                    'user_id'             => auth()->id(),
                ]);
            }

            // 2. Distribute amount across pending purchases (oldest first)
            $pendingPurchases = Purchase::where('supplier_name', $supplierName)
                ->whereIn('payment_status', ['partial', 'due'])
                ->whereRaw('paid_amount < total')
                ->orderBy('purchase_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $remaining = $paymentAmount;

            foreach ($pendingPurchases as $purchase) {
                if ($remaining <= 0) {
                    break;
                }

                $due     = (float) $purchase->due_amount;
                $toApply = min($remaining, $due);
                $remaining = round($remaining - $toApply, 2);

                PurchasePayment::create([
                    'bank_transaction_id' => $bankTx?->id,
                    'purchase_id'         => $purchase->id,
                    'amount'              => $toApply,
                    'payment_date'        => $date,
                    'payment_method'      => $method,
                    'note'                => $validated['note'] ?: 'Installment Paid (' . ucfirst($method) . ')',
                    'user_id'             => auth()->id(),
                ]);

                $newPaid   = min((float) $purchase->total, (float) $purchase->paid_amount + $toApply);
                $newStatus = $newPaid >= (float) $purchase->total ? 'paid' : 'partial';

                $purchase->update([
                    'paid_amount'    => $newPaid,
                    'payment_status' => $newStatus,
                ]);
            }

            // 3. Excess payment (if remaining > 0) is saved into supplier advance balance
            if ($remaining > 0) {
                $remaining = round($remaining, 2);
                $this->addSupplierAdvance($supplierName, $remaining);

                // Always log the excess in the ledger, so supplier ledger closing = -advance
                $latestPurchase = Purchase::where('supplier_name', $supplierName)->latest('id')->first();
                if ($latestPurchase) {
                    PurchasePayment::create([
                        'bank_transaction_id' => $bankTx?->id,
                        'purchase_id'         => $latestPurchase->id,
                        'amount'              => $remaining,
                        'payment_date'        => $date,
                        'payment_method'      => $method,
                        'note'                => 'Advance Payment (Stored in Supplier Advance Balance)',
                        'user_id'             => auth()->id(),
                    ]);
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment of Rs. ' . number_format($paymentAmount, 2) . ' paid to supplier successfully!',
            ]);
        }

        return back()->with('success', 'Payment of Rs. ' . number_format($paymentAmount, 2) . ' paid to supplier successfully!');
    }

    /**
     * Get purchase details and payment history (for modal view / AJAX).
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['payments.user', 'items.product']);

        return response()->json([
            'success'  => true,
            'purchase' => [
                'id'             => $purchase->id,
                'reference_no'   => $purchase->reference_no,
                'supplier_name'  => $purchase->supplier_name ?? 'General Supplier',
                'date'           => $purchase->purchase_date->format('d M, Y'),
                'due_date'       => $purchase->due_date ? $purchase->due_date->format('d M, Y') : '—',
                'is_overdue'     => $purchase->due_date && $purchase->due_date->isPast() && $purchase->due_amount > 0,
                'total'          => (float) $purchase->total,
                'paid_amount'    => (float) $purchase->paid_amount,
                'due_amount'     => (float) $purchase->due_amount,
                'payment_status' => $purchase->payment_status,
                'notes'          => $purchase->notes,
                'items'          => $purchase->items->map(fn($item) => [
                    'product_name'     => $item->product->name ?? 'Item',
                    'quantity'         => $item->quantity,
                    'price'            => (float) $item->price,
                    'discount_percent' => (float) $item->discount_percent,
                    'discount_amount'  => (float) $item->discount_amount,
                    'total'            => (float) $item->total,
                ]),
                'sub_total'        => (float) $purchase->sub_total,
                'discount'         => (float) $purchase->discount,
                'advance_adjusted' => (float) $purchase->advance_adjusted,
                'payments'       => $purchase->payments->map(fn($p) => [
                    'id'             => $p->id,
                    'amount'         => (float) $p->amount,
                    'payment_date'   => $p->payment_date->format('d M, Y'),
                    'payment_method' => ucfirst($p->payment_method),
                    'note'           => $p->note ?: 'Installment',
                    'paid_by'        => $p->user->name ?? 'System',
                ]),
                // Supplier advance balance (if applicable)
                'supplier_advance' => (float) optional(SupplierAdvance::where('supplier_name', $purchase->supplier_name)->first())->advance_balance ?? 0,
            ],
        ]);
    }

    /**
     * Record an installment / advance payment to a supplier for a credit purchase.
     * Supports overpayment: excess is stored as advance balance for the supplier.
     */
    public function storePayment(Request $request, Purchase $purchase)
    {
        $due = (float) $purchase->due_amount;

        $validated = $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'note'           => ['nullable', 'string', 'max:255'],
        ]);

        $paymentAmount = (float) $validated['amount'];

        DB::transaction(function () use ($validated, $purchase, $due, $paymentAmount) {
            $excess     = max(0, $paymentAmount - $due);
            $applyToDue = min($paymentAmount, $due);

            // Record the payment
            PurchasePayment::create([
                'purchase_id'    => $purchase->id,
                'amount'         => $paymentAmount,
                'payment_date'   => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'note'           => $excess > 0
                    ? ($validated['note'] ?? 'Payment') . ' (Includes Rs. ' . number_format($excess, 2) . ' advance)'
                    : ($validated['note'] ?? 'Supplier Installment Payment'),
                'user_id'        => auth()->id(),
            ]);

            // Recalculate paid_amount and status correctly
            $purchase->update([
                'paid_amount'    => min((float) $purchase->total, (float) $purchase->paid_amount + $applyToDue),
                'payment_status' => ($applyToDue >= $due) ? 'paid' : 'partial',
            ]);

            // Store excess as supplier advance
            if ($excess > 0 && !empty($purchase->supplier_name)) {
                $this->addSupplierAdvance($purchase->supplier_name, $excess);
            }
        });

        if ($request->wantsJson()) {
            $fresh = $purchase->fresh();
            return response()->json([
                'success'        => true,
                'message'        => 'Payment of Rs. ' . number_format($paymentAmount, 2) . ' recorded successfully!',
                'remaining_due'  => (float) $fresh->due_amount,
                'payment_status' => $fresh->payment_status,
                'receipt_url'    => route('credit-purchases.receipt', $purchase->id),
            ]);
        }

        return back()->with('success', 'Payment of Rs. ' . number_format($paymentAmount, 2) . ' recorded successfully!');
    }

    /**
     * Delete an installment payment and reverse any advance balance impact.
     */
    public function destroyPayment(PurchasePayment $payment)
    {
        $purchase = $payment->purchase;
        $amount   = (float) $payment->amount;
        $due      = (float) ($purchase ? $purchase->due_amount : 0);

        DB::transaction(function () use ($payment, $purchase, $amount, $due) {
            // 1. If this was an advance payment entry, reverse the supplier advance balance
            if (str_contains($payment->note ?? '', 'Advance Payment (Stored in Supplier Advance Balance)')
                || $payment->payment_method === 'advance_adjustment'
            ) {
                if ($purchase && !empty($purchase->supplier_name)) {
                    $advance = SupplierAdvance::where('supplier_name', $purchase->supplier_name)->first();
                    if ($advance) {
                        $advance->advance_balance = max(0, round((float) $advance->advance_balance - $amount, 2));
                        $advance->save();
                    }
                }
            } else {
                $excessAtTimeOfPayment = max(0, $amount - $due);
                if ($excessAtTimeOfPayment > 0 && !empty($purchase->supplier_name)) {
                    $advance = SupplierAdvance::where('supplier_name', $purchase->supplier_name)->first();
                    if ($advance) {
                        $advance->advance_balance = max(0, round((float) $advance->advance_balance - $excessAtTimeOfPayment, 2));
                        $advance->save();
                    }
                }

                if ($purchase) {
                    $newPaid   = max(0, round((float) $purchase->paid_amount - min($amount, (float) $purchase->paid_amount), 2));
                    $newStatus = $newPaid <= 0 ? 'due' : ($newPaid < (float) $purchase->total ? 'partial' : 'paid');

                    $purchase->update([
                        'paid_amount'    => $newPaid,
                        'payment_status' => $newStatus,
                    ]);
                }
            }

            // 2. Synchronize bank transaction if payment was linked to one
            if ($payment->bank_transaction_id) {
                $bankTx = BankTransaction::find($payment->bank_transaction_id);
                if ($bankTx) {
                    $otherCount = PurchasePayment::where('bank_transaction_id', $bankTx->id)
                        ->where('id', '!=', $payment->id)
                        ->count();
                    if ($otherCount === 0) {
                        $bankTx->delete();
                    } else {
                        $newTxAmount = max(0, round((float) $bankTx->amount - $amount, 2));
                        if ($newTxAmount <= 0) {
                            $bankTx->delete();
                        } else {
                            $bankTx->update(['amount' => $newTxAmount]);
                        }
                    }
                }
            }

            $payment->delete();
        });

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Payment deleted successfully.']);
        }

        return back()->with('success', 'Payment deleted successfully.');
    }

    /**
     * Print a PDF receipt / voucher for a supplier payment.
     */
    public function printReceipt(Purchase $purchase)
    {
        $purchase->load(['payments.user', 'items.product']);

        // Previous balance = sum of all other pending purchases for this supplier (excluding this one)
        $previousBalance = 0;
        if ($purchase->supplier_name) {
            $previousBalance = (float) Purchase::where('supplier_name', $purchase->supplier_name)
                ->where('id', '!=', $purchase->id)
                ->whereIn('payment_status', ['partial', 'due'])
                ->sum(DB::raw('total - paid_amount'));
        }

        $itemCount = $purchase->items->count();

        $itemRows = '';
        foreach ($purchase->items as $item) {
            $discDisplay = ((float) $item->discount_percent > 0)
                ? rtrim(rtrim(number_format((float) $item->discount_percent, 2), '0'), '.') . '%'
                : '-';

            $itemRows .= '<tr>
                <td style="text-align:center;">' . $item->quantity . '</td>
                <td style="text-align:left; word-break:break-word;">' . e($item->product->name ?? 'Item') . '</td>
                <td style="text-align:right;">' . number_format((float) $item->price, 2) . '</td>
                <td style="text-align:center;">' . $discDisplay . '</td>
                <td style="text-align:right;">' . number_format((float) $item->total, 2) . '</td>
            </tr>';
        }

        $cashPaidToday = max(0, (float) $purchase->paid_amount - (float) $purchase->advance_adjusted);
        $netPayable = max(0, (float) $purchase->total - (float) $purchase->advance_adjusted);

        $htmlContent = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                @page { size: 80mm auto; margin: 3mm 0; }
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 11px; line-height: 1.25; color: #111; padding: 0 12px; }
                hr.dotted-line { border: none; border-top: 1px dashed #000; margin: 4px 0; }
                hr.solid-line { border: none; border-top: 1px solid #000; margin: 4px 0; }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 11px; }
                th, td { padding: 2px 1px; }
                .meta-table td { padding: 1px 0; font-size: 11px; }
                .meta-table .label { font-weight: bold; width: 75px; }
                .items-table thead th { border-bottom: 1px solid #000; font-size: 10px; }
                .totals-table td { padding: 1.5px 0; font-size: 11px; }
                .totals-table .label-col { width: 56%; text-align: left; }
                .totals-table .amt-col { width: 44%; text-align: right; white-space: nowrap; }
                .grand-row td { font-size: 12px; font-weight: bold; padding-top: 3px; }
                .prev-row td { font-size: 11px; font-weight: bold; padding-top: 3px; }
                .section-heading { font-size: 10px; font-weight: bold; margin: 4px 0 2px 0; text-align: left; }
                .thank-you { text-align: center; font-size: 10px; margin-top: 10px; }
            </style>
        </head>
        <body>
            ' . $this->getReceiptHeaderHtml('SUPPLIER PAYMENT VOUCHER', 'Ref # ' . $purchase->reference_no) . '
            <table class="meta-table">
                <tr><td class="label">Date:</td><td>' . $purchase->purchase_date->format('d-m-Y') . '</td></tr>
                <tr><td class="label">Supplier:</td><td>' . e($purchase->supplier_name ?? 'General Supplier') . '</td></tr>
                <tr><td class="label">Status:</td><td><strong>' . strtoupper($purchase->payment_status) . '</strong></td></tr>
                ' . ($purchase->due_date ? '<tr><td class="label">Due Date:</td><td>' . $purchase->due_date->format('d-m-Y') . '</td></tr>' : '') . '
            </table>
            <hr class="dotted-line">
            <div class="section-heading">ORDER ITEMS:</div>
            <table class="items-table">
                <thead><tr>
                    <th style="width:12%; text-align:center;">Qty</th>
                    <th style="width:42%; text-align:left;">Item</th>
                    <th style="width:18%; text-align:right;">Rate</th>
                    <th style="width:12%; text-align:center;">%</th>
                    <th style="width:16%; text-align:right;">Amount</th>
                </tr></thead>
                <tbody>' . $itemRows . '</tbody>
            </table>
            <hr class="dotted-line">
            <table class="totals-table">
                ' . ((float) $purchase->discount > 0 ? '
                <tr><td class="label-col">Sub Total:</td><td class="amt-col">Rs. ' . number_format((float) $purchase->sub_total, 2) . '</td></tr>
                <tr><td class="label-col">Discount:</td><td class="amt-col">- Rs. ' . number_format((float) $purchase->discount, 2) . '</td></tr>
                ' : '') . '
                <tr class="grand-row"><td class="label-col">Order Total:</td><td class="amt-col">Rs. ' . number_format((float) $purchase->total, 2) . '</td></tr>
                ' . ((float) $purchase->advance_adjusted > 0 ? '
                <tr><td class="label-col"><strong>Advance Payment:</strong></td><td class="amt-col" style="font-weight:bold; color:#198754;">- Rs. ' . number_format((float) $purchase->advance_adjusted, 2) . '</td></tr>
                <tr><td class="label-col"><strong>Remaining Payable:</strong></td><td class="amt-col" style="font-weight:bold;">Rs. ' . number_format($netPayable, 2) . '</td></tr>
                ' : '') . '
                <tr><td class="label-col">Paid Today:</td><td class="amt-col" style="font-weight:bold; color:#198754;">Rs. ' . number_format($cashPaidToday, 2) . '</td></tr>
                <tr><td class="label-col">Remaining Due:</td><td class="amt-col" style="font-weight:bold; color:#dc3545;">Rs. ' . number_format((float) $purchase->due_amount, 2) . '</td></tr>
            </table>
            ' . ($previousBalance > 0 ? '
            <hr class="solid-line">
            <table class="totals-table">
                <tr class="prev-row"><td class="label-col">Supplier Prev Balance:</td><td class="amt-col">Rs. ' . number_format($previousBalance, 2) . '</td></tr>
            </table>' : '') . '
            <hr class="solid-line">
            ' . ($purchase->notes ? '<p style="font-size:10px; margin-top:4px;"><strong>Notes:</strong> ' . e($purchase->notes) . '</p>' : '') . '
            <p class="thank-you">Supplier Ledger Record</p>
        </body>
        </html>';

        $pdf = Pdf::loadHTML($htmlContent)->setPaper([0, 0, 226.77, 841.89], 'portrait');
        return $pdf->stream('purchase-voucher-' . $purchase->reference_no . '.pdf');
    }

    protected function getReceiptHeaderHtml(string $receiptTitle, ?string $documentNo = null): string
    {
        return parent::getReceiptHeaderHtml($receiptTitle, $documentNo);
    }

    protected function addSupplierAdvance(string $supplierName, float $amount): void
    {
        $advance = SupplierAdvance::firstOrCreate(
            ['supplier_name' => $supplierName],
            ['advance_balance' => 0]
        );

        $advance->increment('advance_balance', round($amount, 2));
    }
}
