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
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total');
            $table->date('due_date')->nullable()->after('paid_amount');
            $table->string('payment_status', 20)->default('paid')->after('due_date'); // paid, partial, due
            $table->text('notes')->nullable()->after('payment_status');
        });

        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method', 50)->default('cash');
            $table->string('note', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Backfill existing purchases
        $purchases = DB::table('purchases')->get();
        foreach ($purchases as $purchase) {
            DB::table('purchases')->where('id', $purchase->id)->update([
                'paid_amount' => $purchase->total,
                'payment_status' => 'paid',
            ]);

            if ($purchase->total > 0) {
                DB::table('purchase_payments')->insert([
                    'purchase_id' => $purchase->id,
                    'amount' => $purchase->total,
                    'payment_date' => $purchase->purchase_date ?? date('Y-m-d'),
                    'payment_method' => 'cash',
                    'note' => 'Initial / Full Payment',
                    'user_id' => null,
                    'created_at' => $purchase->created_at ?? now(),
                    'updated_at' => $purchase->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'due_date', 'payment_status', 'notes']);
        });
    }
};

