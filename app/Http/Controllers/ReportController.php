<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Purchase;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Resolve [$start, $end, $period, $startDate, $endDate, $date] Carbon range from request.
     */
    private function resolveRange(Request $request): array
    {
        $period = $request->get('period', 'monthly');
        $date = $request->get('date');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

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

        $query = Sale::with(['customer', 'items.product'])
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

        $totalSales     = $sales->sum('total');
        $totalDiscount  = $sales->sum('discount');
        $totalSubTotal  = $sales->sum('sub_total');
        $totalInvoices  = $sales->count();
        $totalItems     = $sales->sum(fn($s) => $s->items->sum('quantity'));
        $averageOrder   = $totalInvoices > 0 ? ($totalSales / $totalInvoices) : 0;

        return view('reports.sales', compact(
            'sales', 'period', 'date', 'startDate', 'endDate', 'start', 'end', 'search',
            'totalSales', 'totalDiscount', 'totalSubTotal', 'totalInvoices', 'totalItems', 'averageOrder'
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
}
