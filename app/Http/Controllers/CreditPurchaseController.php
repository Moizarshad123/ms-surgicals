<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchasePayment;
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
        $status = $request->get('status', 'pending'); // pending, all, cleared, overdue
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Query purchases that were bought on credit or have remaining balance
        $query = Purchase::with(['payments.user', 'items.product']);

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
            // 'all': any purchase that has credit or installments
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

        // Global KPI Metrics for Credit Purchases / Installments
        $totalCreditPurchasesCount = Purchase::whereIn('payment_status', ['partial', 'due'])
            ->whereRaw('paid_amount < total')
            ->count();

        $totalCreditBilled = (float) Purchase::whereIn('payment_status', ['partial', 'due'])
            ->sum('total');

        $totalCreditPaid = (float) Purchase::whereIn('payment_status', ['partial', 'due'])
            ->sum('paid_amount');

        $totalOutstandingDue = (float) Purchase::whereIn('payment_status', ['partial', 'due'])
            ->sum(DB::raw('total - paid_amount'));

        $totalOverdueCount = Purchase::whereIn('payment_status', ['partial', 'due'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        return view('credit_purchases.index', compact(
            'purchases',
            'status',
            'search',
            'startDate',
            'endDate',
            'totalCreditPurchasesCount',
            'totalCreditBilled',
            'totalCreditPaid',
            'totalOutstandingDue',
            'totalOverdueCount'
        ));
    }

    /**
     * Get purchase details and payment history (for modal view / AJAX).
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['payments.user', 'items.product']);

        return response()->json([
            'success' => true,
            'purchase' => [
                'id' => $purchase->id,
                'reference_no' => $purchase->reference_no,
                'supplier_name' => $purchase->supplier_name ?? 'General Supplier',
                'date' => $purchase->purchase_date->format('d M, Y'),
                'due_date' => $purchase->due_date ? $purchase->due_date->format('d M, Y') : '—',
                'is_overdue' => $purchase->due_date && $purchase->due_date->isPast() && $purchase->due_amount > 0,
                'total' => (float) $purchase->total,
                'paid_amount' => (float) $purchase->paid_amount,
                'due_amount' => (float) $purchase->due_amount,
                'payment_status' => $purchase->payment_status,
                'notes' => $purchase->notes,
                'items' => $purchase->items->map(fn($item) => [
                    'product_name' => $item->product->name ?? 'Item',
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'total' => (float) $item->total,
                ]),
                'payments' => $purchase->payments->map(fn($p) => [
                    'id' => $p->id,
                    'amount' => (float) $p->amount,
                    'payment_date' => $p->payment_date->format('d M, Y'),
                    'payment_method' => ucfirst($p->payment_method),
                    'note' => $p->note ?: 'Installment',
                    'paid_by' => $p->user->name ?? 'System',
                ]),
            ],
        ]);
    }

    /**
     * Record an installment payment to a supplier for a credit purchase.
     */
    public function storePayment(Request $request, Purchase $purchase)
    {
        $due = $purchase->due_amount;

        if ($due <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'This purchase order is already fully paid.',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:' . $due],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = DB::transaction(function () use ($validated, $purchase) {
            $payment = PurchasePayment::create([
                'purchase_id' => $purchase->id,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'note' => $validated['note'] ?? 'Supplier Installment Payment',
                'user_id' => auth()->id(),
            ]);

            $newPaid = $purchase->paid_amount + $validated['amount'];
            $newStatus = $newPaid >= $purchase->total ? 'paid' : 'partial';

            $purchase->update([
                'paid_amount' => $newPaid,
                'payment_status' => $newStatus,
            ]);

            return $payment;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Installment of Rs. ' . number_format($validated['amount']) . ' paid to supplier successfully!',
                'remaining_due' => (float) ($purchase->fresh()->due_amount),
                'payment_status' => $purchase->fresh()->payment_status,
                'receipt_url' => route('credit-purchases.receipt', $purchase->id),
            ]);
        }

        return back()->with('success', 'Installment of Rs. ' . number_format($validated['amount']) . ' paid successfully!');
    }

    /**
     * Delete an installment payment (if entered mistakenly).
     */
    public function destroyPayment(PurchasePayment $payment)
    {
        $purchase = $payment->purchase;

        DB::transaction(function () use ($payment, $purchase) {
            $newPaid = max(0, $purchase->paid_amount - $payment->amount);
            $payment->delete();

            $newStatus = 'due';
            if ($newPaid >= $purchase->total) {
                $newStatus = 'paid';
            } elseif ($newPaid > 0) {
                $newStatus = 'partial';
            }

            $purchase->update([
                'paid_amount' => $newPaid,
                'payment_status' => $newStatus,
            ]);
        });

        return back()->with('success', 'Payment record deleted and supplier purchase balance recalculated.');
    }

    /**
     * Print Installment Ledger Statement / Payment Voucher (Thermal / 80mm PDF).
     */
    public function printReceipt(Purchase $purchase)
    {
        $purchase->load(['items.product', 'payments']);

        // Previous balance of the supplier from earlier purchases
        $previousBalance = 0;
        if ($purchase->supplier_name) {
            $previousBalance = (float) Purchase::where('supplier_name', $purchase->supplier_name)
                ->where('id', '<', $purchase->id)
                ->whereRaw('total > paid_amount')
                ->sum(DB::raw('total - paid_amount'));
            $previousBalance = max(0, $previousBalance);
        }

        // Build item rows
        $itemRows = '';
        foreach ($purchase->items as $item) {
            $itemRows .= '<tr>
                            <td style="text-align:center;">' . $item->quantity . '</td>
                            <td style="text-align:left;">' . e($item->product->name ?? 'Item') . '</td>
                            <td>' . number_format($item->price) . '</td>
                            <td style="text-align:right;">' . number_format($item->total) . '</td>
                        </tr>';
        }

        // Build payments history rows
        $paymentRows = '';
        foreach ($purchase->payments as $p) {
            $paymentRows .= '<tr>
                                <td>' . $p->payment_date->format('d-m-Y') . '</td>
                                <td>' . ucfirst($p->payment_method) . '</td>
                                <td style="text-align:right; font-weight:bold;">Rs. ' . number_format($p->amount) . '</td>
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
                .solid-line {
                    border: none;
                    border-bottom: 1px solid #000;
                    margin: 6px 0;
                }
                .dotted-line {
                    border: none;
                    border-bottom: 2px dotted #000;
                    margin: 6px 0;
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
                    width: 80px;
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
                .prev-row td {
                    font-size: 12px;
                    font-weight: bold;
                    padding-top: 3px;
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
                    img {
                        display: block;
                        max-width: 80% !important;
                    }
                    body {
                        width: 80mm;
                    }
                }
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
                <thead>
                    <tr>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:left;">Item</th>
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
                <tr class="grand-row">
                    <td>Order Total:</td>
                    <td class="amt">Rs. ' . number_format($purchase->total) . '</td>
                </tr>
                <tr>
                    <td>Total Paid:</td>
                    <td class="amt" style="font-weight:bold; color:#198754;">Rs. ' . number_format($purchase->paid_amount) . '</td>
                </tr>
                <tr>
                    <td>Remaining Due:</td>
                    <td class="amt" style="font-weight:bold; color:#dc3545;">Rs. ' . number_format($purchase->due_amount) . '</td>
                </tr>
            </table>
 

            ' . ($previousBalance > 0 ? '
            <hr class="solid-line">
            <table class="totals-table">
                <tr class="prev-row">
                    <td>Supplier Prev Balance:</td>
                    <td class="amt">Rs. ' . number_format($previousBalance) . '</td>
                </tr>
            </table>
            ' : '') . '
            <hr class="solid-line">
            ' . ($purchase->notes ? '<p style="font-size:10px; margin-top:5px;"><strong>Notes:</strong> ' . e($purchase->notes) . '</p>' : '') . '
            <p class="thank-you">
                TG SURGICALS &bull; Supplier Ledger Record
            </p>
        </body>
        </html>';

        $pdf = Pdf::loadHTML($htmlContent)->setPaper([0, 0, 226.77, 841.89], 'portrait');
        return $pdf->stream('purchase-voucher-' . $purchase->reference_no . '.pdf');
    }
}

