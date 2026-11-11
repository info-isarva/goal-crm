<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'proposal_version')) {
                $table->unsignedInteger('proposal_version')->default(1)->after('deal_id');
            }
            if (!Schema::hasColumn('quotations', 'project_detail')) {
                $table->longText('project_detail')->nullable()->after('terms_conditions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'proposal_version')) {
                $table->dropColumn('proposal_version');
            }
            if (Schema::hasColumn('quotations', 'project_detail')) {
                $table->dropColumn('project_detail');
            }
        });
    }
};
