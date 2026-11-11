<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'max_users')) {
                $table->unsignedInteger('max_users')->nullable()->after('country');
            }
            if (!Schema::hasColumn('companies', 'admin_assignable_permissions')) {
                $table->json('admin_assignable_permissions')->nullable()->after('max_users');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'admin_assignable_permissions')) {
                $table->dropColumn('admin_assignable_permissions');
            }
            if (Schema::hasColumn('companies', 'max_users')) {
                $table->dropColumn('max_users');
            }
        });
    }
};
