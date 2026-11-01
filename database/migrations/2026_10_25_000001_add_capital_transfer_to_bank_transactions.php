<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->boolean('is_capital_transfer')->default(false)->after('category');
            $table->unsignedBigInteger('capital_source_id')->nullable()->after('is_capital_transfer')
                  ->comment('References business_capitals.id if this transaction originated from a capital transfer');
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropColumn(['is_capital_transfer', 'capital_source_id']);
        });
    }
};
