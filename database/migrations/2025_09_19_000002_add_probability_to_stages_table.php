<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (! Schema::hasTable('stages')) {
            return;
        }
        Schema::table('stages', function (Blueprint $table) {
            if (! Schema::hasColumn('stages', 'probability')) {
                $table->integer('probability')->nullable()->after('name');
            }
        });
    }
    public function down() {
        Schema::table('stages', function (Blueprint $table) {
            $table->dropColumn('probability');
        });
    }
};
