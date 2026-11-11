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
        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'currency_code')) {
                $table->string('currency_code', 10)->nullable()->default('INR')->after('quotation_product_type');
            }
            if (!Schema::hasColumn('quotations', 'currency_symbol')) {
                $table->string('currency_symbol', 10)->nullable()->default('₹')->after('currency_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'currency_symbol')) {
                $table->dropColumn('currency_symbol');
            }
            if (Schema::hasColumn('quotations', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });
    }
};
