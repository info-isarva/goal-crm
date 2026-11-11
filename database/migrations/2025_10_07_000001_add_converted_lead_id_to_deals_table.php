<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (! Schema::hasTable('deals')) {
            return;
        }
        Schema::table('deals', function (Blueprint $table) {
            if (! Schema::hasColumn('deals', 'converted_lead_id')) {
                $table->unsignedBigInteger('converted_lead_id')->nullable()->after('lead_source');
                $table->foreign('converted_lead_id')->references('id')->on('leads')->nullOnDelete();
            }
        });
    }
    public function down() {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['converted_lead_id']);
            $table->dropColumn('converted_lead_id');
        });
    }
};
