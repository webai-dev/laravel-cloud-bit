<?php

namespace App\Billing\Managers\Stripe;

use App\Exceptions\BillingException;
use Stripe\Error\Base;

class BaseBillingManager {

    public function __call($name, $arguments) {
        try {
            return $name($arguments);
        } catch (Base $exception) {
            $code = $exception->getHttpStatus();
            $code = !is_null($code) && is_integer($code) && $code >= 100 ? $code : 500;

            $error = new BillingException($exception->getMessage(), $code);
            throw $error;
        }
    }
}