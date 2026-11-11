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
        if (!Schema::hasTable('files')) {
            Schema::create('files', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->enum('file_type', ['file upload', 'file links']);
                $table->string('file_path', 255)->nullable();
                $table->string('file_name', 150);
                $table->string('related_type', 50)->nullable();
                $table->bigInteger('related_id')->nullable();
                $table->bigInteger('user_restored_id')->nullable();
                $table->bigInteger('user_owner_id')->nullable();
                $table->bigInteger('user_assigned_id')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
};
