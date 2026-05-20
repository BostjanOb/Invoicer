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
        Schema::table('accounting_periods', function (Blueprint $table) {
            $table->decimal('monthly_tax_paid', 10, 2)->default(0.00)->after('is_closed');
            $table->string('tax_calculator')->nullable()->after('monthly_tax_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_periods', function (Blueprint $table) {
            $table->dropColumn(['monthly_tax_paid', 'tax_calculator']);
        });
    }
};
