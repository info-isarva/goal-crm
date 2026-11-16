<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_org_units', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['region', 'cluster', 'branch']);
            $table->foreignId('parent_id')->nullable()->constrained('goal_org_units')->nullOnDelete();
            $table->string('name', 150);
            $table->string('code', 50)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_org_units');
    }
};
