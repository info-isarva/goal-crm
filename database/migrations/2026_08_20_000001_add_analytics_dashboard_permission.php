<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $parentId = DB::table('parent_permissions')
            ->where('name', 'Analytics Reports')
            ->value('id');

        DB::table('permissions')->updateOrInsert(
            ['guard_name' => 'view_analytics_dashboard_guard'],
            [
                'parent_id' => $parentId,
                'name' => 'Analytics Dashboard',
                'description' => 'Allows access to the interactive analytics dashboard.',
                'crm_permission' => 1,
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('guard_name', 'view_analytics_dashboard_guard')
            ->delete();
    }
};