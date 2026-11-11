<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_whatsapp_send_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('status', 20)->default('sent');
            $table->string('recipient', 50)->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message_preview')->nullable();
            $table->string('pdf_filename', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['quotation_id', 'created_at']);
        });

        Schema::create('quotation_email_send_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('status', 20)->default('sent');
            $table->string('recipient', 500)->nullable();
            $table->string('cc', 500)->nullable();
            $table->string('bcc', 500)->nullable();
            $table->string('subject', 255)->nullable();
            $table->string('from_address', 255)->nullable();
            $table->string('reply_to', 255)->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message_preview')->nullable();
            $table->string('pdf_filename', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['quotation_id', 'created_at']);
        });

        if (Schema::hasTable('quotation_send_logs')) {
            $legacy = DB::table('quotation_send_logs')->orderBy('id')->get();
            foreach ($legacy as $row) {
                if ($row->channel === 'whatsapp') {
                    $meta = $row->meta ? json_decode($row->meta, true) : [];
                    DB::table('quotation_whatsapp_send_logs')->insert([
                        'quotation_id'    => $row->quotation_id,
                        'status'          => $row->status,
                        'recipient'       => $row->recipient,
                        'sent_by'         => $row->sent_by,
                        'message_preview' => $row->message_preview,
                        'pdf_filename'    => $meta['pdf_filename'] ?? null,
                        'error_message'   => $row->error_message,
                        'meta'            => $row->meta,
                        'created_at'      => $row->created_at,
                        'updated_at'      => $row->updated_at,
                    ]);
                } elseif ($row->channel === 'email') {
                    $meta = $row->meta ? json_decode($row->meta, true) : [];
                    DB::table('quotation_email_send_logs')->insert([
                        'quotation_id'    => $row->quotation_id,
                        'status'          => $row->status,
                        'recipient'       => $row->recipient,
                        'cc'              => $row->cc,
                        'bcc'             => $row->bcc,
                        'subject'         => $row->subject,
                        'from_address'    => $row->from_address,
                        'reply_to'        => $meta['reply_to'] ?? null,
                        'sent_by'         => $row->sent_by,
                        'message_preview' => $row->message_preview,
                        'pdf_filename'    => $meta['pdf_filename'] ?? null,
                        'error_message'   => $row->error_message,
                        'meta'            => $row->meta,
                        'created_at'      => $row->created_at,
                        'updated_at'      => $row->updated_at,
                    ]);
                }
            }

            Schema::drop('quotation_send_logs');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_email_send_logs');
        Schema::dropIfExists('quotation_whatsapp_send_logs');

        Schema::create('quotation_send_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('status', 20)->default('sent');
            $table->string('recipient', 500)->nullable();
            $table->string('cc', 500)->nullable();
            $table->string('bcc', 500)->nullable();
            $table->string('subject', 255)->nullable();
            $table->string('from_address', 255)->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message_preview')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
};
