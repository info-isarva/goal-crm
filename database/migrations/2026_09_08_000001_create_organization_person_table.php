<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_person', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('person_id');
            $table->timestamps();

            $table->unique(['organization_id', 'person_id']);
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('person_id')->references('id')->on('people')->cascadeOnDelete();
        });

        // Backfill from existing primary organization_id links
        $rows = DB::table('people')
            ->whereNotNull('organization_id')
            ->where('organization_id', '>', 0)
            ->select('id', 'organization_id', 'created_at', 'updated_at')
            ->get();

        $now = now();
        foreach ($rows as $row) {
            DB::table('organization_person')->insertOrIgnore([
                'organization_id' => $row->organization_id,
                'person_id' => $row->id,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $row->updated_at ?? $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_person');
    }
};
