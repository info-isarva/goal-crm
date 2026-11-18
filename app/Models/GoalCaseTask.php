<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class GoalCaseTask extends Model
{
    public const TYPE_VISIT = 'visit';

    public const TYPE_FOLLOWUP = 'followup';

    public const STATUS_OPEN = 'Open';

    public const STATUS_COMPLETED = 'Completed';

    public const STATUS_CANCELLED = 'Cancelled';

    protected $table = 'goal_case_tasks';

    protected $fillable = [
        'goal_loan_case_id', 'title', 'task_type', 'status',
        'due_at', 'completed_at', 'completed_by',
        'assigned_to', 'created_by', 'notes', 'outcome', 'location',
        'escalated_level', 'escalated_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isOverdue(?CarbonInterface $now = null): bool
    {
        if (! $this->isOpen() || ! $this->due_at) {
            return false;
        }
        $now = $now ?: now();

        return $this->due_at->lt($now);
    }

    public function isDueToday(?CarbonInterface $now = null): bool
    {
        if (! $this->isOpen() || ! $this->due_at) {
            return false;
        }
        $now = $now ?: now();

        return $this->due_at->isSameDay($now);
    }

    public function isUpcoming(?CarbonInterface $now = null): bool
    {
        if (! $this->isOpen() || ! $this->due_at) {
            return false;
        }
        $now = $now ?: now();

        return $this->due_at->gt($now->copy()->endOfDay());
    }

    /** UI status: Scheduled | Upcoming | Overdue | Completed | Cancelled */
    public function displayStatus(?CarbonInterface $now = null): string
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 'Completed';
        }
        if ($this->status === self::STATUS_CANCELLED) {
            return 'Cancelled';
        }
        if ($this->isOverdue($now)) {
            return 'Overdue';
        }
        if ($this->isDueToday($now)) {
            return 'Scheduled';
        }
        if ($this->isUpcoming($now)) {
            return 'Upcoming';
        }

        return 'Scheduled';
    }

    public function typeLabel(): string
    {
        return match ($this->task_type) {
            self::TYPE_VISIT => 'Customer Visit',
            self::TYPE_FOLLOWUP => 'Follow-up',
            'document' => 'Document',
            default => ucfirst((string) $this->task_type),
        };
    }

    public function escalationLabel(): string
    {
        return match ($this->escalated_level) {
            'bm' => 'Escalated to BM',
            'cluster' => 'Escalated to Cluster Head',
            default => '—',
        };
    }

    /**
     * Refresh escalation based on overdue age (BM ≥1 day, Cluster ≥3 days).
     */
    public function syncEscalation(?CarbonInterface $now = null): bool
    {
        $now = $now ?: now();
        if (! $this->isOverdue($now) || ! $this->due_at) {
            return false;
        }

        $days = $this->due_at->diffInDays($now);
        $level = null;
        if ($days >= 3) {
            $level = 'cluster';
        } elseif ($days >= 1) {
            $level = 'bm';
        }

        if (! $level) {
            return false;
        }

        $changed = $this->escalated_level !== $level;
        if ($changed || ! $this->escalated_at) {
            $this->escalated_level = $level;
            $this->escalated_at = $this->escalated_at ?: $now;
            $this->save();
        }

        return $changed;
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_OPEN);
    }

    public function scopeVisitTypes(Builder $q): Builder
    {
        return $q->whereIn('task_type', [self::TYPE_VISIT, self::TYPE_FOLLOWUP]);
    }

    public function scopeOverdue(Builder $q, ?CarbonInterface $now = null): Builder
    {
        $now = $now ?: now();

        return $q->open()->whereNotNull('due_at')->where('due_at', '<', $now);
    }

    public function scopeDueToday(Builder $q, ?CarbonInterface $now = null): Builder
    {
        $now = $now ?: now();

        return $q->open()->whereBetween('due_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()]);
    }

    public function scopeUpcoming(Builder $q, ?CarbonInterface $now = null): Builder
    {
        $now = $now ?: now();

        return $q->open()->where('due_at', '>', $now->copy()->endOfDay());
    }

    public function scopeCompletedThisMonth(Builder $q, ?CarbonInterface $now = null): Builder
    {
        $now = $now ?: now();

        return $q->where('status', self::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
    }
}
