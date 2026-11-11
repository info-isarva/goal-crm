<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_ad_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20)->unique();
            $table->boolean('is_enabled')->default(false);
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->string('webhook_key', 64)->nullable();
            $table->string('connected_name')->nullable();
            $table->string('connected_external_id')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('social_ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20);
            $table->string('external_id', 100);
            $table->string('name')->nullable();
            $table->text('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_subscribed')->default(false);
            $table->json('meta')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['platform', 'external_id']);
        });

        Schema::create('social_ad_forms', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20);
            $table->foreignId('social_ad_account_id')->nullable()->constrained('social_ad_accounts')->nullOnDelete();
            $table->string('external_id', 100)->nullable();
            $table->string('token', 40)->nullable()->unique();
            $table->string('name');
            $table->string('status', 30)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->json('questions')->nullable();
            $table->json('field_mapping')->nullable();
            $table->unsignedBigInteger('lead_source_id')->nullable();
            $table->string('lead_status', 100)->nullable();
            $table->string('lead_priority', 10)->default('normal');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('default_category_ids', 500)->nullable();
            $table->unsignedInteger('leads_count')->default(0);
            $table->timestamp('last_lead_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['platform', 'external_id']);
        });

        Schema::create('social_ad_leads', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20);
            $table->foreignId('social_ad_form_id')->nullable()->constrained('social_ad_forms')->nullOnDelete();
            $table->string('external_id', 150)->nullable();
            $table->json('answers')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->boolean('is_test')->default(false);
            $table->string('campaign_name')->nullable();
            $table->string('adset_name')->nullable();
            $table->string('ad_name')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['platform', 'external_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_ad_leads');
        Schema::dropIfExists('social_ad_forms');
        Schema::dropIfExists('social_ad_accounts');
        Schema::dropIfExists('social_ad_integrations');
    }
};
