<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_tax', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('product_id')->unsigned();
            $table->bigInteger('tax_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('tax_id')->references('id')->on('tax_rates')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tax');
    }
};
