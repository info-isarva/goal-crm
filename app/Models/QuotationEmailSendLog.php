<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationEmailSendLog extends Model
{
    protected $fillable = [
        'quotation_id',
        'status',
        'recipient',
        'cc',
        'bcc',
        'subject',
        'from_address',
        'reply_to',
        'sent_by',
        'message_preview',
        'pdf_filename',
        'error_message',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function isSuccess(): bool
    {
        return $this->status === 'sent';
    }
}
