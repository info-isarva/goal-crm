<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('products', 'product_type')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('product_type', 20)->default('default')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'product_type')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('product_type', 20)->default('product')->change();
            });
        }
    }
};
