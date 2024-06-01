<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class MagicLink extends Mailable {

    protected $url;

    public function __construct($url) {
        $this->url = $url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        return $this->subject(__('emails.login_subject'))
            ->view('emails.magiclink')
            ->with([
                'url' => $this->url
            ]);
    }
}
