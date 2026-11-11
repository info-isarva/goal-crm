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
            if (! Schema::hasColumn('deals', 'reason_for_loss')) {
                $table->string('reason_for_loss', 255)->nullable()->after('stage');
            }
        });
    }
    public function down() {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn('reason_for_loss');
        });
    }
};
