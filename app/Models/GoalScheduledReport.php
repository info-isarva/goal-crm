<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoalScheduledReport extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'overview' => 'Analytics Dashboard (Summary)',
        'leads' => 'Lead Analytics',
        'processing' => 'Processing',
        'disbursement' => 'Disbursement',
        'revenue' => 'Revenue & Payout',
        'performance' => 'Performance',
        'lead_performance' => 'MIS — Lead Performance',
        'loan_processing' => 'MIS — Loan Processing',
        'visit_followup' => 'MIS — Visit & Follow-up',
        'exception_report' => 'MIS — Exception Report',
        'case_summary' => 'MIS — Case Summary',
        'disbursement_summary' => 'MIS — Disbursement Summary',
        'revenue_ledger' => 'MIS — Revenue Ledger',
        'payout_ageing' => 'MIS — Payout Ageing',
        'branch_mis' => 'MIS — Branch MIS',
        'target_vs_achievement' => 'MIS — Target vs Achievement',
        'hierarchy_performance' => 'MIS — Hierarchy Performance',
        'lender_performance' => 'MIS — Lender Performance',
    ];

    public const FREQUENCIES = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'custom' => 'Custom',
    ];

    public const FORMATS = [
        'pdf' => 'PDF',
        'xlsx' => 'Excel (XLSX)',
        'csv' => 'CSV',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'paused' => 'Paused',
        'failed' => 'Failed',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'report_type',
        'frequency',
        'frequency_config',
        'run_time',
        'start_date',
        'end_date',
        'no_end_date',
        'format',
        'recipients',
        'filters',
        'status',
        'next_run_at',
        'last_run_at',
        'last_error',
    ];

    protected $casts = [
        'frequency_config' => 'array',
        'recipients' => 'array',
        'filters' => 'array',
        'no_end_date' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(GoalScheduledReportRun::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->report_type] ?? $this->report_type;
    }

    public function frequencyLabel(): string
    {
        return self::FREQUENCIES[$this->frequency] ?? $this->frequency;
    }

    public function formatLabel(): string
    {
        return self::FORMATS[$this->format] ?? strtoupper((string) $this->format);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function recipientsList(): array
    {
        return array_values(array_filter(array_map('trim', $this->recipients ?? [])));
    }
}
