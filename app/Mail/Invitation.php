<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Teams\Invitation as InvitationModel;

class Invitation extends Mailable
{
    use Queueable, SerializesModels;

    protected $invitation;
    
    public function __construct(InvitationModel $invitation)
    {
        $this->invitation = $invitation;
    }

    public function build()
    {
        return $this->subject(__('emails.invitation_subject'))
                    ->view('emails.invitation',['invitation' => $this->invitation])
                    ->withSwiftMessage(function($message){
                        $merge_vars = [
                            'username' => $this->invitation->user->name,
                            'team_name' => $this->invitation->team->name,
                            'login_link' => 'ybit.io'
                        ];
                        
                        $headers = $message->getHeaders();
                        $headers->addTextHeader('X-MC-Template',config('mail.templates.invitation'));
                        $headers->addTextHeader('X-MC-MergeVars',json_encode($merge_vars));
                    });
    }
}
