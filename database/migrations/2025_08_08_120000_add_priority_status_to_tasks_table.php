<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (! Schema::hasColumn('tasks', 'priority')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->enum('priority', ['high','higest','low','lowest','normal'])->default('normal')->after('description');
            });
        }
        if (! Schema::hasColumn('tasks', 'status')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->enum('status', ['Not Started','Deferred','In Progress','Completed','Waiting for input'])->default('Not Started')->after('priority');
            });
        }
    }

    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['priority', 'status']);
        });
    }
};
