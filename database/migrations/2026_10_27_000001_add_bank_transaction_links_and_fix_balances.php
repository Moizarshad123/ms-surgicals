<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add linked fields to bank_transactions if not present
        Schema::table('bank_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_transactions', 'linked_supplier')) {
                $table->string('linked_supplier', 150)->nullable()->after('category');
            }
            if (!Schema::hasColumn('bank_transactions', 'linked_customer_id')) {
                $table->unsignedBigInteger('linked_customer_id')->nullable()->after('linked_supplier');
            }
        });

        // 2. Add bank_transaction_id to purchase_payments
        Schema::table('purchase_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_payments', 'bank_transaction_id')) {
                $table->unsignedBigInteger('bank_transaction_id')->nullable()->after('purchase_id')->index();
            }
        });

        // 3. Add bank_transaction_id to sale_payments
        Schema::table('sale_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_payments', 'bank_transaction_id')) {
                $table->unsignedBigInteger('bank_transaction_id')->nullable()->after('sale_id')->index();
            }
        });

        // 4. Backfill existing purchase_payments with matching bank_transactions
        // Match TX #6 (268,200.00, Khalid Mansoor Cure Sure) -> Payment 1
        DB::table('purchase_payments')->where('id', 1)->update(['bank_transaction_id' => 6]);
        DB::table('bank_transactions')->where('id', 6)->update(['linked_supplier' => 'Khalid Mansoor Cure Sure']);

        // Match TX #7 (46,814.30, Razi bandage and cotton) -> Payment 2
        DB::table('purchase_payments')->where('id', 2)->update(['bank_transaction_id' => 7]);
        DB::table('bank_transactions')->where('id', 7)->update(['linked_supplier' => 'Razi bandage and cotton']);

        // Match TX #8 (50,000.00, Shah Surgical) -> Payment 3
        DB::table('purchase_payments')->where('id', 3)->update(['bank_transaction_id' => 8]);
        DB::table('bank_transactions')->where('id', 8)->update(['linked_supplier' => 'Shah Surgical']);

        // Match TX #9 (200,000.00, Shah Surgical) -> Payments 4, 5, 6
        DB::table('purchase_payments')->whereIn('id', [4, 5, 6])->update(['bank_transaction_id' => 9]);
        DB::table('bank_transactions')->where('id', 9)->update(['linked_supplier' => 'Shah Surgical']);

        // Match TX #10 (500,000.00, Wahab Memon) -> Payment 7
        DB::table('purchase_payments')->where('id', 7)->update(['bank_transaction_id' => 10]);
        DB::table('bank_transactions')->where('id', 10)->update(['linked_supplier' => 'Wahab Memon']);

        // Match TX #11 (150,000.00, Wahab Memon) -> Payment 9
        DB::table('purchase_payments')->where('id', 9)->update(['bank_transaction_id' => 11]);
        DB::table('bank_transactions')->where('id', 11)->update(['linked_supplier' => 'Wahab Memon']);

        // 5. Fix overpaid purchases (Purchase #10 and #15)
        // Cap paid_amount to total so no duplicate phantom paid amounts exist
        DB::statement("UPDATE purchases SET paid_amount = total WHERE paid_amount > total");
        DB::statement("UPDATE sales SET paid_amount = total WHERE paid_amount > total");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_payments', 'bank_transaction_id')) {
                $table->dropColumn('bank_transaction_id');
            }
        });

        Schema::table('sale_payments', function (Blueprint $table) {
            if (Schema::hasColumn('sale_payments', 'bank_transaction_id')) {
                $table->dropColumn('bank_transaction_id');
            }
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('bank_transactions', 'linked_customer_id')) {
                $table->dropColumn('linked_customer_id');
            }
            if (Schema::hasColumn('bank_transactions', 'linked_supplier')) {
                $table->dropColumn('linked_supplier');
            }
        });
    }
};

