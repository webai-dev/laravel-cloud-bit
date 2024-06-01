<?php

namespace App\Http\Controllers\Integrations\Webhooks;

use \Stripe\Webhook;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Billing\EventHandlers\Stripe\CustomerDeletedHandler;
use App\Billing\EventHandlers\Stripe\SubscriptionHandler;
use App\Billing\EventHandlers\Stripe\PaymentSucceededHandler;
use App\Billing\EventHandlers\Stripe\PaymentFailedHandler;

class StripeController extends Controller {

    public function request(Request $request){
        try {
            $event = Webhook::constructEvent(
                $request->getContent(), $request->header('Stripe-Signature'), config('services.stripe.secret')
            );

            switch($event->type){
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                case 'customer.subscription.deleted':
                    $handler = new SubscriptionHandler($event->data, $event->type);
                    $handler->handle();
                    break;
                case 'customer.deleted':
                    $handler = new CustomerDeletedHandler($event->data, $event->type);
                    $handler->handle();
                    break;
                case 'invoice.payment_succeeded':
                    $handler = new PaymentSucceededHandler($event->data, $event->type);
                    $handler->handle();
                    break;
                case 'payment.failed':
                    $handler = new PaymentFailedHandler($event->data, $event->type);
                    $handler->handle();
                    break;
            }
            return 'OK';

        } catch(\UnexpectedValueException $e) {
            throw new \Exception("Invalid stripe webhook payload");

        } catch(\Stripe\Error\SignatureVerification $e) {
            throw new \Exception("Invalid stripe webhook signature");
        }
    }
}
