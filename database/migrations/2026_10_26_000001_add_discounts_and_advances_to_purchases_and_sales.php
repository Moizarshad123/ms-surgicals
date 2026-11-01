<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add discount fields to purchase_items
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('discount_percent', 8, 2)->default(0)->after('price')
                  ->comment('Discount percentage applied per unit');
            $table->decimal('discount_amount', 14, 2)->default(0)->after('discount_percent')
                  ->comment('Total discount amount for this line item');
        });

        // 2. Add sub_total, discount, and advance_adjusted to purchases
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('sub_total', 14, 2)->default(0)->after('supplier_name')
                  ->comment('Total before item discounts');
            $table->decimal('discount', 14, 2)->default(0)->after('sub_total')
                  ->comment('Total item discounts combined');
            $table->decimal('advance_adjusted', 14, 2)->default(0)->after('paid_amount')
                  ->comment('Amount deducted from supplier advance balance');
        });

        // 3. Add advance_adjusted to sales
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('advance_adjusted', 14, 2)->default(0)->after('paid_amount')
                  ->comment('Amount deducted from customer advance balance');
        });

        // 4. Backfill existing data
        DB::statement("UPDATE purchases SET sub_total = total WHERE sub_total = 0 OR sub_total IS NULL");

        DB::statement("
            UPDATE purchases p
            SET advance_adjusted = COALESCE((
                SELECT SUM(pp.amount)
                FROM purchase_payments pp
                WHERE pp.purchase_id = p.id AND pp.payment_method = 'advance_adjustment'
            ), 0)
        ");

        DB::statement("
            UPDATE sales s
            SET advance_adjusted = COALESCE((
                SELECT SUM(sp.amount)
                FROM sale_payments sp
                WHERE sp.sale_id = s.id AND sp.payment_method = 'advance_adjustment'
            ), 0)
        ");
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'discount_amount']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['sub_total', 'discount', 'advance_adjusted']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['advance_adjusted']);
        });
    }
};

