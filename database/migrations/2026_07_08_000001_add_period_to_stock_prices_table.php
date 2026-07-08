<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_prices', function (Blueprint $table) {
            // Add a plain index on stock_id first so MySQL FK constraint is satisfied
            // before we drop the composite unique that was its only covering index
            $table->index('stock_id', 'stock_prices_stock_id_index');
        });

        Schema::table('stock_prices', function (Blueprint $table) {
            $table->dropUnique(['stock_id', 'price_date']);
            $table->enum('period', ['daily', 'weekly'])->default('daily')->after('price_date');
            $table->unique(['stock_id', 'price_date', 'period']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_prices', function (Blueprint $table) {
            $table->dropUnique(['stock_id', 'price_date', 'period']);
            $table->dropColumn('period');
            $table->unique(['stock_id', 'price_date']);
        });

        Schema::table('stock_prices', function (Blueprint $table) {
            $table->dropIndex('stock_prices_stock_id_index');
        });
    }
};
