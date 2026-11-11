<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('finish_at');
            $table->string('venue', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('related_type', 50);
            $table->bigInteger('related_id');
            $table->text('user_restored_id')->nullable();
            $table->bigInteger('user_owner_id')->nullable();
            $table->bigInteger('user_assigned_id')->nullable();
            $table->text('meet_link')->nullable();
            $table->string('google_event_id')->nullable();
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

    public function down()
    {
        Schema::dropIfExists('meetings');
    }
};
