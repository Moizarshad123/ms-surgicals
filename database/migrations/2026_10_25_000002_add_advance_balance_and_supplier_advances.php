<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add advance_balance to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('advance_balance', 14, 2)->default(0)->after('phone')
                  ->comment('Advance amount paid by customer that can be applied to future invoices');
        });

        // Create supplier_advances table (suppliers are text fields, not a model)
        Schema::create('supplier_advances', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_name', 150)->unique();
            $table->decimal('advance_balance', 14, 2)->default(0)
                  ->comment('Advance amount paid to supplier, applied automatically on next purchase');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('advance_balance');
        });

        Schema::dropIfExists('supplier_advances');
    }
};
