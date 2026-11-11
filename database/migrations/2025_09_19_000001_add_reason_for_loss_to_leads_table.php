<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (! Schema::hasTable('leads')) {
            return;
        }
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'reason_for_loss')) {
                $table->string('reason_for_loss', 255)->nullable()->after('status');
            }
        });
    }
    public function down() {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('reason_for_loss');
        });
    }
};
