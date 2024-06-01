<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Onboarding extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user){
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(__('emails.onboarding_subject'))
                    ->view('emails.default')
                    ->withSwiftMessage(function($message){
                        $merge_vars = [
                            'username' => $this->user->name
                        ];
                        
                        $headers = $message->getHeaders();
                        $headers->addTextHeader('X-MC-Template',config('mail.templates.onboarding'));
                        $headers->addTextHeader('X-MC-MergeVars',json_encode($merge_vars));
                    });
    }
}
