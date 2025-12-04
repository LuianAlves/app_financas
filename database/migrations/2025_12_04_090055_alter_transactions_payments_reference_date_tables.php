// migration
Schema::table('payment_transactions', function (\Illuminate\Database\Schema\Blueprint $table) {
    $table->date('reference_date')->nullable()->after('payment_date');
});