<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (!Schema::hasColumn('deals', 'main_category_id')) {
                $table->unsignedBigInteger('main_category_id')->nullable()->after('category');
                $table->foreign('main_category_id')->references('id')->on('main_categories')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (Schema::hasColumn('deals', 'main_category_id')) {
                $table->dropForeign(['main_category_id']);
                $table->dropColumn('main_category_id');
            }
        });
    }
};
