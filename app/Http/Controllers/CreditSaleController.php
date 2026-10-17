<?php

namespace App\Http\Controllers;

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
        $status = $request->get('status', 'pending'); // pending, all, cleared, overdue
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Query sales that were sold on credit or have remaining balance
        $query = Sale::with(['customer', 'payments.user', 'items.product']);

        if ($status === 'pending') {
            $query->whereIn('payment_status', ['partial', 'due'])
                  ->whereRaw('paid_amount < total');
        } elseif ($status === 'cleared') {
            $query->where('payment_status', 'paid')
                  ->whereHas('payments', function ($q) {
                      $q->havingRaw('count(*) > 1'); // Had installments and now cleared
                  });
        } elseif ($status === 'overdue') {
            $query->whereIn('payment_status', ['partial', 'due'])
                  ->whereRaw('paid_amount < total')
                  ->whereNotNull('due_date')
                  ->where('due_date', '<', now()->toDateString());
        } else {
            // 'all': any sale that has credit or installments
            $query->where(function ($q) {
                $q->whereIn('payment_status', ['partial', 'due'])
                  ->orWhereHas('payments');
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
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

        // Global KPI Metrics for Credit / Installments
        $totalCreditSalesCount = Sale::whereIn('payment_status', ['partial', 'due'])
            ->whereRaw('paid_amount < total')
            ->count();

        $totalCreditBilled = Sale::whereIn('payment_status', ['partial', 'due'])
            ->sum('total');

        $totalCreditCollected = Sale::whereIn('payment_status', ['partial', 'due'])
            ->sum('paid_amount');

        $totalOutstandingDue = Sale::whereIn('payment_status', ['partial', 'due'])
            ->sum(DB::raw('total - paid_amount'));

        $totalOverdueCount = Sale::whereIn('payment_status', ['partial', 'due'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        return view('credit_sales.index', compact(
            'sales',
            'status',
            'search',
            'startDate',
            'endDate',
            'totalCreditSalesCount',
            'totalCreditBilled',
            'totalCreditCollected',
            'totalOutstandingDue',
            'totalOverdueCount'
        ));
    }

    /**
     * Get sale details and payment history (for modal view / AJAX).
     */
    public function show(Sale $sale)
    {
        $sale->load(['customer', 'payments.user', 'items.product']);

        return response()->json([
            'success' => true,
            'sale' => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'customer_name' => $sale->customer->name ?? 'Walk-in Customer',
                'customer_phone' => $sale->customer->phone ?? '—',
                'date' => $sale->created_at->format('d M, Y h:i A'),
                'due_date' => $sale->due_date ? $sale->due_date->format('d M, Y') : '—',
                'is_overdue' => $sale->due_date && $sale->due_date->isPast() && $sale->due_amount > 0,
                'sub_total' => (float) $sale->sub_total,
                'discount' => (float) $sale->discount,
                'total' => (float) $sale->total,
                'paid_amount' => (float) $sale->paid_amount,
                'due_amount' => (float) $sale->due_amount,
                'payment_status' => $sale->payment_status,
                'notes' => $sale->notes,
                'items' => $sale->items->map(fn($item) => [
                    'product_name' => $item->product->name ?? 'Item',
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'total' => (float) $item->total,
                ]),
                'payments' => $sale->payments->map(fn($p) => [
                    'id' => $p->id,
                    'amount' => (float) $p->amount,
                    'payment_date' => $p->payment_date->format('d M, Y'),
                    'payment_method' => ucfirst($p->payment_method),
                    'note' => $p->note ?: 'Installment',
                    'received_by' => $p->user->name ?? 'System',
                ]),
            ],
        ]);
    }

    /**
     * Record an installment / partial payment for a sale.
     */
    public function storePayment(Request $request, Sale $sale)
    {
        $due = $sale->due_amount;

        if ($due <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'This order is already fully paid.',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:' . $due],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = DB::transaction(function () use ($validated, $sale) {
            $payment = SalePayment::create([
                'sale_id' => $sale->id,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'note' => $validated['note'] ?? 'Installment Payment',
                'user_id' => auth()->id(),
            ]);

            $newPaid = $sale->paid_amount + $validated['amount'];
            $newStatus = $newPaid >= $sale->total ? 'paid' : 'partial';

            $sale->update([
                'paid_amount' => $newPaid,
                'payment_status' => $newStatus,
            ]);

            return $payment;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Installment of Rs. ' . number_format($validated['amount']) . ' recorded successfully!',
                'remaining_due' => (float) ($sale->fresh()->due_amount),
                'payment_status' => $sale->fresh()->payment_status,
                'receipt_url' => route('credit-sales.receipt', $sale->id),
            ]);
        }

        return back()->with('success', 'Installment of Rs. ' . number_format($validated['amount']) . ' recorded successfully!');
    }

    /**
     * Delete an installment payment (if entered mistakenly).
     */
    public function destroyPayment(SalePayment $payment)
    {
        $sale = $payment->sale;

        DB::transaction(function () use ($payment, $sale) {
            $newPaid = max(0, $sale->paid_amount - $payment->amount);
            $payment->delete();

            $newStatus = 'due';
            if ($newPaid >= $sale->total) {
                $newStatus = 'paid';
            } elseif ($newPaid > 0) {
                $newStatus = 'partial';
            }

            $sale->update([
                'paid_amount' => $newPaid,
                'payment_status' => $newStatus,
            ]);
        });

        return back()->with('success', 'Payment record deleted and order balance recalculated.');
    }

    /**
     * Print Installment Ledger Statement / Receipt (Thermal / 80mm PDF).
     */
    public function printReceipt(Sale $sale)
    {
        $sale->load(['items.product', 'customer', 'payments.user']);

        $balance = $sale->total - $sale->paid_amount;

        // Build item rows
        $itemRows = '';
        foreach ($sale->items as $item) {
            $itemRows .= '<tr>
                            <td style="text-align:left;">' . e($item->product->name ?? 'Item') . '</td>
                            <td style="text-align:center;">' . $item->quantity . '</td>
                            <td>' . number_format($item->price) . '</td>
                            <td>' . number_format($item->total) . '</td>
                        </tr>';
        }

        // Build installment payments rows
        $paymentRows = '';
        $runningTotalPaid = 0;
        foreach ($sale->payments as $index => $payment) {
            $runningTotalPaid += $payment->amount;
            $remAfter = max(0, $sale->total - $runningTotalPaid);
            $paymentRows .= '<tr>
                                <td>' . ($index + 1) . '. ' . $payment->payment_date->format('d/m/Y') . '</td>
                                <td>' . e($payment->note ?: ucfirst($payment->payment_method)) . '</td>
                                <td style="text-align:right;">Rs. ' . number_format($payment->amount) . '</td>
                            </tr>';
        }

        $htmlContent = '
        <html>
        <head>
            <style>
                * { box-sizing: border-box; }
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 10px 12px;
                    font-size: 12px;
                    color: #000;
                }
                .text-center { text-align: center; }
                .shop-name {
                    font-size: 16px;
                    font-weight: bold;
                    margin: 0;
                }
                .shop-sub {
                    font-size: 11px;
                    margin: 0px 0 6px 0;
                }
                .receipt-title {
                    font-size: 14px;
                    font-weight: bold;
                    margin: 6px 0 2px 0;
                    text-transform: uppercase;
                }
                .badge-credit {
                    display: inline-block;
                    padding: 2px 6px;
                    border: 1px solid #000;
                    font-size: 10px;
                    font-weight: bold;
                    margin-bottom: 6px;
                }
                .invoice-no {
                    font-size: 12px;
                    font-weight: bold;
                    margin: 0 0 6px 0;
                }
                .solid-line {
                    border: none;
                    border-bottom: 1px solid #000;
                    margin: 5px 0;
                }
                .dotted-line {
                    border: none;
                    border-bottom: 2px dotted #000;
                    margin: 5px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 11px;
                }
                th, td {
                    padding: 2px 1px;
                }
                .meta-table td {
                    padding: 1px 0;
                    font-size: 11px;
                }
                .meta-table .label {
                    font-weight: bold;
                    width: 75px;
                }
                .items-table thead th, .payments-table thead th {
                    border-bottom: 1px solid #000;
                    font-size: 10px;
                    text-align: left;
                }
                .totals-table td {
                    font-size: 11px;
                }
                .totals-table .amt {
                    text-align: right;
                }
                .grand-row td {
                    font-size: 13px;
                    font-weight: bold;
                    padding-top: 4px;
                }
                .due-row td {
                    font-size: 14px;
                    font-weight: bold;
                    padding-top: 4px;
                    color: #000;
                }
                .section-heading {
                    font-size: 11px;
                    font-weight: bold;
                    margin: 6px 0 2px 0;
                    text-align: left;
                }
                .thank-you {
                    text-align: center;
                    font-size: 10px;
                    margin-top: 12px;
                }
                @media print {
                    @page {
                        size: 80mm auto;
                        margin: 0;
                    }
                    body {
                        width: 80mm;
                    }
                }
            </style>
        </head>
        <body>
            <div class="text-center">
                <p class="shop-name">TG SURGICALS</p>
                <p class="shop-sub">
                    Shop 58, Mirwah Road TandoAllahyar<br>
                    Phone # 0334-2830393
                </p>
                <div class="badge-credit">CREDIT / INSTALLMENT STATEMENT</div>
                <p class="invoice-no">Invoice # ' . $sale->invoice_no . '</p>
            </div>

            <hr class="solid-line">

            <table class="meta-table">
                <tr><td class="label">Date:</td><td>' . $sale->created_at->format('d-m-Y h:i A') . '</td></tr>
                <tr><td class="label">Customer:</td><td>' . e($sale->customer->name ?? 'Walk-in Customer') . '</td></tr>
                <tr><td class="label">Phone:</td><td>' . e($sale->customer->phone ?? '-') . '</td></tr>
                <tr><td class="label">Due Date:</td><td><strong>' . ($sale->due_date ? $sale->due_date->format('d-m-Y') : 'Within 1 Month') . '</strong></td></tr>
                <tr><td class="label">Status:</td><td><strong>' . strtoupper($sale->payment_status) . '</strong></td></tr>
            </table>

            <hr class="dotted-line">

            <div class="section-heading">ITEMS PURCHASED:</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="text-align:left;">Item</th>
                        <th style="text-align:center;">Qty</th>
                        <th>Price</th>
                        <th style="text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $itemRows . '
                </tbody>
            </table>

            <hr class="dotted-line">

            <table class="totals-table">
                <tr>
                    <td>Sub Total:</td>
                    <td class="amt">Rs. ' . number_format($sale->sub_total) . '</td>
                </tr>
                <tr>
                    <td>Discount:</td>
                    <td class="amt">' . ($sale->discount > 0 ? '- Rs. ' . number_format($sale->discount) : 'Rs. 0.0') . '</td>
                </tr>
                <tr class="grand-row">
                    <td>Grand Total:</td>
                    <td class="amt">Rs. ' . number_format($sale->total) . '</td>
                </tr>
            </table>

            <hr class="solid-line">

            <div class="section-heading">INSTALLMENTS / PAYMENT HISTORY:</div>
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Note / Method</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ' . ($paymentRows ?: '<tr><td colspan="3" style="text-align:center;">No payments recorded yet</td></tr>') . '
                </tbody>
            </table>

            <hr class="dotted-line">

            <table class="totals-table">
                <tr>
                    <td>Total Paid to Date:</td>
                    <td class="amt" style="font-weight:bold;">Rs. ' . number_format($sale->paid_amount) . '</td>
                </tr>
                <tr class="due-row">
                    <td>REMAINING BALANCE:</td>
                    <td class="amt">Rs. ' . number_format(max(0, $balance)) . '</td>
                </tr>
            </table>

            ' . ($sale->notes ? '<p style="font-size:10px; margin-top:5px;"><strong>Notes:</strong> ' . e($sale->notes) . '</p>' : '') . '

            <p class="thank-you">
                Please clear remaining balance by ' . ($sale->due_date ? $sale->due_date->format('d-m-Y') : 'due date') . '.<br>
                Thank you for your business!
            </p>
        </body>
        </html>';

        $pdf = Pdf::loadHTML($htmlContent)->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->stream('installment-statement-' . $sale->invoice_no . '.pdf');
    }
}

