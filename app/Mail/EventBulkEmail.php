<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EventBulkEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The event instance.
     *
     * @var Event
     */
    public $event;

    /**
     * The user instance.
     *
     * @var User
     */
    public $user;

    /**
     * The subject.
     *
     */
    public $subject;

    /**
     * The message.
     *
     */
    public $content;


    /**
     * Create a new message instance.
     *
     * @param Event $event
     * @param User $user
     * @param $subject
     * @param $content
     */
    public function __construct(Event $event, User $user, $subject, $content)
    {
        $this->event = $event;
        $this->user = $user;
        $this->subject = $subject;
        $this->content = $content;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.event.bulkEmail');
    }
}