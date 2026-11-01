<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SalePayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditSaleController extends Controller
{
    /**
     * Display credit / installment sales list with KPI metrics and filtering.
     */
    public function index(Request $request)
    {
        $status    = $request->get('status', 'pending');
        $search    = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate   = $request->get('end_date');

        $query = Sale::with(['customer', 'payments.user', 'items.product']);

        if ($status === 'pending') {
            $query->whereIn('payment_status', ['partial', 'due'])->whereRaw('paid_amount < total');
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
                $q->whereIn('payment_status', ['partial', 'due'])->orWhereHas('payments');
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        $sales = $query->latest()->paginate(15)->withQueryString();

        $totalCreditSalesCount = Sale::whereIn('payment_status', ['partial', 'due'])->whereRaw('paid_amount < total')->count();
        $totalCreditBilled     = Sale::whereIn('payment_status', ['partial', 'due'])->sum('total');
        $totalCreditCollected  = Sale::whereIn('payment_status', ['partial', 'due'])->sum('paid_amount');
        $totalOutstandingDue   = Sale::whereIn('payment_status', ['partial', 'due'])->sum(DB::raw('total - paid_amount'));
        $totalOverdueCount     = Sale::whereIn('payment_status', ['partial', 'due'])
            ->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count();

        return view('credit_sales.index', compact(
            'sales', 'status', 'search', 'startDate', 'endDate',
            'totalCreditSalesCount', 'totalCreditBilled', 'totalCreditCollected',
            'totalOutstandingDue', 'totalOverdueCount'
        ));
    }

    /**
     * Grouped customer list: one row per customer with aggregated totals.
     */
    public function customersList(Request $request)
    {
        $search       = $request->get('search');
        $statusFilter = $request->get('status', 'pending');

        $query = Customer::withSum(['sales as total_billed' => function ($q) use ($statusFilter) {
            if ($statusFilter === 'pending') {
                $q->whereIn('payment_status', ['partial', 'due']);
            }
        }], 'total')
        ->withSum(['sales as total_paid_sum' => function ($q) use ($statusFilter) {
            if ($statusFilter === 'pending') {
                $q->whereIn('payment_status', ['partial', 'due']);
            }
        }], 'paid_amount')
        ->withCount(['sales as total_invoices' => function ($q) use ($statusFilter) {
            if ($statusFilter === 'pending') {
                $q->whereIn('payment_status', ['partial', 'due']);
            }
        }])
        ->having('total_invoices', '>', 0);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderByRaw('(total_billed - total_paid_sum) DESC')
            ->paginate(20)
            ->withQueryString();

        $totalCustomers   = Customer::whereHas('sales', fn($q) => $q->whereIn('payment_status', ['partial', 'due']))->count();
        $totalOutstanding = (float) Sale::whereIn('payment_status', ['partial', 'due'])->sum(DB::raw('GREATEST(total - paid_amount, 0)'));
        $totalAdvances    = (float) Customer::sum('advance_balance');
        $banks            = Bank::where('status', 'active')->orderBy('name')->get();

        return view('credit_sales.customers', compact(
            'customers', 'search', 'statusFilter',
            'totalCustomers', 'totalOutstanding', 'totalAdvances', 'banks'
        ));
    }

    /**
     * Detail page: all sales + payments for one specific customer.
     */
    public function customerDetail(Customer $customer)
    {
        $sales = Sale::with(['items.product', 'payments.user'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->get();

        $totalBilled    = (float) $sales->sum('total');
        $balanceDue     = (float) $sales->sum(fn($s) => $s->due_amount);
        $totalPaid      = max(0, round($totalBilled - $balanceDue, 2));
        $advanceBalance = (float) $customer->advance_balance;

        $allPayments = SalePayment::whereIn('sale_id', $sales->pluck('id'))
            ->with(['sale', 'user'])
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalInvoices   = $sales->count();
        $paidInvoices    = $sales->where('payment_status', 'paid')->count();
        $pendingInvoices = $sales->whereIn('payment_status', ['partial', 'due'])->count();
        $banks           = Bank::where('status', 'active')->orderBy('name')->get();

        return view('credit_sales.customer_detail', compact(
            'customer', 'sales', 'allPayments',
            'totalBilled', 'totalPaid', 'balanceDue', 'advanceBalance',
            'totalInvoices', 'paidInvoices', 'pendingInvoices', 'banks'
        ));
    }

    /**
     * Record payment received from customer (across all unpaid invoices).
     * If paymentMethod is bank, creates a credit BankTransaction so bank balance increases.
     * Cash payments increase cash-in-hand directly via SalePayment payment_method=cash.
     * Excess payment is stored as customer advance_balance.
     */
    public function storeCustomerPayment(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:cash,bank,easypaisa,jazzcash,cheque,other'],
            'bank_id'        => ['nullable', 'required_if:payment_method,bank', 'exists:banks,id'],
            'reference_no'   => ['nullable', 'string', 'max:100'],
            'note'           => ['nullable', 'string', 'max:255'],
        ]);

        $paymentAmount = (float) $validated['amount'];
        $date          = $validated['payment_date'];
        $method        = $validated['payment_method'];

        DB::transaction(function () use ($validated, $customer, $paymentAmount, $date, $method) {
            $bankTx = null;
            // 1. If bank payment, record in bank_transactions to increment bank balance
            if ($method === 'bank' && !empty($validated['bank_id'])) {
                $bank = Bank::findOrFail($validated['bank_id']);
                $bankTx = BankTransaction::create([
                    'bank_id'             => $bank->id,
                    'transaction_date'    => $date,
                    'transaction_time'    => now()->format('H:i'),
                    'type'                => 'credit',
                    'amount'              => $paymentAmount,
                    'person_name'         => $customer->name,
                    'reference_no'        => $validated['reference_no'] ?? null,
                    'category'            => 'Customer Receipt',
                    'linked_customer_id'  => $customer->id,
                    'description'         => 'Received from ' . $customer->name . ' (' . ($validated['note'] ?: 'Ledger Payment') . ')',
                    'is_capital_transfer' => false,
                    'user_id'             => auth()->id(),
                ]);
            }

            // 2. Distribute amount across pending invoices (oldest first)
            $pendingSales = Sale::where('customer_id', $customer->id)
                ->whereIn('payment_status', ['partial', 'due'])
                ->whereRaw('paid_amount < total')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $remaining = $paymentAmount;

            foreach ($pendingSales as $sale) {
                if ($remaining <= 0) {
                    break;
                }

                $due     = (float) $sale->due_amount;
                $toApply = min($remaining, $due);
                $remaining = round($remaining - $toApply, 2);

                SalePayment::create([
                    'bank_transaction_id' => $bankTx?->id,
                    'sale_id'             => $sale->id,
                    'amount'              => $toApply,
                    'payment_date'        => $date,
                    'payment_method'      => $method,
                    'note'                => $validated['note'] ?: 'Installment Received (' . ucfirst($method) . ')',
                    'user_id'             => auth()->id(),
                ]);

                $newPaid   = min((float) $sale->total, (float) $sale->paid_amount + $toApply);
                $newStatus = $newPaid >= (float) $sale->total ? 'paid' : 'partial';

                $sale->update([
                    'paid_amount'    => $newPaid,
                    'payment_status' => $newStatus,
                ]);
            }
            
            $remaining = round($remaining, 2);
            // 3. Excess payment (if remaining > 0) is saved into customer advance balance
            if ($remaining > 0) {
                $customer->increment('advance_balance', $remaining);

                // Always log the excess in the ledger so closing balance = -advance
                $latestSale = Sale::where('customer_id', $customer->id)->latest('id')->first();
                if ($latestSale) {
                    SalePayment::create([
                        'bank_transaction_id' => $bankTx?->id,
                        'sale_id'             => $latestSale->id,
                        'amount'              => $remaining,
                        'payment_date'        => $date,
                        'payment_method'      => $method,
                        'note'                => 'Advance Deposit (Stored in Customer Advance Balance)',
                        'user_id'             => auth()->id(),
                    ]);
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment of Rs. ' . number_format($paymentAmount, 2) . ' received and applied successfully!',
            ]);
        }

        return back()->with('success', 'Payment of Rs. ' . number_format($paymentAmount, 2) . ' received and applied successfully!');
    }

    /**
     * Get sale details and payment history (for modal view / AJAX).
     */
    public function show(Sale $sale)
    {
        $sale->load(['customer', 'payments.user', 'items.product']);

        return response()->json([
            'success' => true,
            'sale'    => [
                'id'              => $sale->id,
                'invoice_no'      => $sale->invoice_no,
                'customer_name'   => $sale->customer->name ?? 'Walk-in Customer',
                'customer_phone'  => $sale->customer->phone ?? '—',
                'date'            => $sale->created_at->format('d M, Y h:i A'),
                'due_date'        => $sale->due_date ? $sale->due_date->format('d M, Y') : '—',
                'is_overdue'      => $sale->due_date && $sale->due_date->isPast() && $sale->due_amount > 0,
                'sub_total'       => (float) $sale->sub_total,
                'discount'        => (float) $sale->discount,
                'total'           => (float) $sale->total,
                'paid_amount'     => (float) $sale->paid_amount,
                'due_amount'      => (float) $sale->due_amount,
                'payment_status'  => $sale->payment_status,
                'notes'           => $sale->notes,
                'customer_advance'=> (float) optional($sale->customer)->advance_balance ?? 0,
                'items'           => $sale->items->map(fn($item) => [
                    'product_name' => $item->product->name ?? 'Item',
                    'quantity'     => $item->quantity,
                    'price'        => (float) $item->price,
                    'total'        => (float) $item->total,
                ]),
                'payments'        => $sale->payments->map(fn($p) => [
                    'id'             => $p->id,
                    'amount'         => (float) $p->amount,
                    'payment_date'   => $p->payment_date->format('d M, Y'),
                    'payment_method' => ucfirst($p->payment_method),
                    'note'           => $p->note ?: 'Installment',
                    'received_by'    => $p->user->name ?? 'System',
                ]),
            ],
        ]);
    }

    /**
     * Record an installment / advance payment for a sale.
     * Supports overpayment: excess is stored as customer advance balance.
     */
    public function storePayment(Request $request, Sale $sale)
    {
        $due = (float) $sale->due_amount;

        $validated = $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'note'           => ['nullable', 'string', 'max:255'],
        ]);

        $paymentAmount = (float) $validated['amount'];

        DB::transaction(function () use ($validated, $sale, $due, $paymentAmount) {
            $excess     = max(0, $paymentAmount - $due);
            $applyToDue = min($paymentAmount, $due);

            SalePayment::create([
                'sale_id'        => $sale->id,
                'amount'         => $paymentAmount,
                'payment_date'   => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'note'           => $excess > 0
                    ? ($validated['note'] ?? 'Payment') . ' (Includes Rs. ' . number_format($excess, 2) . ' advance)'
                    : ($validated['note'] ?? 'Installment Payment'),
                'user_id'        => auth()->id(),
            ]);

            $newPaid   = (float) $sale->paid_amount + $applyToDue;
            $newStatus = $newPaid >= (float) $sale->total ? 'paid' : 'partial';

            $sale->update([
                'paid_amount'    => $newPaid,
                'payment_status' => $newStatus,
            ]);

            // Store excess as customer advance balance
            if ($excess > 0 && $sale->customer_id) {
                $customer = $sale->customer;
                if ($customer) {
                    $customer->increment('advance_balance', $excess);
                }
            }
        });

        if ($request->wantsJson()) {
            $fresh = $sale->fresh();
            return response()->json([
                'success'        => true,
                'message'        => 'Payment of Rs. ' . number_format($paymentAmount, 2) . ' recorded successfully!',
                'remaining_due'  => (float) $fresh->due_amount,
                'payment_status' => $fresh->payment_status,
                'receipt_url'    => route('credit-sales.receipt', $sale->id),
            ]);
        }

        return back()->with('success', 'Payment of Rs. ' . number_format($paymentAmount, 2) . ' recorded successfully!');
    }

    /**
     * Delete an installment payment and reverse any advance balance impact.
     */
    public function destroyPayment(SalePayment $payment)
    {
        $sale   = $payment->sale;
        $amount = (float) $payment->amount;

        DB::transaction(function () use ($payment, $sale, $amount) {
            $note     = (string) $payment->note;
            $customer = $sale->customer_id ? $sale->customer : null;

            if (str_starts_with($note, 'Advance Deposit')) {
                // Pure advance row: sirf advance reverse karo, invoice ko mat chhedo
                if ($customer) {
                    $customer->advance_balance = max(0, round((float) $customer->advance_balance - $amount, 2));
                    $customer->save();
                }
            } else {
                // Mixed row from storePayment(): note mein excess likha hota hai
                $excess = 0.0;
                if (preg_match('/Includes Rs\. ([\d,]+(?:\.\d+)?) advance/', $note, $m)) {
                    $excess = (float) str_replace(',', '', $m[1]);
                }

                if ($excess > 0 && $customer) {
                    $customer->advance_balance = max(0, round((float) $customer->advance_balance - $excess, 2));
                    $customer->save();
                }

                $applied   = max(0, $amount - $excess);
                $newPaid   = max(0, round((float) $sale->paid_amount - min($applied, (float) $sale->paid_amount), 2));
                $newStatus = $newPaid <= 0 ? 'due' : ($newPaid < (float) $sale->total ? 'partial' : 'paid');

                $sale->update([
                    'paid_amount'    => $newPaid,
                    'payment_status' => $newStatus,
                ]);
            }

            // Synchronize bank transaction if payment was linked to one
            if ($payment->bank_transaction_id) {
                $bankTx = BankTransaction::find($payment->bank_transaction_id);
                if ($bankTx) {
                    $otherCount = SalePayment::where('bank_transaction_id', $bankTx->id)
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
     * Print a PDF receipt for a customer credit sale.
     */
    public function printReceipt(Sale $sale)
    {
        $sale->load(['customer', 'payments.user', 'items.product']);

        $previousBalance = 0;
        if ($sale->customer_id) {
            $previousBalance = (float) Sale::where('customer_id', $sale->customer_id)
                ->where('id', '!=', $sale->id)
                ->whereIn('payment_status', ['partial', 'due'])
                ->sum(DB::raw('total - paid_amount'));
        }

        $itemCount = $sale->items->count();

        $itemRows = '';
        foreach ($sale->items as $item) {
            $discPercent = (float) ($item->discount_percent ?? 0);
            $discAmount  = (float) ($item->discount_amount ?? 0);
            $discDisplay = $discPercent > 0
                ? rtrim(rtrim(number_format($discPercent, 2), '0'), '.') . '%'
                : '-';

            $itemRows .= '<tr>
                <td style="text-align:center;">' . $item->quantity . '</td>
                <td style="text-align:left; word-break:break-word;">' . e($item->product->name ?? 'Item') .
                    ($discAmount > 0 ? '<div style="font-size:9.5px; color:#555; line-height:1.2;">Disc: -Rs. ' . number_format($discAmount, 2) . '</div>' : '') .
                '</td>
                <td style="text-align:right;">' . number_format((float) $item->price, 2) . '</td>
                <td style="text-align:center;">' . $discDisplay . '</td>
                <td style="text-align:right;">' . number_format((float) $item->total, 2) . '</td>
            </tr>';
        }

        $advanceAdjusted = (float) $sale->advance_adjusted;
        if ($advanceAdjusted <= 0) {
            $advanceAdjusted = (float) $sale->payments()->where('payment_method', 'advance_adjustment')->sum('amount');
        }
        $cashPaidToday = max(0, (float) $sale->paid_amount - $advanceAdjusted);
        $netPayable = max(0, (float) $sale->total - $advanceAdjusted);

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
                .meta-table .label { font-weight: bold; width: 70px; }
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
            ' . $this->getReceiptHeaderHtml('SALE RECEIPT', 'Invoice # ' . $sale->invoice_no) . '
            <table class="meta-table">
                <tr><td class="label">Date:</td><td>' . $sale->created_at->format('d-m-Y h:i A') . '</td></tr>
                <tr><td class="label">Customer:</td><td>' . e($sale->customer->name ?? 'Walk-in Customer') . '</td></tr>
                <tr><td class="label">Phone:</td><td>' . e($sale->customer->phone ?? '-') . '</td></tr>
                <tr><td class="label">Status:</td><td><strong>' . strtoupper($sale->payment_status) . '</strong></td></tr>
            </table>
            <hr class="dotted-line">
            <div class="section-heading">ITEMS PURCHASED:</div>
            <table class="items-table">
                <thead><tr>
                    <th style="width:12%; text-align:center;">Qty</th>
                    <th style="width:40%; text-align:left;">Item</th>
                    <th style="width:18%; text-align:right;">Price</th>
                    <th style="width:12%; text-align:center;">Disc%</th>
                    <th style="width:18%; text-align:right;">Total</th>
                </tr></thead>
                <tbody>' . $itemRows . '</tbody>
            </table>
            <hr class="dotted-line">
            <table class="totals-table">
                <tr><td class="label-col">Sub Total (' . $itemCount . ' ' . ($itemCount === 1 ? 'Item' : 'Items') . '):</td><td class="amt-col">Rs. ' . number_format((float) $sale->sub_total, 2) . '</td></tr>
                ' . ((float)$sale->discount > 0 ? '<tr><td class="label-col">Discount:</td><td class="amt-col">- Rs. ' . number_format((float)$sale->discount, 2) . '</td></tr>' : '') . '
                <tr class="grand-row"><td class="label-col">Inv Total Amount:</td><td class="amt-col">Rs. ' . number_format((float) $sale->total, 2) . '</td></tr>
                ' . ($advanceAdjusted > 0 ? '
                <tr><td class="label-col"><strong>Advance Payment:</strong></td><td class="amt-col" style="font-weight:bold; color:#198754;">- Rs. ' . number_format($advanceAdjusted, 2) . '</td></tr>
                <tr><td class="label-col"><strong>Remaining Payable:</strong></td><td class="amt-col" style="font-weight:bold;">Rs. ' . number_format($netPayable, 2) . '</td></tr>
                ' : '') . '
                <tr><td class="label-col">' . ($sale->due_amount > 0 ? 'Paid Today:' : 'Paid Amount:') . '</td><td class="amt-col" style="font-weight:bold;">Rs. ' . number_format($cashPaidToday, 2) . '</td></tr>
                <tr><td class="label-col">Remaining Due:</td><td class="amt-col" style="font-weight:bold; color:#dc3545;">Rs. ' . number_format((float) $sale->due_amount, 2) . '</td></tr>
            </table>
            ' . ($sale->customer_id ? '
            <hr class="solid-line">
            <table class="totals-table">
                <tr class="prev-row"><td class="label-col">Previous Balance:</td><td class="amt-col">Rs. ' . number_format($previousBalance, 2) . '</td></tr>
            </table>' : '') . '
            ' . ($sale->notes ? '<p style="font-size:10px; margin-top:4px;"><strong>Notes:</strong> ' . e($sale->notes) . '</p>' : '') . '
            <p class="thank-you">Thank you for your business!</p>
        </body>
        </html>';

        $pdf = Pdf::loadHTML($htmlContent)->setPaper([0, 0, 226.77, 841.89], 'portrait');
        return $pdf->stream('invoice-' . $sale->invoice_no . '.pdf');
    }
}
