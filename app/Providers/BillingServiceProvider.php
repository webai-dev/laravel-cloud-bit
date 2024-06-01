<?php

namespace App\Providers;

use App\Billing\Managers\CustomerManager;
use App\Billing\Managers\InvoiceManager;
use App\Billing\Managers\PlanManager;
use App\Billing\Managers\ProductManager;
use App\Billing\Managers\Stripe\StripeCustomerManager;
use App\Billing\Managers\Stripe\StripeInvoiceManager;
use App\Billing\Managers\Stripe\StripePlanManager;
use App\Billing\Managers\Stripe\StripeProductManager;
use App\Billing\Managers\Stripe\StripeSubscriptionManager;
use App\Billing\Managers\SubscriptionManager;
use Illuminate\Support\ServiceProvider;
use Stripe\Stripe;

class BillingServiceProvider extends ServiceProvider {
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        Stripe::setApiKey(config('services.stripe.key'));
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(CustomerManager::class, function ($app) {
            return new StripeCustomerManager();
        });

        $this->app->singleton(SubscriptionManager::class, function ($app) {
            return new StripeSubscriptionManager();
        });

        $this->app->singleton(ProductManager::class, function ($app) {
            return new StripeProductManager();
        });

        $this->app->singleton(PlanManager::class, function ($app) {
            return new StripePlanManager();
        });

        $this->app->singleton(InvoiceManager::class, function ($app) {
            return new StripeInvoiceManager();
        });
    }
}
