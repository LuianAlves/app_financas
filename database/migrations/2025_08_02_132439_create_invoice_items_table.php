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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('invoice_id')->constrained()->onDelete('cascade');

            $table->foreignUuid('card_id')->constrained()->onDelete('cascade');

            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');

            $table->string('description');

            $table->decimal('amount', 12, 2);

            $table->date('date');

            $table->unsignedTinyInteger('installments')->default(1);
            $table->unsignedTinyInteger('current_installment')->default(1);

            $table->foreignUuid('transaction_category_id')->references('id')->on('transaction_categories')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_transactions');
    }
};
