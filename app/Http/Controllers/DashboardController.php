<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\BusinessCapital;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // 1. Daily KPIs
        $todaySales = (float) Sale::whereDate('created_at', $today)->sum('total');
        $todaySalesCount = Sale::whereDate('created_at', $today)->count();

        $todayPurchases = (float) Purchase::whereDate('purchase_date', $today)->sum('total');
        $todayPurchasesCount = Purchase::whereDate('purchase_date', $today)->count();

        $todayExpenses = (float) Expense::whereDate('expense_date', $today)->sum('amount');
        $todayExpensesCount = Expense::whereDate('expense_date', $today)->count();

        $todayNetCash = $todaySales - ($todayPurchases + $todayExpenses);

        // 2. Monthly & Overall KPIs
        $monthSales = (float) Sale::whereMonth('created_at', $today->month)
            ->whereYear('created_at', $today->year)
            ->sum('total');
        $monthSalesCount = Sale::whereMonth('created_at', $today->month)
            ->whereYear('created_at', $today->year)
            ->count();

        $totalInventory = (int) Product::sum('stock');
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $totalCustomers = Customer::count();
        $totalLowStock = Product::lowStock()->count();

        // 3. 7-Day Revenue & Expense Trend Data for Area Chart
        $days = collect(range(6, 0))->map(function ($i) {
            return Carbon::today()->subDays($i);
        });

        $chartLabels = $days->map(fn($d) => $d->format('d M'))->values()->toArray();

        $chartSales = $days->map(function ($d) {
            return round((float) Sale::whereDate('created_at', $d)->sum('total'), 2);
        })->values()->toArray();

        $chartPurchases = $days->map(function ($d) {
            return round((float) Purchase::whereDate('purchase_date', $d)->sum('total'), 2);
        })->values()->toArray();

        $chartExpenses = $days->map(function ($d) {
            return round((float) Expense::whereDate('expense_date', $d)->sum('amount'), 2);
        })->values()->toArray();

        // 4. Category Distribution Donut Chart Data
        $categoriesWithProducts = Category::withCount('products')
            ->having('products_count', '>', 0)
            ->orderByDesc('products_count')
            ->take(6)
            ->get();

        if ($categoriesWithProducts->isEmpty()) {
            $categoriesWithProducts = Category::withCount('products')->get();
        }

        $donutCategoryLabels = $categoriesWithProducts->pluck('name')->toArray();
        $donutCategorySeries = $categoriesWithProducts->pluck('products_count')->map(fn($v) => (int)$v)->toArray();

        // 5. Monthly Cashflow Donut Chart Data (Sales vs Purchases vs Expenses)
        $monthPurchases = (float) Purchase::whereMonth('purchase_date', $today->month)
            ->whereYear('purchase_date', $today->year)
            ->sum('total');
        $monthExpenses = (float) Expense::whereMonth('expense_date', $today->month)
            ->whereYear('expense_date', $today->year)
            ->sum('amount');

        $cashflowLabels = ['Sales Revenue', 'Restock Purchases', 'Operating Expenses'];
        $cashflowSeries = [round($monthSales, 2), round($monthPurchases, 2), round($monthExpenses, 2)];

        // 6. Top 5 Selling Products
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total) as total_revenue'))
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        // 7. Low Stock Alerts
        $lowStockProducts = Product::lowStock()->with('category')->orderBy('stock')->take(6)->get();

        // 8. Recent Sales Log
        $recentSales = Sale::with(['customer', 'items'])->latest()->take(6)->get();

        // 9. Bank Balances
        $banks = Bank::all();
        $totalBankBalance = (float) $banks->sum(fn($b) => $b->current_balance);

        // 10. Cash in Hand
        // Initial capital introduced as cash
        $totalCashCapital = (float) BusinessCapital::sum('amount');

        // All cash received: cash sales + installment payments in cash
        $cashSalesInflow = (float) SalePayment::where('payment_method', 'cash')->sum('amount');

        // Cash withdrawn from bank (physical cash taken out)
        $cashWithdrawalsFromBank = (float) BankTransaction::where('type', 'debit')
            ->where('is_capital_transfer', false)
            ->where(function ($q) {
                $q->where('category', 'like', '%cash withdrawal%')
                  ->orWhere('description', 'like', '%cash withdrawal%');
            })->sum('amount');

        // Cash deposited to bank (physical cash put in)
        $cashDepositsToBank = (float) BankTransaction::where('type', 'credit')
            ->where('is_capital_transfer', false)
            ->where(function ($q) {
                $q->where('category', 'like', '%cash deposit%')
                  ->orWhere('description', 'like', '%cash deposit%');
            })->sum('amount');

        // Capital transferred directly to bank
        $capitalTransferredToBank = (float) BankTransaction::where('is_capital_transfer', true)
            ->where('type', 'credit')
            ->sum('amount');

        // Cash paid for purchases
        $cashPurchases = (float) PurchasePayment::where('payment_method', 'cash')->sum('amount');

        // All expenses in cash (bank expenses are debited from bank balance)
        $cashExpenses = (float) Expense::where(function ($q) {
            $q->where('payment_method', 'cash')->orWhereNull('payment_method');
        })->sum('amount');

        // Sale refunds paid back in cash
        $cashRefunds = (float) SaleReturn::sum('refund_amount');

        $cashInHand = ($totalCashCapital + $cashSalesInflow + $cashWithdrawalsFromBank)
                    - ($cashPurchases + $cashExpenses + $cashDepositsToBank + $capitalTransferredToBank + $cashRefunds);
        $cashInHand = round($cashInHand, 2);

        return view('dashboard.index', compact(
            'todaySales', 'todaySalesCount', 'todayPurchases', 'todayPurchasesCount',
            'todayExpenses', 'todayExpensesCount', 'todayNetCash',
            'monthSales', 'monthSalesCount', 'totalInventory', 'totalProducts',
            'totalCategories', 'totalCustomers', 'totalLowStock',
            'chartLabels', 'chartSales', 'chartPurchases', 'chartExpenses',
            'donutCategoryLabels', 'donutCategorySeries',
            'cashflowLabels', 'cashflowSeries',
            'topProducts', 'lowStockProducts', 'recentSales',
            // Financial position tabs
            'banks', 'totalBankBalance',
            'cashInHand', 'totalCashCapital', 'cashSalesInflow', 'cashPurchases',
            'cashExpenses', 'cashRefunds', 'cashWithdrawalsFromBank', 'cashDepositsToBank'
        ));
    }
}
