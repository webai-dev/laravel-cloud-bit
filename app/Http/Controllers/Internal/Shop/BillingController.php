<?php

namespace App\Http\Controllers\Internal\Shop;

use App\Billing\Managers\CustomerManager;
use App\Billing\Managers\InvoiceManager;
use App\Http\Controllers\Controller;
use App\Models\Teams\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BillingController extends Controller {
    protected $customers, $invoices;

    public function __construct(CustomerManager $customers, InvoiceManager $invoices) {
        $this->customers = $customers;
        $this->invoices = $invoices;
    }

    public function invoices(Team $team, Request $request) {
        $this->authorize('update_billing', $team);

        $invoices = $this->invoices->index($team, $request->input('invoices_after'));

        return response()->json($invoices);
    }

    public function payInvoice(Request $request) {
        $this->validate($request, [
            'invoice_id' => 'required'
        ]);

        return $this->invoices->pay($request->input('invoice_id'));
    }

    public function cards(Team $team) {
        $this->authorize('update_billing', $team);

        $cards = $this->customers->cards($team);

        return response()->json($cards);
    }

    public function create(Team $team, Request $request) {
        $this->validate($request, [
            'token' => 'required'
        ]);
        $this->authorize('update_billing', $team);

        $customer = $this->customers->create($team, $request->input('token'));

        $team->customer_code = $customer->id;
        $team->customer_created_at = Carbon::now();
        $team->save();

        return response()->json([
            'message' => __('billing.payment_method_created')
        ]);
    }

    public function update(Team $team, Request $request) {
        $this->validate($request, [
            'token' => 'required'
        ]);
        $this->authorize('update_billing', $team);

        $customer = $this->customers->update($team, $request->input('token'));
        $team->customer_code = $customer->id;
        $team->customer_updated_at = Carbon::now();
        $team->save();

        return response()->json([
            'message' => __('billing.payment_method_updated')
        ]);
    }

}
