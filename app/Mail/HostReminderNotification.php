<?php

namespace App\Mail;

use App\Helpers\RelatedCrmLink;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HostReminderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $meeting;
    public $related;
    public $hostName;

    /**
     * @param  mixed  $meeting
     */
    public function __construct($meeting)
    {
        $this->meeting = $meeting;
        $this->related = RelatedCrmLink::fromEntity($meeting);

        $ownerId = is_array($meeting)
            ? ($meeting['user_owner_id'] ?? null)
            : ($meeting->user_owner_id ?? null);
        $host = $ownerId ? User::find($ownerId) : null;
        $this->hostName = $host->name ?? 'Host';
    }

    public function build()
    {
        return $this->subject('Meeting Reminder')
            ->view('emails.host_reminder_notification')
            ->with([
                'meeting' => $this->meeting,
                'related' => $this->related,
                'hostName' => $this->hostName,
            ]);
    }
}
