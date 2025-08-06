<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('nome'); // Ex: Salário, Aluguel, Mercado, etc.
            $table->enum('tipo', ['entrada', 'saida', 'investimento']);
            $table->decimal('limite_mensal', 12, 2)->nullable();
            $table->string('cor')->default('#18dec7');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
