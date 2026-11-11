<?php

namespace App\Mail;

use App\Helpers\RelatedCrmLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MeetingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $meeting;
    public $related;

    /**
     * @param  mixed  $meeting
     */
    public function __construct($meeting)
    {
        $this->meeting = $meeting;
        $this->related = RelatedCrmLink::fromEntity($meeting);
    }

    public function build()
    {
        return $this->subject('New Meeting Notification')
            ->view('emails.meeting_notification')
            ->with([
                'meeting' => $this->meeting,
                'related' => $this->related,
            ]);
    }
}
