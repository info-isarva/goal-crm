<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('product_name', 150);
            $table->string('product_code', 150);
            $table->text('product_description')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->integer('product_status', false, true)->default(1);
            $table->integer('product_category_id')->nullable();
            $table->integer('commission_rate', false, true)->nullable();
            $table->boolean('tax_status')->default(0);
            $table->bigInteger('product_owner_id')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->bigInteger('deleted_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
