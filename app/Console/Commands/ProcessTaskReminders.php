<?php

namespace App\Console\Commands;

use App\Mail\TaskReminderMail;
use App\Mail\MeetingReminderMail;
use App\Models\TaskReminder;
use App\Models\MeetingReminder;
use App\Notifications\TaskReminderNotification;
use App\Notifications\MeetingReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Services\FirebaseNotificationService;
use App\Models\Lead;
use App\Models\Deal;

class ProcessTaskReminders extends Command
{
    protected $signature = 'task:process-reminders';
    protected $description = 'Process and send pending task & meeting reminders';

    public function handle()
    {
        $this->info('Processing reminders...');
        $now = Carbon::now();
        $next30Minutes = $now->copy()->addMinutes(30);

        /*
        |--------------------------------------------------------------------------
        | TASK REMINDERS
        |--------------------------------------------------------------------------
        */

        $taskReminders = TaskReminder::where('remind_at', '<=', $now)
            ->whereHas('task')
            ->whereHas('user')
            ->where(function ($query) {
                $query->where('email_sent', false)
                      ->orWhere('notification_sent', false);
            })
            ->with(['task', 'user'])
            ->get();

        $this->info("Task reminders found: " . $taskReminders->count());

        foreach ($taskReminders as $reminder) {
            try {

                if (!$reminder->task || !$reminder->user) {
                    $this->error("Reminder {$reminder->id} missing task or user.");
                    continue;
                }

                // Email
                if (
                    !$reminder->email_sent &&
                    in_array($reminder->reminder_type, ['email', 'both'])
                ) {
                    $this->sendTaskEmail($reminder);
                }

                // In-app Notification
                if (
                    !$reminder->notification_sent &&
                    in_array($reminder->reminder_type, ['notification', 'both'])
                ) {
                    $user = $reminder->user; // Assuming TaskReminder has a 'user' relationship
                    if ($user && !empty($user->fcm_token)) {
                        $taskTitle = $reminder->task->name;
                        $related_id = $reminder->task->related_id;
                        $related_type = $reminder->task->related_type;
                        if($related_type === 'lead') {
                            $leadOrDealTitle = $related_id ? Lead::find($related_id)->title : 'N/A';
                        } elseif($related_type === 'deal') {
                            $leadOrDealTitle = $related_id ? Deal::find($related_id)->title : 'N/A';
                        } else {
                            $leadOrDealTitle = 'N/A';
                        }

                        $notificationService = app(FirebaseNotificationService::class);
                        $notificationService->sendNotification(
                            $user->fcm_token,
                            "Task Reminder",
                            "Reminder: Your task '{$taskTitle}' (" . ($related_type === 'lead' ? 'Lead' : 'Deal') . ": '{$leadOrDealTitle}') is due.",
                            [
                                'type' => 'task',
                                'task_id' => (string)$reminder->task->id,
                                'related_id' => (string)$related_id,
                                'related_type' => $related_type
                            ]
                        );
                    }
                    $this->sendTaskNotification($reminder);
                }

            } catch (\Exception $e) {
                $this->error("Task reminder {$reminder->id} failed: " . $e->getMessage());
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MEETING REMINDERS
        |--------------------------------------------------------------------------
        */

        $meetingReminders = MeetingReminder::where('remind_at', '<=', $now)
            ->whereHas('meeting')
            ->whereHas('user')
            ->where(function ($query) {
                $query->where('email_sent', false)
                      ->orWhere('notification_sent', false);
            })
            ->with(['meeting', 'user'])
            ->get();

        $this->info("Meeting reminders found: " . $meetingReminders->count());

        foreach ($meetingReminders as $reminder) {
            try {

                if (!$reminder->meeting || !$reminder->user) {
                    $this->error("Meeting reminder {$reminder->id} missing meeting or user.");
                    continue;
                }

                // Email
                if (
                    !$reminder->email_sent &&
                    in_array($reminder->reminder_type, ['email', 'both'])
                ) {
                    $this->sendMeetingEmail($reminder);
                }

                // In-app Notification
                if (
                    !$reminder->notification_sent &&
                    in_array($reminder->reminder_type, ['notification', 'both'])
                ) {
                    $user = $reminder->user; // Assuming MeetingReminder has a 'user' relationship
                    if ($user && !empty($user->fcm_token)) {
                        $related_id = $reminder->meeting->related_id;
                        $related_type = $reminder->meeting->related_type;
                        if($related_type === 'lead') {
                            $leadOrDealTitle = $related_id ? Lead::find($related_id)->title : 'N/A';
                            $id = $related_id ? Lead::find($related_id)->id : null;
                        } elseif($related_type === 'deal') {
                            $leadOrDealTitle = $related_id ? Deal::find($related_id)->title : 'N/A';
                            $id = $related_id ? Deal::find($related_id)->id : null;
                        } else {
                            $leadOrDealTitle = 'N/A';
                            $id = null;
                        }
                        $notificationService = app(FirebaseNotificationService::class);
                        $notificationService->sendNotification(
                            $user->fcm_token,
                            "Meeting Reminder",
                            "Reminder: Your meeting '{$reminder->meeting->name}' (" . ($related_type === 'lead' ? 'Lead' : 'Deal') . ": '{$leadOrDealTitle}') is due.",
                            [
                                'type' => 'meeting',
                                'meeting_id' => (string)$reminder->meeting->id,
                                'related_id' => (string)$id,
                                'related_type' => $related_type
                            ]
                        );
                    }
                    $this->sendMeetingNotification($reminder);
                }

            } catch (\Exception $e) {
                $this->error("Meeting reminder {$reminder->id} failed: " . $e->getMessage());
            }
        }

        $this->info('All reminders processed successfully.');
        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | TASK EMAIL
    |--------------------------------------------------------------------------
    */

    private function sendTaskEmail(TaskReminder $reminder)
    {
        try {

            if (!$reminder->user->email) {
                return;
            }

            $forceSendNow = env('REMINDER_FORCE_SEND_NOW', false);

            if ($forceSendNow) {
                Mail::to($reminder->user->email)
                    ->send(new TaskReminderMail($reminder->task, $reminder->user));
                $this->info("✓ Task email sent immediately ({$reminder->id})");
            } else {
                Mail::to($reminder->user->email)
                    ->queue(new TaskReminderMail($reminder->task, $reminder->user));
                $this->info("✓ Task email queued ({$reminder->id})");
            }

            $reminder->update([
                'email_sent' => true,
                'email_sent_at' => Carbon::now(),
            ]);

        } catch (\Exception $e) {
            $this->error("Task email failed ({$reminder->id}): " . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TASK NOTIFICATION
    |--------------------------------------------------------------------------
    */

    private function sendTaskNotification(TaskReminder $reminder)
    {
        try {

            Notification::sendNow(
                $reminder->user,
                new TaskReminderNotification($reminder)
            );

            $reminder->update([
                'notification_sent' => true,
                'notification_sent_at' => Carbon::now(),
            ]);

            $this->info("✓ Task notification sent ({$reminder->id})");

        } catch (\Exception $e) {
            $this->error("Task notification failed ({$reminder->id}): " . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MEETING EMAIL
    |--------------------------------------------------------------------------
    */

    private function sendMeetingEmail(MeetingReminder $reminder)
    {
        try {

            if (!$reminder->user->email) {
                return;
            }

            Mail::to($reminder->user->email)
                ->queue(new MeetingReminderMail($reminder->meeting, $reminder->user));

            $reminder->update([
                'email_sent' => true,
                'email_sent_at' => Carbon::now(),
            ]);

            $this->info("✓ Meeting email queued ({$reminder->id})");

        } catch (\Exception $e) {
            $this->error("Meeting email failed ({$reminder->id}): " . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MEETING NOTIFICATION
    |--------------------------------------------------------------------------
    */

    private function sendMeetingNotification(MeetingReminder $reminder)
    {
        try {
            // Ensure only the current meeting ID is used
            $meeting = $reminder->meeting;

            if (!$meeting || !$reminder->user) {
                $this->error("Meeting or user not found for reminder ({$reminder->id})");
                return;
            }

            Notification::sendNow(
                $reminder->user,
                new MeetingReminderNotification($meeting, $reminder)
            );

            $reminder->update([
                'notification_sent' => true,
                'notification_sent_at' => Carbon::now(),
            ]);

            $this->info("✓ Meeting notification sent ({$reminder->id})");

        } catch (\Exception $e) {
            $this->error("Meeting notification failed ({$reminder->id}): " . $e->getMessage());
        }
    }
}
