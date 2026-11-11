<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 150);
            $table->string('description', 250)->nullable();
            $table->integer('amount')->nullable();
            $table->enum('label', ['high', 'low', 'normal'])->nullable();
            $table->unsignedBigInteger('lead_source')->nullable();
            $table->text('category')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('people_id')->nullable();
            $table->decimal('value', 12, 2)->nullable();
            $table->enum('stage', [
                'Qualification',
                'Needs Analysis',
                'Value Proposition',
                'Identify Decision Makers',
                'Proposal/Price Quote',
                'Negotiation/Review',
                'Closed Won',
                'Closed Lost'
            ])->nullable();
            $table->enum('status', ['Open', 'Closed won', 'Closed lost'])->default('Open');
            $table->date('close_date')->nullable();
            $table->unsignedBigInteger('assigned_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals');
    }
};
