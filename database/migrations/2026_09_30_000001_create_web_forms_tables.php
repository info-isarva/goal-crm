<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('web_forms')) {
            Schema::create('web_forms', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('token', 64)->unique();
                $table->string('heading', 200)->nullable();
                $table->text('intro_text')->nullable();
                $table->json('fields');
                $table->string('submit_button_text', 60)->default('Submit');
                $table->text('success_message')->nullable();
                $table->string('redirect_url', 500)->nullable();
                $table->string('theme_color', 20)->default('#5b3cc4');
                $table->unsignedBigInteger('lead_source_id')->nullable();
                $table->string('lead_status', 100)->nullable();
                $table->string('lead_priority', 20)->default('normal');
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->string('default_category_ids', 255)->nullable();
                $table->text('allowed_domains')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('submissions_count')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('web_form_submissions')) {
            Schema::create('web_form_submissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('web_form_id');
                $table->unsignedBigInteger('lead_id')->nullable();
                $table->json('payload');
                $table->string('status', 20)->default('created');
                $table->text('error_message')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->string('page_url', 500)->nullable();
                $table->timestamps();

                $table->index(['web_form_id', 'created_at']);
                $table->foreign('web_form_id')->references('id')->on('web_forms')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('web_form_submissions');
        Schema::dropIfExists('web_forms');
    }
};
