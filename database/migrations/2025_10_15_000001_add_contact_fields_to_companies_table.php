<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('companies')) {
            return;
        }
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'mobile')) {
                $table->string('mobile')->nullable();
            }
            if (! Schema::hasColumn('companies', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('companies', 'website')) {
                $table->string('website')->nullable();
            }
            if (! Schema::hasColumn('companies', 'favicon')) {
                $table->string('favicon')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['mobile', 'email', 'website', 'favicon']);
        });
    }
};
