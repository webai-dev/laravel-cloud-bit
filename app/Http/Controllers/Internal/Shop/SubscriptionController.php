<?php

namespace App\Http\Controllers\Internal\Shop;

use App\Billing\Managers\PlanManager;
use App\Billing\Managers\SubscriptionManager;
use App\Enums\SubscriptionType;
use App\Events\Subscriptions\SubscriptionUpdated;
use App\Http\Controllers\Controller;
use App\Mail\SubscriptionRequested;
use App\Models\Teams\Subscription;
use App\Models\Teams\SubscriptionRequest;
use App\Models\Teams\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SubscriptionController extends Controller {

    protected $subscriptions;

    public function __construct(SubscriptionManager $subscriptions, PlanManager $plans) {
        $this->subscriptions = $subscriptions;
        $this->plans = $plans;
    }

    public function index(Team $team) {
        $this->authorize('update_billing', $team);

        return response()->json([
            'main'    => $this->getSubscriptionPlan($team, SubscriptionType::MAIN),
            'bit' => $this->getSubscriptionPlan($team, SubscriptionType::BIT)
        ]);
    }

    protected function getSubscriptionPlan(Team $team, $type) {
        $subscription = $team->subscriptions()
            ->where('active', true)
            ->where('type', $type)
            ->first();
        if ($subscription == null) {
            return [];
        }

        $plan = $this->subscriptions->plan($subscription);

        return [
            'id'   => $subscription->id,
            'plan' => $plan
        ];
    }

    public function request(Team $team, Request $request) {
        $this->authorize('update_billing', $team);

        $subscription = new SubscriptionRequest($request->all());
        $subscription->team_id = $team->id;
        $subscription->save();

        Mail::to(config('mail.support_address'))
            ->send(new SubscriptionRequested($subscription));

        return $subscription;
    }

    public function change(Team $team, Request $request) {
        $this->authorize('update_billing', $team);

        $this->validate($request, [
            'plan_code' => 'required|string',
            'type'      => 'required|string|in:main,storage'
        ]);
        $type = $request->input('type');

        /** @var Subscription $existing */
        $existing = $team->subscriptions()
            ->where('type', $type)
            ->where('active', true)
            ->first();

        $plan_code = $request->input('plan_code');

        // Check if plans storage is bellow the teams used storage
        $plans_storage = $this->plans->storage($plan_code);
        $used_storage = $team->getTotalUsedStorage();

        if($plans_storage < $used_storage){
            abort(400, __('subscriptions.exceeding_limit'));
        }

        if ($existing == null) {
            $sub = $this->subscriptions->create($team, $plan_code);
        } else {
            $sub = $this->subscriptions->update($existing, $plan_code);
        }

        event(new SubscriptionUpdated($sub));

        return response()->json([
            'message' => __('subscriptions.updated')
        ]);
    }

    public function cancel(Team $team, Subscription $subscription) {
        $this->authorize('update_billing', $team);

        // Check if free storage is bellow the teams used storage
        $free_storage = config('filesystems.default_storage_limit');
        $used_storage = $team->getTotalUsedStorage();
        if($free_storage < $used_storage){
            abort(400, __('subscriptions.exceeding_limit'));
        }

        $this->subscriptions->cancel($subscription);

        return response()->json([
            'message' => __('subscriptions.canceled')
        ]);
    }
}
