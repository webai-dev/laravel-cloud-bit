<?php

namespace App\Billing\Managers\Stripe;

use App\Billing\Managers\CustomerManager;
use App\Exceptions\BillingException;
use App\Models\Teams\Team;
use Stripe\Card;
use Stripe\Customer;

class StripeCustomerManager extends BaseBillingManager implements CustomerManager {

    public function cards(Team $team) {
        if ($team->customer_code == null){
            return  [];
        }

        /* @var Customer $customer */
        $customer = Customer::retrieve($team->customer_code);
        $cards = [];

        if(!$customer->sources) return [];
        foreach ($customer->sources->data as $source) {
            if (!$source instanceof Card) {
                continue;
            }

            $cards[] = [
                'name'      => $source->name,
                'brand'     => $source->brand,
                'last_4'    => $source->last4,
                'exp_month' => $source->exp_month,
                'exp_year'  => $source->exp_year
            ];
        }
        return $cards;
    }

    public function create(Team $team, $token) {
        if ($team->customer_code != null) {
            throw new BillingException(__('billing.payment_method_already_created'), 400);
        }

        $customer = Customer::create([
            'source'   => $token['id'],
            'email'   => $token['email'],
            'shipping' => [
                'name' => $token['card']['name'],
                'address' => [
                    'line1' => $token['card']['address_line1'],
                    'city' => $token['card']['address_city'],
                    'postal_code' => $token['card']['address_zip'],
                    'country' =>$token['card']['address_country'],
                    'state' => $token['card']['address_state']
                ]
            ],
            'description' => $team->name
        ]);

        return $customer;
    }

    public function update(Team $team, $token) {
        /* @var Customer $customer */
        $customer = Customer::retrieve($team->customer_code);
        $customer->email = $token['email'];
        $customer->source = $token['id'];
        $customer->shipping = [
            'name' => $token['card']['name'],
            'address' => [
                'line1' => $token['card']['address_line1'],
                'city' => $token['card']['address_city'],
                'postal_code' => $token['card']['address_zip'],
                'country' =>$token['card']['address_country'],
                'state' => $token['card']['address_state']
            ]
        ];
        $customer->description = $team->name;
        $customer->save();

        return $customer;
    }

    public function delete(Team $team) {
        if ($team->customer_code == null) {
            return $team;
        }

        /* @var Customer $customer */
        $customer = Customer::retrieve($team->customer_code);
        $customer->delete();

        return $team;
    }
}