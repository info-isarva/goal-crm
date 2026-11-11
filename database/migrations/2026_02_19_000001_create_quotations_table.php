<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quote_no')->unique();
            $table->string('subject')->nullable();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('people_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->date('valid_until');
            $table->enum('status', ['Draft', 'Negotiation', 'Delivered', 'On Hold', 'Confirmed', 'Closed Won', 'Closed Lost'])->default('Draft');
            
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('adjustment', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2);
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->bigInteger('deleted_by')->unsigned()->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};