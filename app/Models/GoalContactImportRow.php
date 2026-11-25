<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalContactImportRow extends Model
{
    protected $table = 'goal_contact_import_rows';

    protected $fillable = [
        'goal_contact_import_id',
        'row_number',
        'payload',
        'goal_loan_lead_id',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(GoalContactImport::class, 'goal_contact_import_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(GoalLoanLead::class, 'goal_loan_lead_id');
    }
}
