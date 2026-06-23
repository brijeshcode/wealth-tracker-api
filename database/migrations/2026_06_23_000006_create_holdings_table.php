<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('platform_id')->constrained('platforms')->restrictOnDelete();
            $table->enum('type', ['stock', 'mf', 'fd', 'rd', 'sgb', 'bond', 'gold', 'ppf', 'nps']);
            $table->enum('status', ['active', 'matured', 'redeemed', 'closed', 'broken'])->default('active');
            $table->decimal('principal_amount', 14, 2)->default(0);
            $table->decimal('current_value', 14, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('nickname')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holdings');
    }
};
