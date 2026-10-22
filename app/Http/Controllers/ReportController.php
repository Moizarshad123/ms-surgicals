<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Resolve [$start, $end, $period, $startDate, $endDate, $date] Carbon range from request.
     */
    private function resolveRange(Request $request, string $defaultPeriod = 'monthly'): array
    {
        $period = $request->get('period', $defaultPeriod);
        $date = $request->get('date');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($period === 'all') {
            $start = Carbon::create(2020, 1, 1)->startOfDay();
            $end = now()->endOfDay();
            return [$start, $end, 'all', $start->toDateString(), $end->toDateString(), null];
        }

        if ($period === 'custom') {
            $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
            $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();
            return [$start, $end, 'custom', $start->toDateString(), $end->toDateString(), null];
        }

        $baseDate = $date ? Carbon::parse($date) : now();

        [$start, $end] = match ($period) {
            'daily'   => [$baseDate->copy()->startOfDay(), $baseDate->copy()->endOfDay()],
            'weekly'  => [$baseDate->copy()->startOfWeek(), $baseDate->copy()->endOfWeek()],
            'yearly'  => [$baseDate->copy()->startOfYear(), $baseDate->copy()->endOfYear()],
            default   => [$baseDate->copy()->startOfMonth(), $baseDate->copy()->endOfMonth()], // monthly
        };

        return [$start, $end, $period, $start->toDateString(), $end->toDateString(), $baseDate->toDateString()];
    }

    public function sales(Request $request)
    {
        [$start, $end, $period, $startDate, $endDate, $date] = $this->resolveRange($request);
        $search = $request->get('search');

        $query = Sale::with(['customer', 'items.product', 'items.returnItems', 'returns'])
            ->whereBetween('created_at', [$start, $end]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $sales = $query->latest()->get();

        $totalSales         = $sales->sum('total');
        $totalDiscount      = $sales->sum('discount');
        $totalSubTotal      = $sales->sum('sub_total');
        $totalInvoices      = $sales->count();
        $totalSoldUnits     = (int) $sales->sum(fn($s) => $s->items->sum('quantity'));
        $totalReturnedUnits = (int) $sales->sum(fn($s) => $s->items->sum(fn($i) => $i->returnItems->sum('quantity')));
        $totalItems         = max(0, $totalSoldUnits - $totalReturnedUnits);
        $averageOrder       = $totalInvoices > 0 ? ($totalSales / $totalInvoices) : 0;

        return view('reports.sales', compact(
            'sales', 'period', 'date', 'startDate', 'endDate', 'start', 'end', 'search',
            'totalSales', 'totalDiscount', 'totalSubTotal', 'totalInvoices', 'totalItems',
            'totalSoldUnits', 'totalReturnedUnits', 'averageOrder'
        ));
    }

    public function purchases(Request $request)
    {
        [$start, $end, $period, $startDate, $endDate, $date] = $this->resolveRange($request);
        $supplier = $request->get('supplier');

        $query = Purchase::with(['items.product'])
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()]);

        if ($supplier) {
            $query->where('supplier_name', 'like', "%{$supplier}%");
        }

        $purchases = $query->latest('purchase_date')->get();

        $totalPurchases      = $purchases->sum('total');
        $totalInvoices       = $purchases->count();
        $totalUnitsRestocked = $purchases->sum(fn($p) => $p->items->sum('quantity'));
        $averageOrder        = $totalInvoices > 0 ? ($totalPurchases / $totalInvoices) : 0;

        return view('reports.purchases', compact(
            'purchases', 'period', 'date', 'startDate', 'endDate', 'start', 'end', 'supplier',
            'totalPurchases', 'totalInvoices', 'totalUnitsRestocked', 'averageOrder'
        ));
    }

    public function expenses(Request $request)
    {
        [$start, $end, $period, $startDate, $endDate, $date] = $this->resolveRange($request);
        $categoryId = $request->get('expense_category_id');

        $query = Expense::with('category')
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()]);

        if ($categoryId) {
            $query->where('expense_category_id', $categoryId);
        }

        $expenses = $query->latest('expense_date')->get();

        $totalExpenses  = $expenses->sum('amount');
        $totalEntries   = $expenses->count();
        $averageExpense = $totalEntries > 0 ? ($totalExpenses / $totalEntries) : 0;

        // Top category by spending
        $categoryBreakdown = $expenses->groupBy(fn($e) => $e->category ? $e->category->name : 'General')
            ->map(fn($group) => $group->sum('amount'))
            ->sortDesc();
        $topCategory = $categoryBreakdown->keys()->first() ?? 'None';
        $topCategoryAmount = $categoryBreakdown->first() ?? 0;

        $categories = ExpenseCategory::orderBy('name')->get();

        return view('reports.expenses', compact(
            'expenses', 'period', 'date', 'startDate', 'endDate', 'start', 'end', 'categoryId', 'categories',
            'totalExpenses', 'totalEntries', 'averageExpense', 'topCategory', 'topCategoryAmount'
        ));
    }

    public function bank(Request $request)
    {
        [$start, $end, $period, $startDate, $endDate, $date] = $this->resolveRange($request);
        $bankId = $request->get('bank_id');
        $type = $request->get('type');
        $search = $request->get('search');

        $banks = Bank::all();

        // 1. Calculate Opening Balance before $start
        $priorBankQuery = Bank::query();
        if ($bankId && $bankId !== 'all') {
            $priorBankQuery->where('id', $bankId);
        }
        $baseOpening = (float) $priorBankQuery->sum('opening_balance');

        $priorTxQuery = BankTransaction::where('transaction_date', '<', $start->toDateString());
        if ($bankId && $bankId !== 'all') {
            $priorTxQuery->where('bank_id', $bankId);
        }
        $priorCredit = (float) (clone $priorTxQuery)->where('type', 'credit')->sum('amount');
        $priorDebit  = (float) (clone $priorTxQuery)->where('type', 'debit')->sum('amount');
        $openingBalance = $baseOpening + $priorCredit - $priorDebit;

        // 2. Query transactions within the period
        $query = BankTransaction::with(['bank', 'user'])
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]);

        if ($bankId && $bankId !== 'all') {
            $query->where('bank_id', $bankId);
        }

        if ($type && in_array($type, ['credit', 'debit'])) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('person_name', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Ordered chronologically for statement ledger
        $transactions = $query->orderBy('transaction_date', 'asc')
            ->orderBy('transaction_time', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate period metrics
        $totalCredit = $transactions->where('type', 'credit')->sum('amount');
        $totalDebit  = $transactions->where('type', 'debit')->sum('amount');
        $netChange   = $totalCredit - $totalDebit;
        $closingBalance = $openingBalance + $netChange;
        $totalCount  = $transactions->count();

        // Running balance for statement ledger
        $runningBalance = $openingBalance;
        $statementRows = $transactions->map(function ($tx) use (&$runningBalance) {
            if ($tx->type === 'credit') {
                $runningBalance += (float) $tx->amount;
            } else {
                $runningBalance -= (float) $tx->amount;
            }
            $tx->running_balance = $runningBalance;
            return $tx;
        });

        $selectedBank = ($bankId && $bankId !== 'all') ? Bank::find($bankId) : null;

        return view('reports.bank', compact(
            'statementRows', 'period', 'date', 'startDate', 'endDate', 'start', 'end',
            'banks', 'bankId', 'type', 'search', 'selectedBank',
            'openingBalance', 'totalCredit', 'totalDebit', 'netChange', 'closingBalance', 'totalCount'
        ));
    }

    /**
     * Sales, Purchase, and Invoice-Wise Profit Report
     */
    public function profit(Request $request)
    {
        [$start, $end, $period, $startDate, $endDate, $date] = $this->resolveRange($request, 'monthly');
        $search = $request->get('search');
        $customerId = $request->get('customer_id');
        $paymentStatus = $request->get('payment_status');

        $salesQuery = Sale::with(['customer', 'items.product', 'returns']);
        if ($period !== 'all') {
            $salesQuery->whereBetween('created_at', [$start, $end]);
        }

        if ($search) {
            $salesQuery->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($customerId && $customerId !== 'all') {
            $salesQuery->where('customer_id', $customerId);
        }

        if ($paymentStatus && in_array($paymentStatus, ['paid', 'partial', 'due'])) {
            $salesQuery->where('payment_status', $paymentStatus);
        }

        $sales = $salesQuery->latest()->get();

        // Calculate Cost and Profit for each Invoice
        $invoicesData = $sales->map(function ($sale) {
            $invoiceCost = 0;
            $itemsBreakdown = [];

            foreach ($sale->items as $item) {
                $unitCost = (float) ($item->product->purchase_price ?? 0);
                $unitPrice = (float) $item->price;
                $qty = (int) $item->quantity;
                $lineCost = $qty * $unitCost;
                $lineTotal = (float) $item->total;
                $lineProfit = $lineTotal - $lineCost;
                $lineMargin = $lineTotal > 0 ? round(($lineProfit / $lineTotal) * 100, 1) : 0;

                $invoiceCost += $lineCost;

                $itemsBreakdown[] = [
                    'id' => $item->id,
                    'product_name' => $item->product->name ?? 'Unknown Product',
                    'product_code' => $item->product->code ?? '-',
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'unit_price' => $unitPrice,
                    'line_cost' => $lineCost,
                    'line_total' => $lineTotal,
                    'line_profit' => $lineProfit,
                    'line_margin' => $lineMargin,
                ];
            }

            $saleTotal = (float) $sale->total;
            $invoiceProfit = $saleTotal - $invoiceCost;
            $profitMargin = $saleTotal > 0 ? round(($invoiceProfit / $saleTotal) * 100, 2) : 0;

            $sale->product_cost = $invoiceCost;
            $sale->invoice_profit = $invoiceProfit;
            $sale->profit_margin = $profitMargin;
            $sale->items_breakdown = $itemsBreakdown;

            return $sale;
        });

        // Summary KPI Metrics
        $totalSales = (float) $invoicesData->sum('total');
        $totalProductCost = (float) $invoicesData->sum('product_cost');
        $totalProfit = (float) $invoicesData->sum('invoice_profit');
        $overallMargin = $totalSales > 0 ? round(($totalProfit / $totalSales) * 100, 2) : 0;

        // Purchases in the same period
        $purchasesQuery = Purchase::query();
        if ($period !== 'all') {
            $purchasesQuery->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()]);
        }
        $totalPurchases = (float) $purchasesQuery->sum('total');
        $totalPurchaseInvoices = $purchasesQuery->count();

        // Expenses in the same period
        $expensesQuery = Expense::query();
        if ($period !== 'all') {
            $expensesQuery->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()]);
        }
        $totalExpenses = (float) $expensesQuery->sum('amount');
        $netProfit = $totalProfit - $totalExpenses;
        $netMargin = $totalSales > 0 ? round(($netProfit / $totalSales) * 100, 2) : 0;

        $totalInvoicesCount = $invoicesData->count();
        $totalUnitsSold = (int) $invoicesData->sum(fn($s) => $s->items->sum('quantity'));
        $averageInvoiceSale = $totalInvoicesCount > 0 ? ($totalSales / $totalInvoicesCount) : 0;
        $averageInvoiceProfit = $totalInvoicesCount > 0 ? ($totalProfit / $totalInvoicesCount) : 0;

        $customers = Customer::orderBy('name')->get();

        return view('reports.profit', compact(
            'invoicesData', 'period', 'date', 'startDate', 'endDate', 'start', 'end',
            'search', 'customerId', 'paymentStatus', 'customers',
            'totalSales', 'totalPurchases', 'totalPurchaseInvoices', 'totalProductCost',
            'totalProfit', 'overallMargin', 'totalExpenses', 'netProfit', 'netMargin',
            'totalInvoicesCount', 'totalUnitsSold', 'averageInvoiceSale', 'averageInvoiceProfit'
        ));
    }

    /**
     * Overall Business Financial Report
     * Consolidates Bank, Expense, Sales, Purchase modules into a single executive dashboard.
     */
    public function financial(Request $request)
    {
        [$start, $end, $period, $startDate, $endDate, $date] = $this->resolveRange($request, 'all');
        $bankId = $request->get('bank_id');

        // 1. CAPITAL / INVESTMENT INTRODUCED
        $totalBankOpening = (float) Bank::sum('opening_balance');

        $capitalQuery = BankTransaction::where('type', 'credit')
            ->where(function ($q) {
                $q->where('category', 'like', '%capital%')
                  ->orWhere('category', 'like', '%investment%')
                  ->orWhere('category', 'like', '%equity%')
                  ->orWhere('description', 'like', '%capital%')
                  ->orWhere('description', 'like', '%investment%');
            });
        if ($period !== 'all') {
            $capitalQuery->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]);
        }
        $periodCapitalInjected = (float) $capitalQuery->sum('amount');
        $totalCapitalIntroduced = $totalBankOpening + $periodCapitalInjected;

        // 2. SALES REVENUE & RECEIVABLES
        $salesQuery = Sale::with(['customer', 'items.product', 'returns', 'payments']);
        if ($period !== 'all') {
            $salesQuery->whereBetween('created_at', [$start, $end]);
        }
        $sales = $salesQuery->get();

        $grossSales = (float) $sales->sum('sub_total');
        $totalDiscounts = (float) $sales->sum('discount');
        $netSales = (float) $sales->sum('total');
        $salesCount = $sales->count();
        $totalSoldUnits = (int) $sales->sum(fn($s) => $s->items->sum('quantity'));

        // Sales Returns in period
        $saleReturnsQuery = SaleReturn::query();
        if ($period !== 'all') {
            $saleReturnsQuery->whereBetween('return_date', [$start->toDateString(), $end->toDateString()]);
        }
        $totalSaleReturns = (float) $saleReturnsQuery->sum('total_amount');
        $totalCashRefunds = (float) $saleReturnsQuery->sum('refund_amount');

        // Customer Receivables (Unpaid balances on sales)
        $receivablesQuery = Sale::whereIn('payment_status', ['partial', 'due']);
        if ($period !== 'all') {
            $receivablesQuery->whereBetween('created_at', [$start, $end]);
        }
        $periodReceivables = (float) $receivablesQuery->sum(DB::raw('total - paid_amount'));

        // All-time active total receivables for balance sheet
        $totalAllTimeReceivables = (float) Sale::whereIn('payment_status', ['partial', 'due'])
            ->sum(DB::raw('total - paid_amount'));

        // 3. PURCHASES & INVENTORY RESTOCKING
        $purchasesQuery = Purchase::with(['items.product']);
        if ($period !== 'all') {
            $purchasesQuery->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()]);
        }
        $purchases = $purchasesQuery->latest('purchase_date')->get();
        $totalPurchases = (float) $purchases->sum('total');
        $purchasesCount = $purchases->count();
        $totalPurchasedUnits = (int) $purchases->sum(fn($p) => $p->items->sum('quantity'));

        // Purchase Returns
        $purchaseReturnsQuery = PurchaseReturn::query();
        if ($period !== 'all') {
            $purchaseReturnsQuery->whereBetween('return_date', [$start->toDateString(), $end->toDateString()]);
        }
        $totalPurchaseReturns = (float) $purchaseReturnsQuery->sum('total_amount');
        $netPurchases = max(0, $totalPurchases - $totalPurchaseReturns);

        // Outstanding Supplier Payables (Purchases minus supplier payments through bank)
        $supplierBankPaymentsQuery = BankTransaction::where('type', 'debit')
            ->where(function ($q) {
                $q->where('category', 'like', '%supplier%')
                  ->orWhere('description', 'like', '%supplier%')
                  ->orWhere('description', 'like', '%purchase%');
            });
        if ($period !== 'all') {
            $supplierBankPaymentsQuery->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]);
        }
        $totalSupplierBankPaid = (float) $supplierBankPaymentsQuery->sum('amount');
        $outstandingPayables = (float) Purchase::whereIn('payment_status', ['partial', 'due'])->sum(DB::raw('total - paid_amount'));

        // 4. OPERATING EXPENSES
        $expensesQuery = Expense::with('category');
        if ($period !== 'all') {
            $expensesQuery->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()]);
        }
        $expenses = $expensesQuery->latest('expense_date')->get();
        $totalExpenses = (float) $expenses->sum('amount');
        $expensesCount = $expenses->count();

        $expensesByCategory = $expenses->groupBy(fn($e) => $e->category ? $e->category->name : 'General')
            ->map(fn($group) => [
                'count' => $group->count(),
                'total' => $group->sum('amount'),
                'percentage' => $totalExpenses > 0 ? round(($group->sum('amount') / $totalExpenses) * 100, 1) : 0,
            ])->sortByDesc('total');

        // 5. COST OF GOODS SOLD (COGS) & PROFITABILITY
        $totalProductCostSold = (float) $sales->sum(function ($s) {
            return $s->items->sum(function ($item) {
                return $item->quantity * (float) ($item->product->purchase_price ?? 0);
            });
        });

        $grossProfit = $netSales - $totalProductCostSold;
        $grossMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0;

        $netProfit = $grossProfit - $totalExpenses;
        $netMargin = $netSales > 0 ? round(($netProfit / $netSales) * 100, 2) : 0;

        // 6. BANK BALANCES
        $banksQuery = Bank::query();
        if ($bankId && $bankId !== 'all') {
            $banksQuery->where('id', $bankId);
        }
        $banks = $banksQuery->get();
        $totalBankBalance = (float) $banks->sum(fn($b) => $b->current_balance);

        $bankDetails = $banks->map(function ($b) use ($start, $end, $period) {
            $txQuery = $b->transactions();
            if ($period !== 'all') {
                $txQuery->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]);
            }
            $credits = (float) (clone $txQuery)->where('type', 'credit')->sum('amount');
            $debits = (float) (clone $txQuery)->where('type', 'debit')->sum('amount');
            return [
                'id' => $b->id,
                'name' => $b->name,
                'account_title' => $b->account_title,
                'account_number' => $b->account_number,
                'branch' => $b->branch,
                'opening_balance' => (float) $b->opening_balance,
                'current_balance' => (float) $b->current_balance,
                'period_credits' => $credits,
                'period_debits' => $debits,
                'period_net' => $credits - $debits,
            ];
        });

        // 7. CASH IN HAND (CASH REGISTER LIQUIDITY)
        $cashPaymentsQuery = SalePayment::where('payment_method', 'cash');
        if ($period !== 'all') {
            $cashPaymentsQuery->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()]);
        }
        $cashSalesInflow = (float) $cashPaymentsQuery->sum('amount');

        // Bank Cash movements
        $bankCashWithdrawalsQuery = BankTransaction::where('type', 'debit')
            ->where(function ($q) {
                $q->where('category', 'like', '%cash withdrawal%')
                  ->orWhere('description', 'like', '%cash withdrawal%');
            });
        $bankCashDepositsQuery = BankTransaction::where('type', 'credit')
            ->where(function ($q) {
                $q->where('category', 'like', '%cash deposit%')
                  ->orWhere('description', 'like', '%cash deposit%');
            });
        if ($period !== 'all') {
            $bankCashWithdrawalsQuery->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]);
            $bankCashDepositsQuery->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]);
        }
        $cashWithdrawalsFromBank = (float) $bankCashWithdrawalsQuery->sum('amount');
        $cashDepositsToBank = (float) $bankCashDepositsQuery->sum('amount');

        // Cash outflow for purchases not paid through bank
        $purchasesFromCash = max(0, $netPurchases - $totalSupplierBankPaid);
        // Operating expenses paid from cash
        $expensesFromCash = $totalExpenses;

        $cashInHand = ($cashSalesInflow + $cashWithdrawalsFromBank) - ($purchasesFromCash + $expensesFromCash + $cashDepositsToBank + $totalCashRefunds);
        $cashInHand = round($cashInHand, 2);

        // 8. INVENTORY VALUE (ASSET)
        $totalInventoryStockValue = (float) Product::select(DB::raw('SUM(stock * purchase_price) as val'))->value('val');
        $totalInventoryUnits = (int) Product::sum('stock');

        // 9. FINANCIAL POSITION / BALANCE SHEET
        $totalCurrentAssets = $cashInHand + $totalBankBalance + $totalAllTimeReceivables + $totalInventoryStockValue;
        $totalLiabilities = $outstandingPayables;
        $netWorth = $totalCurrentAssets - $totalLiabilities;

        // Top Debtors (Customers owing money)
        $topDebtors = Customer::whereHas('sales', function ($q) {
            $q->whereIn('payment_status', ['partial', 'due'])->whereRaw('paid_amount < total');
        })->with(['sales' => function ($q) {
            $q->whereIn('payment_status', ['partial', 'due']);
        }])->get()
          ->filter(fn($c) => $c->total_due > 0)
          ->sortByDesc('total_due')
          ->take(8);

        // Top Suppliers (Purchase history)
        $topSuppliers = Purchase::select('supplier_name', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as total_spent'))
            ->whereNotNull('supplier_name')
            ->groupBy('supplier_name')
            ->orderByDesc('total_spent')
            ->take(8)
            ->get();

        $allBanksList = Bank::orderBy('name')->get();

        return view('reports.financial', compact(
            'period', 'date', 'startDate', 'endDate', 'start', 'end', 'bankId', 'allBanksList',
            'totalCapitalIntroduced', 'totalBankOpening', 'periodCapitalInjected',
            'netSales', 'grossSales', 'totalDiscounts', 'salesCount', 'totalSoldUnits', 'totalSaleReturns',
            'totalPurchases', 'netPurchases', 'purchasesCount', 'totalPurchasedUnits', 'totalPurchaseReturns',
            'totalExpenses', 'expensesCount', 'expensesByCategory',
            'totalProductCostSold', 'grossProfit', 'grossMargin', 'netProfit', 'netMargin',
            'cashInHand', 'cashSalesInflow', 'purchasesFromCash', 'expensesFromCash', 'totalCashRefunds',
            'cashWithdrawalsFromBank', 'cashDepositsToBank',
            'totalBankBalance', 'bankDetails', 'banks',
            'periodReceivables', 'totalAllTimeReceivables', 'topDebtors',
            'outstandingPayables', 'totalSupplierBankPaid', 'topSuppliers',
            'totalInventoryStockValue', 'totalInventoryUnits',
            'totalCurrentAssets', 'totalLiabilities', 'netWorth'
        ));
    }

    // =========================================================================
    //  LEDGER REPORTS — Customer & Supplier
    // =========================================================================

    /**
     * Resolve start/end dates from a ledger period filter (1m, 3m, 6m, 1y).
     */
    private function resolveLedgerRange(Request $request): array
    {
        $period = $request->get('period', '1m');

        $end = now()->endOfDay();
        $start = match ($period) {
            '3m'  => now()->subMonths(3)->startOfDay(),
            '6m'  => now()->subMonths(6)->startOfDay(),
            '1y'  => now()->subYear()->startOfDay(),
            default => now()->startOfMonth()->startOfDay(), // 1m — current month
        };

        return [$start, $end, $period];
    }

    /**
     * Customer Ledger — list all customers with totals.
     */
    public function customerLedgerIndex(Request $request)
    {
        $search = $request->get('search');

        $query = Customer::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->get()->map(function ($customer) {
            $customer->total_sales = (float) $customer->sales()->sum('total');
            $customer->total_paid = (float) $customer->sales()->sum('paid_amount');
            $customer->total_outstanding = max(0, $customer->total_sales - $customer->total_paid);
            $customer->sales_count = $customer->sales()->count();
            return $customer;
        });

        return view('reports.customer_ledger_index', compact('customers', 'search'));
    }

    /**
     * Customer Ledger — detailed report for a specific customer.
     */
    public function customerLedgerShow(Request $request, Customer $customer)
    {
        [$start, $end, $period] = $this->resolveLedgerRange($request);

        // === OPENING BALANCE ===
        // Sum of all sales before period start minus all payments/returns before period start
        $salesBeforePeriod = (float) Sale::where('customer_id', $customer->id)
            ->where('created_at', '<', $start)
            ->sum('total');

        $paymentsBeforePeriod = (float) SalePayment::whereHas('sale', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
            ->where('payment_date', '<', $start->toDateString())
            ->sum('amount');

        $returnsBeforePeriod = (float) SaleReturn::where('customer_id', $customer->id)
            ->where('return_date', '<', $start->toDateString())
            ->sum('total_amount');

        $openingBalance = $salesBeforePeriod - $paymentsBeforePeriod - $returnsBeforePeriod;

        // === TRANSACTIONS IN PERIOD ===
        $transactions = collect();

        // 1. Sales in period
        $sales = Sale::where('customer_id', $customer->id)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($sales as $sale) {
            $transactions->push([
                'date'       => $sale->created_at,
                'type'       => 'INVOICE',
                'doc_no'     => $sale->invoice_no,
                'debit'      => (float) $sale->total,  // increases customer owes
                'credit'     => 0,
                'sort_key'   => $sale->created_at->format('Y-m-d H:i:s') . '_0_' . $sale->id,
            ]);
        }

        // 2. Sale Payments (receipts) in period
        $payments = SalePayment::whereHas('sale', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('payment_date', 'asc')
            ->get();

        foreach ($payments as $payment) {
            $transactions->push([
                'date'       => $payment->payment_date,
                'type'       => 'RECEIPT',
                'doc_no'     => $payment->sale ? $payment->sale->invoice_no : '-',
                'debit'      => 0,
                'credit'     => (float) $payment->amount,  // reduces customer owes
                'sort_key'   => $payment->payment_date->format('Y-m-d') . '_1_' . $payment->id,
            ]);
        }

        // 3. Sale Returns in period
        $returns = SaleReturn::where('customer_id', $customer->id)
            ->whereBetween('return_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('return_date', 'asc')
            ->get();

        foreach ($returns as $return) {
            $transactions->push([
                'date'       => $return->return_date,
                'type'       => 'RETURN',
                'doc_no'     => $return->return_no,
                'debit'      => 0,
                'credit'     => (float) $return->total_amount,  // reduces customer owes
                'sort_key'   => $return->return_date->format('Y-m-d') . '_1_R' . $return->id,
            ]);
        }

        // Sort chronologically
        $transactions = $transactions->sortBy('sort_key')->values();

        // Calculate running balance and totals
        $runningBalance = $openingBalance;
        $totalDebit = 0;
        $totalCredit = 0;

        $transactions = $transactions->map(function ($tx) use (&$runningBalance, &$totalDebit, &$totalCredit) {
            $runningBalance += $tx['debit'] - $tx['credit'];
            $totalDebit += $tx['debit'];
            $totalCredit += $tx['credit'];
            $tx['balance'] = $runningBalance;
            return $tx;
        });

        $closingBalance = $runningBalance;

        return view('reports.customer_ledger_show', compact(
            'customer', 'transactions', 'period', 'start', 'end',
            'openingBalance', 'totalDebit', 'totalCredit', 'closingBalance'
        ));
    }

    /**
     * Supplier Ledger — list all suppliers with totals.
     */
    public function supplierLedgerIndex(Request $request)
    {
        $search = $request->get('search');

        // Suppliers are stored as supplier_name in purchases (not a separate table)
        $query = Purchase::select('supplier_name')
            ->whereNotNull('supplier_name')
            ->where('supplier_name', '!=', '')
            ->groupBy('supplier_name');

        if ($search) {
            $query->where('supplier_name', 'like', "%{$search}%");
        }

        $suppliers = $query->orderBy('supplier_name')->get()->map(function ($row) {
            $name = $row->supplier_name;
            $purchases = Purchase::where('supplier_name', $name);
            $totalPurchases = (float) (clone $purchases)->sum('total');
            $totalPaid = (float) (clone $purchases)->sum('paid_amount');
            $purchasesCount = (clone $purchases)->count();
            $totalOutstanding = max(0, $totalPurchases - $totalPaid);

            return (object) [
                'name'              => $name,
                'total_purchases'   => $totalPurchases,
                'total_paid'        => $totalPaid,
                'total_outstanding' => $totalOutstanding,
                'purchases_count'   => $purchasesCount,
            ];
        });

        return view('reports.supplier_ledger_index', compact('suppliers', 'search'));
    }

    /**
     * Supplier Ledger — detailed report for a specific supplier.
     */
    public function supplierLedgerShow(Request $request)
    {
        $supplierName = $request->get('supplier');

        if (!$supplierName) {
            return redirect()->route('reports.supplier-ledger');
        }

        [$start, $end, $period] = $this->resolveLedgerRange($request);

        // === OPENING BALANCE ===
        $purchasesBeforePeriod = (float) Purchase::where('supplier_name', $supplierName)
            ->where('purchase_date', '<', $start->toDateString())
            ->sum('total');

        $paymentsBeforePeriod = (float) PurchasePayment::whereHas('purchase', function ($q) use ($supplierName) {
                $q->where('supplier_name', $supplierName);
            })
            ->where('payment_date', '<', $start->toDateString())
            ->sum('amount');

        $returnsBeforePeriod = (float) PurchaseReturn::where('supplier_name', $supplierName)
            ->where('return_date', '<', $start->toDateString())
            ->sum('total_amount');

        $openingBalance = $purchasesBeforePeriod - $paymentsBeforePeriod - $returnsBeforePeriod;

        // === TRANSACTIONS IN PERIOD ===
        $transactions = collect();

        // 1. Purchases in period
        $purchases = Purchase::where('supplier_name', $supplierName)
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('purchase_date', 'asc')
            ->get();

        foreach ($purchases as $purchase) {
            $transactions->push([
                'date'       => $purchase->purchase_date,
                'type'       => 'INVOICE',
                'doc_no'     => $purchase->reference_no,
                'debit'      => (float) $purchase->total,  // increases amount owed to supplier
                'credit'     => 0,
                'sort_key'   => $purchase->purchase_date->format('Y-m-d') . '_0_' . $purchase->id,
            ]);
        }

        // 2. Purchase Payments in period
        $payments = PurchasePayment::whereHas('purchase', function ($q) use ($supplierName) {
                $q->where('supplier_name', $supplierName);
            })
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('payment_date', 'asc')
            ->get();

        foreach ($payments as $payment) {
            $transactions->push([
                'date'       => $payment->payment_date,
                'type'       => 'PAYMENT',
                'doc_no'     => $payment->purchase ? $payment->purchase->reference_no : '-',
                'debit'      => 0,
                'credit'     => (float) $payment->amount,  // reduces amount owed to supplier
                'sort_key'   => $payment->payment_date->format('Y-m-d') . '_1_' . $payment->id,
            ]);
        }

        // 3. Purchase Returns in period
        $returns = PurchaseReturn::where('supplier_name', $supplierName)
            ->whereBetween('return_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('return_date', 'asc')
            ->get();

        foreach ($returns as $return) {
            $transactions->push([
                'date'       => $return->return_date,
                'type'       => 'RETURN',
                'doc_no'     => $return->return_no,
                'debit'      => 0,
                'credit'     => (float) $return->total_amount,  // reduces amount owed
                'sort_key'   => $return->return_date->format('Y-m-d') . '_1_R' . $return->id,
            ]);
        }

        // Sort chronologically
        $transactions = $transactions->sortBy('sort_key')->values();

        // Calculate running balance and totals
        $runningBalance = $openingBalance;
        $totalDebit = 0;
        $totalCredit = 0;

        $transactions = $transactions->map(function ($tx) use (&$runningBalance, &$totalDebit, &$totalCredit) {
            $runningBalance += $tx['debit'] - $tx['credit'];
            $totalDebit += $tx['debit'];
            $totalCredit += $tx['credit'];
            $tx['balance'] = $runningBalance;
            return $tx;
        });

        $closingBalance = $runningBalance;

        return view('reports.supplier_ledger_show', compact(
            'supplierName', 'transactions', 'period', 'start', 'end',
            'openingBalance', 'totalDebit', 'totalCredit', 'closingBalance'
        ));
    }
}
