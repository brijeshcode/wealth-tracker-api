<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_holding_id')->constrained('stock_holdings')->cascadeOnDelete();
            $table->enum('type', ['buy', 'sell', 'dividend', 'bonus', 'split']);
            $table->decimal('quantity', 12, 4)->nullable();
            $table->decimal('price_per_unit', 10, 4)->nullable();
            $table->decimal('amount', 14, 2);
            $table->date('transaction_date');
            $table->enum('source', ['manual', 'csv_import', 'api_sync'])->default('manual');
            $table->string('reference')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
