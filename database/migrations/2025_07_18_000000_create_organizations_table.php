<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 150);
            $table->integer('industry_type');
            $table->string('website', 255)->nullable();
            $table->text('address')->nullable();
             $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('description', 255)->nullable();
            $table->bigInteger('number_of_employees')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->bigInteger('deleted_by')->unsigned()->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->bigInteger('user_owner_id')->unsigned()->nullable();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('user_owner_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
