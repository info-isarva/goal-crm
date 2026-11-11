<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Meeting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'start_at',
        'finish_at',
        'venue',
        'location',
        'related_type',
        'related_id',
        'user_restored_id',
        'user_owner_id',
        'user_assigned_id',
        'meet_link',
        'google_event_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    protected $dates = [
        'start_at',
        'finish_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_owner_id');
    }
    
    public function person()
    {
        return $this->belongsTo(\App\Models\Person::class, 'user_assigned_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'related_id');
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'related_id');
    }

    public function participants()
    {
        return $this->hasMany(Participant::class, 'meeting_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function createReminder($offsetMinutes = 30)
    {
        if (!$this->start_at) {
            return;
        }

        // Parse start_at as Carbon to be safe, then subtract offset minutes
        $due = $this->start_at instanceof \Carbon\Carbon ? $this->start_at : Carbon::parse($this->start_at);
        $remindAt = $due->copy()->subMinutes(intval($offsetMinutes));

        // Create reminder for the owner
        if ($this->user_owner_id) {
            MeetingReminder::create([
                'meeting_id' => $this->id,
                'user_id' => $this->user_owner_id,
                'user_type' => 'host',
                'remind_at' => $remindAt,
                'reminder_type' => 'both', // Send both email and notification
            ]);
        }

        // Also create reminder for assigned user if different
        // if ($this->user_assigned_id && $this->user_assigned_id != $this->user_owner_id) {
        //     MeetingReminder::create([
        //         'meeting_id' => $this->id,
        //         'user_id' => $this->user_assigned_id,
        //         'remind_at' => $remindAt,
        //         'reminder_type' => 'both',
        //     ]);
        // }
    }

    public function reminders()
    {
        return $this->hasMany(MeetingReminder::class);
    }
}
