<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove legacy Manager (id 2) and Employee (id 3) CRM roles.
     * Goal hierarchy uses Admin (1) + roles 4–14 only.
     */
    public function up(): void
    {
        $legacyIds = [2, 3];

        DB::table('role_has_permission')->whereIn('role_id', $legacyIds)->delete();

        DB::table('roles')
            ->whereIn('id', $legacyIds)
            ->orWhereIn('name', ['Manager', 'Employee'])
            ->delete();
    }

    public function down(): void
    {
        $defaults = [
            2 => ['name' => 'Manager', 'description' => 'Team manager with reporting scope'],
            3 => ['name' => 'Employee', 'description' => 'Standard CRM user'],
        ];

        foreach ($defaults as $id => $data) {
            $exists = DB::table('roles')->where('id', $id)->orWhere('name', $data['name'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('roles')->insert([
                'id' => $id,
                'name' => $data['name'],
                'description' => $data['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
