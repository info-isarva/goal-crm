<?php

namespace App\Mail;

use App\Helpers\RelatedCrmLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MeetingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $meeting;
    public $user;
    public $related;

    /**
     * @param  mixed  $meeting
     * @param  mixed  $user
     */
    public function __construct($meeting, $user)
    {
        $this->meeting = $meeting;
        $this->user = $user;
        $this->related = RelatedCrmLink::fromEntity($meeting);
    }

    public function build()
    {
        return $this->subject('Meeting Reminder')
            ->view('emails.meeting_reminder')
            ->with([
                'meeting' => $this->meeting,
                'user' => $this->user,
                'related' => $this->related,
            ]);
    }
}
