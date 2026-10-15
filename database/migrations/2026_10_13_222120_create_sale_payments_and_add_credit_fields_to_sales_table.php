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
        Schema::table('sales', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('paid_amount');
            $table->string('payment_status', 20)->default('paid')->after('due_date'); // paid, partial, due
            $table->text('notes')->nullable()->after('payment_status');
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method', 50)->default('cash');
            $table->string('note', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Backfill existing sales
        $sales = DB::table('sales')->get();
        foreach ($sales as $sale) {
            $status = 'paid';
            if ($sale->paid_amount <= 0) {
                $status = 'due';
            } elseif ($sale->paid_amount < $sale->total) {
                $status = 'partial';
            }

            DB::table('sales')->where('id', $sale->id)->update([
                'payment_status' => $status,
            ]);

            if ($sale->paid_amount > 0) {
                DB::table('sale_payments')->insert([
                    'sale_id' => $sale->id,
                    'amount' => $sale->paid_amount,
                    'payment_date' => $sale->created_at ? date('Y-m-d', strtotime($sale->created_at)) : date('Y-m-d'),
                    'payment_method' => 'cash',
                    'note' => 'Initial / Advance Payment',
                    'user_id' => $sale->user_id,
                    'created_at' => $sale->created_at ?? now(),
                    'updated_at' => $sale->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payments');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'payment_status', 'notes']);
        });
    }
};
