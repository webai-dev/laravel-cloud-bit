<?php

namespace App\Mail;

use App\Models\Teams\SubscriptionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionRequested extends Mailable {
    use Queueable, SerializesModels;

    protected $request;

    public function __construct(SubscriptionRequest $request) {
        $this->request = $request;
    }

    public function build() {
        return $this
            ->view('emails.subscription_request', ['request' => $this->request])
            ->subject(__('emails.sub_request_subject'));
    }
}
