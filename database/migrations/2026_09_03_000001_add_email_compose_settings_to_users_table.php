<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_compose_font_family')) {
                $table->string('email_compose_font_family', 100)->nullable()->after('avatar');
            }
            if (!Schema::hasColumn('users', 'email_compose_font_size')) {
                $table->unsignedSmallInteger('email_compose_font_size')->nullable()->after('email_compose_font_family');
            }
            if (!Schema::hasColumn('users', 'email_signature_name')) {
                $table->string('email_signature_name', 100)->nullable()->after('email_compose_font_size');
            }
            if (!Schema::hasColumn('users', 'email_signature_html')) {
                $table->longText('email_signature_html')->nullable()->after('email_signature_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'email_compose_font_family',
                'email_compose_font_size',
                'email_signature_name',
                'email_signature_html',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
