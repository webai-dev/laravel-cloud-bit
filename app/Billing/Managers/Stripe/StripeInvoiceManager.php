<?php

namespace App\Billing\Managers\Stripe;

use App\Billing\Managers\InvoiceManager;
use App\Models\Teams\Team;
use Stripe\Customer;
use Stripe\Invoice;

class StripeInvoiceManager extends BaseBillingManager implements InvoiceManager {

    public function index(Team $team, $from = null, $to = null) {
        if ($team->customer_code == null){
            return  [];
        }

        /* @var Customer $customer */
        $customer = Customer::retrieve($team->customer_code);

        /** @var Invoice $invoice */
        $invoices = $from != null ? $customer->invoices([
            'starting_after' => $from
        ])->data : $customer->invoices()->data;

        $response = [];

        foreach ($invoices as $invoice) {
            $response[] = [
                'id'         => $invoice->id,
                'total'      => $invoice->total,
                'url'        => $invoice->invoice_pdf,
                'status'     => $invoice->attempted ? ($invoice->paid ? "paid" : "overdue") : "sent",
                'created_at' => $invoice->date
            ];
        };
        return $response;
    }

    public function pay($invoice_id) {
        $invoice = Invoice::retrieve($invoice_id);
        $invoice->pay();
    }
}