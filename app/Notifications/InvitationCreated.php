<?php

namespace App\Notifications;

use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;
use Illuminate\Notifications\Notification;
use App\Mail\Invitation;

class InvitationCreated extends Notification{

    public function via($notifiable){
        return $notifiable->type == "phone" ? [TwilioChannel::class] : ['mail'];
    }

    public function toTwilio($notifiable){
        return (new TwilioSmsMessage())
            ->content(__('invitations.invitation_message',[
                    'team' => $notifiable->team->name,
                    'user' => $notifiable->user->name
                ]
            ));
    }
    
    public function toMail($notifiable){
        return (new Invitation($notifiable))->to($notifiable->contact);
    }
    
}