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
            if (! Schema::hasColumn('companies', 'fy_start_month')) {
                $table->tinyInteger('fy_start_month')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('companies', 'fy_start_day')) {
                $table->tinyInteger('fy_start_day')->nullable()->after('fy_start_month');
            }
            if (! Schema::hasColumn('companies', 'currency_code')) {
                $table->string('currency_code', 10)->nullable()->after('fy_start_day');
            }
            if (! Schema::hasColumn('companies', 'currency_symbol')) {
                $table->string('currency_symbol', 10)->nullable()->after('currency_code');
            }
            if (! Schema::hasColumn('companies', 'currency_position')) {
                $table->string('currency_position', 10)->default('prefix')->after('currency_symbol');
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
            $table->dropColumn(['fy_start_month','fy_start_day','currency_code','currency_symbol','currency_position']);
        });
    }
};
