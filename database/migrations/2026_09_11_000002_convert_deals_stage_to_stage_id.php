<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store deals.stage_id (FK to stages.id) instead of stage name strings.
     */
    public function up(): void
    {
        if (!Schema::hasTable('deals')) {
            return;
        }

        if (!Schema::hasColumn('deals', 'stage_id')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->unsignedBigInteger('stage_id')->nullable()->after('value');
            });
        }

        if (Schema::hasColumn('deals', 'stage')) {
            // Map current stage names → stage ids
            if (DB::getDriverName() === 'mysql') {
                DB::statement('
                    UPDATE deals d
                    INNER JOIN stages s ON s.name = d.stage
                    SET d.stage_id = s.id
                    WHERE d.stage_id IS NULL
                ');

                // Legacy rename leftover
                DB::statement("
                    UPDATE deals d
                    INNER JOIN stages s ON s.name = 'Negotiation/Invoice'
                    SET d.stage_id = s.id
                    WHERE d.stage = 'Negotiation/Review' AND d.stage_id IS NULL
                ");
            } else {
                $stages = DB::table('stages')->pluck('id', 'name');
                foreach ($stages as $name => $id) {
                    DB::table('deals')->where('stage', $name)->whereNull('stage_id')->update(['stage_id' => $id]);
                }
                if (isset($stages['Negotiation/Invoice'])) {
                    DB::table('deals')->where('stage', 'Negotiation/Review')->whereNull('stage_id')->update(['stage_id' => $stages['Negotiation/Invoice']]);
                }
            }

            Schema::table('deals', function (Blueprint $table) {
                $table->dropColumn('stage');
            });
        }

        // Align stage_history legacy names
        if (Schema::hasTable('stage_history') && Schema::hasColumn('stage_history', 'stage_name')) {
            DB::table('stage_history')
                ->where('stage_name', 'Negotiation/Review')
                ->update(['stage_name' => 'Negotiation/Invoice']);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('deals')) {
            return;
        }

        if (!Schema::hasColumn('deals', 'stage')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->string('stage', 100)->nullable()->after('value');
            });
        }

        if (Schema::hasColumn('deals', 'stage_id')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('
                    UPDATE deals d
                    INNER JOIN stages s ON s.id = d.stage_id
                    SET d.stage = s.name
                ');
            } else {
                $stages = DB::table('stages')->pluck('name', 'id');
                foreach ($stages as $id => $name) {
                    DB::table('deals')->where('stage_id', $id)->update(['stage' => $name]);
                }
            }

            Schema::table('deals', function (Blueprint $table) {
                $table->dropColumn('stage_id');
            });
        }
    }
};
