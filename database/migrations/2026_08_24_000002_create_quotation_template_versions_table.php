<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_template_id')
                ->constrained('quotation_templates')
                ->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('html_content');
            $table->longText('css_content')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['quotation_template_id', 'version'], 'qt_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_template_versions');
    }
};
