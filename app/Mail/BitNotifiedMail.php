<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Models\Bits\Bit;

use App\Util\URL;

class BitNotifiedMail extends Mailable
{
    use Queueable, SerializesModels;
    
    protected $content;
    protected $bit;
    
    public function __construct(Bit $bit,$content,$subject = null)
    {
        $this->content = $content;
        $this->subject = $subject;
        $this->bit = $bit;
    }

    public function build()
    {
        $type = $this->bit->type->name;
        $subject = $this->subject == null ? __('bits.bit_notification_subject',['bit' => $this->bit->title]) : $this->subject;
        
        return $this->subject("[yBit.io - $type] $subject")
                    ->view('emails.default')
                    ->withSwiftMessage(function($message){

                        $url = URL::from(config('app.web_url'));
                        $url->path = 'bit/'.$this->bit->id;
                        $url->host = $this->bit->team->subdomain.".".$url->host;

                        $merge_vars = [
                            'content' => $this->content,
                            'link' => $url->__toString()
                        ];
                        
                        $headers = $message->getHeaders();
                        $headers->addTextHeader('X-MC-Template',config('mail.templates.bit_notification'));
                        $headers->addTextHeader('X-MC-MergeVars',json_encode($merge_vars));
                    });
    }
}
