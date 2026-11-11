<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_category_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        $defaults = [
            ['name' => 'products', 'description' => 'Software / product offerings', 'sort_order' => 1],
            ['name' => 'services', 'description' => 'Service offerings', 'sort_order' => 2],
            ['name' => 'industries', 'description' => 'Industry categories', 'sort_order' => 3],
            ['name' => 'general', 'description' => 'General categories', 'sort_order' => 4],
        ];

        $now = now();
        foreach ($defaults as $row) {
            DB::table('product_category_types')->updateOrInsert(
                ['name' => $row['name']],
                [
                    'description' => $row['description'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // Import any other types already used on product categories
        if (Schema::hasTable('product_categories')) {
            $existing = DB::table('product_categories')
                ->whereNotNull('type')
                ->where('type', '!=', '')
                ->distinct()
                ->pluck('type');

            $sort = 10;
            foreach ($existing as $typeName) {
                $typeName = trim((string) $typeName);
                if ($typeName === '') {
                    continue;
                }
                DB::table('product_category_types')->updateOrInsert(
                    ['name' => $typeName],
                    [
                        'description' => null,
                        'sort_order' => $sort++,
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_category_types');
    }
};
