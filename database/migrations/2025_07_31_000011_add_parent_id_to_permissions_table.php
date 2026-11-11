<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->bigInteger('parent_id')->unsigned()->nullable()->after('id');
            $table->foreign('parent_id')->references('id')->on('parent_permissions')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
