<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('companies')) {
            return;
        }
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'fy_end_month')) {
                $table->unsignedTinyInteger('fy_end_month')->nullable()->after('fy_start_day');
            }
            if (! Schema::hasColumn('companies', 'fy_end_day')) {
                $table->unsignedTinyInteger('fy_end_day')->nullable()->after('fy_end_month');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['fy_end_month', 'fy_end_day']);
        });
    }
};
