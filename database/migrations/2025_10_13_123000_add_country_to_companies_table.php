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
            if (! Schema::hasColumn('companies', 'country')) {
                $table->string('country', 100)->nullable()->after('currency_position');
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
            $table->dropColumn('country');
        });
    }
};
