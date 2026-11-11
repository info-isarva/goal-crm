<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leads', 'category')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->text('category')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('leads', 'category')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->string('category', 255)->nullable()->change();
        });
    }
};
