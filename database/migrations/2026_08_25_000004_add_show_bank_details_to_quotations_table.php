<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'show_bank_details')) {
                $table->boolean('show_bank_details')->default(false)->after('project_detail');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'show_bank_details')) {
                $table->dropColumn('show_bank_details');
            }
        });
    }
};
