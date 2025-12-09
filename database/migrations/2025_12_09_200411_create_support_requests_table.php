<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // se seu users é UUID:
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('category_slug')->default('outros'); // ex: 'outros'
            $table->string('subject')->nullable();              // opcional
            $table->text('message');                            // o que o usuário digitou
            $table->string('status')->default('aberto');        // aberto, fechado, etc (se quiser usar depois)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};

