<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deal stages are managed in the `stages` table and can be renamed
     * (e.g. Negotiation/Review → Negotiation/Invoice). An ENUM on deals.stage
     * rejects new names with "Data truncated for column 'stage'".
     */
    public function up(): void
    {
        if (!Schema::hasTable('deals') || !Schema::hasColumn('deals', 'stage')) {
            return;
        }

        // Change ENUM → VARCHAR first so new stage names are allowed
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE deals MODIFY stage VARCHAR(100) NULL');
        } else {
            Schema::table('deals', function ($table) {
                $table->string('stage', 100)->nullable()->change();
            });
        }

        // Normalize legacy enum value to the current stages.name
        DB::table('deals')
            ->where('stage', 'Negotiation/Review')
            ->update(['stage' => 'Negotiation/Invoice']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('deals') || !Schema::hasColumn('deals', 'stage')) {
            return;
        }

        DB::table('deals')
            ->where('stage', 'Negotiation/Invoice')
            ->update(['stage' => 'Negotiation/Review']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE deals MODIFY stage ENUM(
                'Qualification',
                'Needs Analysis',
                'Value Proposition',
                'Identify Decision Makers',
                'Proposal/Price Quote',
                'Negotiation/Review',
                'Closed Won',
                'Closed Lost'
            ) NULL");
        }
    }
};
