<?php

namespace App\Billing\Managers;


use App\Models\Teams\Team;

interface InvoiceManager {

    public function index(Team $team, $from = null, $to = null);

    public function pay($invoice_invoice_id);
}