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
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('account_title', 150)->nullable();
            $table->string('account_number', 100)->nullable();
            $table->string('branch', 150)->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained('banks')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->time('transaction_time')->nullable();
            $table->enum('type', ['credit', 'debit']); // credit = received (in), debit = paid (out)
            $table->decimal('amount', 15, 2);
            $table->string('person_name', 150); // amount kise di ya kis sa receive ki (party/person)
            $table->string('reference_no', 100)->nullable(); // cheque #, deposit slip #, online transaction ID
            $table->string('category', 100)->nullable(); // e.g. Customer Receipt, Supplier Payment, Expense, Transfer, etc.
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Seed the 2 primary banks
        DB::table('banks')->insert([
            [
                'name' => 'Bank Islami',
                'account_title' => 'TG Surgicals',
                'account_number' => null,
                'branch' => 'Main Branch',
                'opening_balance' => 0.00,
                'status' => 'active',
                'notes' => 'Primary Islamic Banking Account',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Meezan Bank',
                'account_title' => 'TG Surgicals',
                'account_number' => null,
                'branch' => 'Main Branch',
                'opening_balance' => 0.00,
                'status' => 'active',
                'notes' => 'Premier Islamic Banking Account',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('banks');
    }
};

