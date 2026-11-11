<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (Schema::hasTable('call_logs')) {
            return;
        }
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('industry')->nullable();
            $table->string('address')->nullable();
            $table->string('mobile_number');
            $table->string('email')->nullable();
            $table->string('requirement')->nullable();
            $table->string('estimated_budget')->nullable();
            $table->enum('call_status', ['Answered', 'Not Answered', 'Busy', 'Switch Off', 'Not Exist', 'Not reachable', 'Wrong number'])->nullable();
            $table->enum('lead_status', ['Interested', 'Not Interested', 'Call Later', 'Follow Up', 'Closed','Share the Details'])->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->text('next_action')->nullable();
            $table->text('remarks')->nullable();
            $table->string('source')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes('deleted_at', 0);
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('call_logs');
    }
};
