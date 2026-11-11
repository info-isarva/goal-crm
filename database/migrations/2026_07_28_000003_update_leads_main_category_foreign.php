<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'main_category_id')) {
                // Rebind FK from product_categories to main_categories.
                $table->dropForeign(['main_category_id']);
                $table->foreign('main_category_id')->references('id')->on('main_categories')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'main_category_id')) {
                $table->dropForeign(['main_category_id']);
                $table->foreign('main_category_id')->references('id')->on('product_categories')->nullOnDelete();
            }
        });
    }
};
