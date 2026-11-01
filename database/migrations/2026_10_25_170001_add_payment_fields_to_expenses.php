<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('note'); // cash, bank, easypaisa, jazzcash, cheque, other
            $table->unsignedBigInteger('bank_id')->nullable()->after('payment_method');
            $table->foreign('bank_id')->references('id')->on('banks')->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['bank_id']);
            $table->dropColumn(['payment_method', 'bank_id']);
        });
    }
};
