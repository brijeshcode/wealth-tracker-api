<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_holding_id')->constrained('stock_holdings')->cascadeOnDelete();
            $table->foreignId('buy_transaction_id')->constrained('stock_transactions')->cascadeOnDelete();
            $table->decimal('quantity_remaining', 12, 4);
            $table->boolean('is_exhausted')->default(false);
            $table->date('locked_until')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['stock_holding_id', 'is_exhausted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lots');
    }
};
