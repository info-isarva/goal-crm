<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable();
            }
            if (! Schema::hasColumn('users', 'mobile')) {
                $table->string('mobile')->nullable();
            }
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable();
            }
            if (! Schema::hasColumn('users', 'state')) {
                $table->string('state')->nullable();
            }
            if (! Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable();
            }
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['address', 'mobile', 'city', 'state', 'country']);
        });
    }
};
