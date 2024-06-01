<?php

namespace App\Notifications;

use App\Models\Bits\Bit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Mail\BitNotifiedMail;

class BitNotification extends Notification
{
    use Queueable;

    protected $bit;
    protected $content;
    protected $subject;

    /**
     * Create a new notification instance.
     * @param Bit $bit
     * @param string $content
     * @param string $subject
     * @return void
     */
    public function __construct($bit,$content,$subject = null)
    {
        $this->bit = $bit;
        $this->content = $content;
        $this->subject = $subject;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BitNotifiedMail
     */
    public function toMail($notifiable)
    {
        return (new BitNotifiedMail(
            $this->bit,
            $this->content,
            $this->subject
        ))->to($notifiable->email);
    }
}
