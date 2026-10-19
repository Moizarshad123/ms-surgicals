<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BankTransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CreditSaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesReturnController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
    Route::get('/pos/invoice/{sale}', [PosController::class, 'invoice'])->name('pos.invoice');
    Route::get('/pos/customers/search', [PosController::class, 'searchCustomers'])->name('pos.customers.search');
    Route::delete('/sales/{sale}', [PosController::class, 'destroy'])->name('sales.destroy');

    // Credit Sales & Installments
    Route::get('/credit-sales', [CreditSaleController::class, 'index'])->name('credit-sales.index');
    Route::get('/credit-sales/{sale}', [CreditSaleController::class, 'show'])->name('credit-sales.show');
    Route::post('/credit-sales/{sale}/payments', [CreditSaleController::class, 'storePayment'])->name('credit-sales.payments.store');
    Route::delete('/credit-sales/payments/{payment}', [CreditSaleController::class, 'destroyPayment'])->name('credit-sales.payments.destroy');
    Route::get('/credit-sales/{sale}/receipt', [CreditSaleController::class, 'printReceipt'])->name('credit-sales.receipt');

    // Sales Returns
    Route::get('/sales-returns', [SalesReturnController::class, 'index'])->name('sales-returns.index');
    Route::get('/sales-returns/create', [SalesReturnController::class, 'create'])->name('sales-returns.create');
    Route::post('/sales-returns', [SalesReturnController::class, 'store'])->name('sales-returns.store');
    Route::get('/sales-returns/search-sale', [SalesReturnController::class, 'searchSale'])->name('sales-returns.search-sale');
    Route::get('/sales-returns/{saleReturn}', [SalesReturnController::class, 'show'])->name('sales-returns.show');
    Route::get('/sales-returns/{saleReturn}/receipt', [SalesReturnController::class, 'receipt'])->name('sales-returns.receipt');

    // Products
    Route::resource('products', ProductController::class)->except(['show']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Purchases
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');

    // Purchase Returns
    Route::get('/purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns.index');
    Route::get('/purchase-returns/create', [PurchaseReturnController::class, 'create'])->name('purchase-returns.create');
    Route::post('/purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store');
    Route::get('/purchase-returns/search-purchase', [PurchaseReturnController::class, 'searchPurchase'])->name('purchase-returns.search-purchase');
    Route::get('/purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('purchase-returns.show');
    Route::get('/purchase-returns/{purchaseReturn}/receipt', [PurchaseReturnController::class, 'receipt'])->name('purchase-returns.receipt');

    // Expenses
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::post('/expense-categories', [ExpenseController::class, 'storeCategory'])->name('expense-categories.store');

    // Bank Accounts & Transactions
    Route::get('/banks', [BankTransactionController::class, 'index'])->name('banks.index');
    Route::post('/banks/transactions', [BankTransactionController::class, 'store'])->name('banks.transactions.store');
    Route::put('/banks/transactions/{bankTransaction}', [BankTransactionController::class, 'update'])->name('banks.transactions.update');
    Route::delete('/banks/transactions/{bankTransaction}', [BankTransactionController::class, 'destroy'])->name('banks.transactions.destroy');
    Route::put('/banks/{bank}', [BankTransactionController::class, 'updateBank'])->name('banks.update');

    // Reports
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');
    Route::get('/reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
    Route::get('/reports/bank', [ReportController::class, 'bank'])->name('reports.bank');
});
