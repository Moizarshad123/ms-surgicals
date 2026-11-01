<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_capitals', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 14, 2);
            $table->date('capital_date');
            $table->enum('type', ['initial_cash', 'additional_investment'])->default('initial_cash');
            $table->string('source', 100)->default('Owner / Partner');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_capitals');
    }
};
