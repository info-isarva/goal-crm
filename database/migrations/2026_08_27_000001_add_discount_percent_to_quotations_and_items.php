<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('discount_percent', 8, 2)->default(0)->after('discount');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->decimal('discount_percent', 8, 2)->default(0)->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });
    }
};
