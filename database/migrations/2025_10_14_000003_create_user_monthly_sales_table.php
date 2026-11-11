<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('user_monthly_sales')) {
            return;
        }
        Schema::create('user_monthly_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('achieved_sales', 15, 2)->default(0);
            $table->decimal('sales_target', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'year', 'month']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_monthly_sales');
    }
};