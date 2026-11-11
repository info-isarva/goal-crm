<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigIncrements('id')->change();
            $table->text('crm_page_right')->nullable()->after('remember_token');
            $table->bigInteger('crm_role_type')->nullable()->after('crm_page_right');
            $table->bigInteger('created_by')->unsigned()->nullable()->after('crm_role_type');
            $table->bigInteger('updated_by')->unsigned()->nullable()->after('created_by');
            $table->bigInteger('deleted_by')->unsigned()->nullable()->after('updated_by');
            $table->timestamp('deleted_at')->nullable()->after('deleted_by');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['crm_page_right', 'crm_role_type', 'created_by', 'updated_by', 'deleted_by', 'deleted_at']);
        });
    }
};
