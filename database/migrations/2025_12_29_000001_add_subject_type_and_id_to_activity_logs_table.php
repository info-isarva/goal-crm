<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_logs', 'subject_type')) {
                $table->string('subject_type')->nullable()->after('details');
            }
            if (!Schema::hasColumn('activity_logs', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            }
        });
    }

    public function down()
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (Schema::hasColumn('activity_logs', 'subject_type')) {
                $table->dropColumn('subject_type');
            }
            if (Schema::hasColumn('activity_logs', 'subject_id')) {
                $table->dropColumn('subject_id');
            }
        });
    }
};
