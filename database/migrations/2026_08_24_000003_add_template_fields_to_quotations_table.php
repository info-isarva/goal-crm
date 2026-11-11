<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'quotation_template_id')) {
                $table->foreignId('quotation_template_id')
                    ->nullable()
                    ->after('terms_conditions')
                    ->constrained('quotation_templates')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('quotations', 'quotation_template_version_id')) {
                $table->foreignId('quotation_template_version_id')
                    ->nullable()
                    ->after('quotation_template_id')
                    ->constrained('quotation_template_versions')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('quotations', 'template_html_snapshot')) {
                $table->longText('template_html_snapshot')->nullable()->after('quotation_template_version_id');
            }
            if (!Schema::hasColumn('quotations', 'template_css_snapshot')) {
                $table->longText('template_css_snapshot')->nullable()->after('template_html_snapshot');
            }
            if (!Schema::hasColumn('quotations', 'template_version_number')) {
                $table->unsignedInteger('template_version_number')->nullable()->after('template_css_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'quotation_template_version_id')) {
                $table->dropConstrainedForeignId('quotation_template_version_id');
            }
            if (Schema::hasColumn('quotations', 'quotation_template_id')) {
                $table->dropConstrainedForeignId('quotation_template_id');
            }
            if (Schema::hasColumn('quotations', 'template_html_snapshot')) {
                $table->dropColumn('template_html_snapshot');
            }
            if (Schema::hasColumn('quotations', 'template_css_snapshot')) {
                $table->dropColumn('template_css_snapshot');
            }
            if (Schema::hasColumn('quotations', 'template_version_number')) {
                $table->dropColumn('template_version_number');
            }
        });
    }
};
