<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_price_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->date('price_date');
            $table->enum('status', ['success', 'failed', 'skipped']);
            $table->enum('triggered_by', ['scheduler', 'api'])->default('scheduler');
            $table->unsignedInteger('stocks_updated')->default(0);
            $table->text('message')->nullable();
            $table->timestamps();

            $table->unique(['price_date', 'triggered_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_price_sync_logs');
    }
};
