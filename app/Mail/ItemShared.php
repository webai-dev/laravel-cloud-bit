<?php

namespace App\Mail;

use App\Models\Share;
use App\Util\URL;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ItemShared extends Mailable
{
    use Queueable, SerializesModels;

    protected $share;
    
    public function __construct(Share $share){
        $this->share = $share;
    }

    public function build(){
        $url = URL::from(config('app.web_url'));

        $type = $this->share->shareable_type;
        $id = $this->share->shareable_id;

        $url->path = "/$type/$id";
        $url->host = $this->share->team->subdomain.".".$url->host;

        return $this->view('emails.default')
                    ->subject(__('emails.item_shared_subject',['type' => $type]))
                    ->withSwiftMessage(function(\Swift_Message $message) use($url){
                        
                        $share = $this->share;
                        $merge_vars = [
                            'sender'    => $share->creator->name,
                            'recipient' => $share->recipient->name,
                            'link'      => $url->__toString(),
                            'type'      => $share->shareable_type,
                            'title'     => $share->shareable->title
                        ];
                        
                        $headers = $message->getHeaders();
                        $headers->addTextHeader('X-MC-Template',config('mail.templates.file_share'));
                        $headers->addTextHeader('X-MC-MergeVars',json_encode($merge_vars));
                    });
    }
}
