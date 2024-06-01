<?php

namespace App\Mail;

use App\Models\File;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Swift_Message;

class FilePublished extends Mailable {
    use Queueable, SerializesModels;

    protected $file;

    public function __construct(File $file, User $user) {
        $this->file = $file;
        $this->user = $user;
    }

    public function build() {
        $url = $this->file->getPublicUrl();

        return $this->view('emails.file_published', compact('url'))
            ->subject(__('emails.file_published_subject'))
            ->withSwiftMessage(function (Swift_Message $message) use ($url) {

                $merge_vars = [
                    'sender'    => $this->user->name,
                    'recipient' => 'there',
                    'link'      => $url,
                    'type'      => 'file',
                    'title'     => $this->file->title
                ];

                $headers = $message->getHeaders();
                $headers->addTextHeader('X-MC-Template', config('mail.templates.file_share'));
                $headers->addTextHeader('X-MC-MergeVars', json_encode($merge_vars));
            });
    }
}
