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
        Schema::table('custom_item_recurrents', function (Blueprint $table) {
            $table->unique(['recurrent_id','reference_year','reference_month','payment_day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_item_recurrents', function (Blueprint $table) {
            //
        });
    }
};
