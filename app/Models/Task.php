<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'due_at',
        'related_type',
        'related_id',
        'user_owner_id',
        'user_assigned_id',
        'priority',
        'status',
        'created_by',
        'reminder_notifications_enabled',
        'updated_by',
        'deleted_by',
        'completed_at',
    ];

    protected $dates = ['due_at', 'completed_at', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'reminder_notifications_enabled' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_owner_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'user_assigned_id');
    }

    public function reminders()
    {
        return $this->hasMany(TaskReminder::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Create a reminder 30 minutes before the task is due
     */
    public static function boot()
    {
        parent::boot();

        static::created(function ($task) {
            $task->createReminder();
        });

        static::updated(function ($task) {
            // If due_at changed, recreate the reminder
            if ($task->isDirty('due_at')) {
                $task->reminders()->delete();
                $task->createReminder();
            }
        });
    }

    public function createReminder($offsetMinutes = 30)
    {
        if (!$this->due_at || !$this->reminder_notifications_enabled) {
            return;
        }

        // Parse due_at as Carbon to be safe, then subtract offset minutes
        $due = $this->due_at instanceof \Carbon\Carbon ? $this->due_at : Carbon::parse($this->due_at);
        $remindAt = $due->copy()->subMinutes(intval($offsetMinutes));

        // Create reminder for the owner
        if ($this->user_owner_id) {
            TaskReminder::create([
                'task_id' => $this->id,
                'user_id' => $this->user_owner_id,
                'remind_at' => $remindAt,
                'reminder_type' => 'both', // Send both email and notification
            ]);
        }

        // Also create reminder for assigned user if different
        if ($this->user_assigned_id && $this->user_assigned_id != $this->user_owner_id) {
            TaskReminder::create([
                'task_id' => $this->id,
                'user_id' => $this->user_assigned_id,
                'remind_at' => $remindAt,
                'reminder_type' => 'both',
            ]);
        }
    }

    public function scopeFilterByDate($query, $startDate, $endDate)
    {
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();

        return $query->whereBetween('due_at', [$startDateTime, $endDateTime])
            ->orWhereHas('reminders', function ($query) use ($startDateTime, $endDateTime) {
                $query->whereBetween('remind_at', [$startDateTime, $endDateTime]);
            });
    }

    /**
     * Get the related deal for the task.
     */
    public function deal()
    {
        return $this->morphTo(__FUNCTION__, 'related_type', 'related_id');
    }

    /**
     * Enable or disable reminder notifications for the task.
     */
    public function enableReminderNotifications(bool $enable)
    {
        $this->reminder_notifications_enabled = $enable;
        $this->save();
    }
}
