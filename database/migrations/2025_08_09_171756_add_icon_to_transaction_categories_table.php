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
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('color');
            // after('color') coloca logo depois da coluna color
        });
    }

    public function down(): void
    {
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
