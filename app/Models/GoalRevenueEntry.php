<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalRevenueEntry extends Model
{
    public const TYPE_RECOGNIZED = 'recognized';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_REVERSAL = 'reversal';

    public const STATUS_POSTED = 'posted';

    public const STATUS_PENDING = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    protected $table = 'goal_revenue_entries';

    protected $fillable = [
        'entry_ref',
        'goal_loan_case_id',
        'goal_disbursement_id',
        'parent_entry_id',
        'amount',
        'entry_type',
        'adjustment_direction',
        'status',
        'attributed_user_id',
        'goal_org_unit_id',
        'note',
        'reason',
        'evidence_path',
        'created_by',
        'posted_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'posted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $entry) {
            if (empty($entry->entry_type)) {
                $entry->entry_type = self::TYPE_RECOGNIZED;
            }
            if (empty($entry->status)) {
                $entry->status = self::STATUS_POSTED;
            }
            if (empty($entry->posted_at) && $entry->status !== self::STATUS_PENDING) {
                $entry->posted_at = now();
            }
            if (empty($entry->entry_ref)) {
                $entry->entry_ref = self::nextEntryRef();
            }
        });
    }

    public static function nextEntryRef(?\DateTimeInterface $at = null): string
    {
        $at = $at ?: now();
        $stamp = $at->format('ym');
        $prefix = 'RV-'.$stamp.'-';
        $last = static::query()
            ->where('entry_ref', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('entry_ref');
        $seq = 1;
        if ($last && preg_match('/(\d+)$/', (string) $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function loanCase(): BelongsTo
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function disbursement(): BelongsTo
    {
        return $this->belongsTo(GoalDisbursement::class, 'goal_disbursement_id');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(GoalOrgUnit::class, 'goal_org_unit_id');
    }

    public function attributedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attributed_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function parentEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_entry_id');
    }

    public function childEntries(): HasMany
    {
        return $this->hasMany(self::class, 'parent_entry_id');
    }

    public function isRecognized(): bool
    {
        $t = (string) ($this->entry_type ?: self::TYPE_RECOGNIZED);

        return $t === self::TYPE_RECOGNIZED || $t === '';
    }

    public function typeLabel(): string
    {
        return match ((string) ($this->entry_type ?: self::TYPE_RECOGNIZED)) {
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_REVERSAL => 'Reversal',
            default => 'Credit',
        };
    }

    public function statusLabel(): string
    {
        return match ((string) ($this->status ?: self::STATUS_POSTED)) {
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            default => 'Posted',
        };
    }

    /**
     * Remaining amount that can still be reversed against this credit.
     */
    public function reversibleRemaining(): float
    {
        if (! $this->isRecognized()) {
            return 0.0;
        }
        $credit = (float) $this->amount;
        $reversed = (float) $this->childEntries()
            ->where('entry_type', self::TYPE_REVERSAL)
            ->whereIn('status', [self::STATUS_POSTED, self::STATUS_APPROVED])
            ->sum('amount');

        // Reversals are negative; remaining = credit + sum(reversals)
        return max(0, round($credit + $reversed, 2));
    }
}
