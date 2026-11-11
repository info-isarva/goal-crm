<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('stage_history')) {
            return;
        }
        Schema::create('stage_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->string('stage_name');
            $table->decimal('amount', 15, 2)->nullable();
            $table->integer('probability')->nullable();
            $table->date('close_date')->nullable();
            $table->timestamp('modified_time')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade');
            $table->foreign('modified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stage_history');
    }
};
