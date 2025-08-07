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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->foreignUuid('transaction_category_id')->references('id')->on('transaction_categories')->onDelete('cascade');

            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('date')->nullable();

            $table->tinyInteger('recurrence_type')->default(1)->nullable(); // '1: Único, 2: Mensal, 3: Anual, 4: Personalizado'
            $table->integer('recurrence_custom')->nullable(); // 'Número de repetições se for recorrência personalizada'

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
