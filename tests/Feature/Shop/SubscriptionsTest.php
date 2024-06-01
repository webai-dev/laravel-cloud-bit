<?php

namespace Tests\Feature;

use App\Billing\Managers\PlanManager;
use App\Billing\Managers\SubscriptionManager;
use Tests\TestCase;
use App\Models\Teams\Subscription;


class SubscriptionsTest extends TestCase
{

    private function mockManagers(){
        $subscriptions = \Mockery::mock(SubscriptionManager::class);
        $plans = \Mockery::mock(PlanManager::class);
        $this->app->singleton(SubscriptionManager::class, function() use ($subscriptions){
            return $subscriptions;
        });
        $this->app->singleton(PlanManager::class, function() use ($plans){
            return $plans;
        });

        return [$subscriptions , $plans];
    }

    public function testCreate(){
        $subscription_code = "sub_code";
        $plan_code = "plan_code";
        $type = 'main';
        $plan_storage = 10;

        list($team,$user) = $this->getTeam();
        $team->customer_code = 'customer_code';
        $team->save();

        list($subscriptions, $plans) = $this->mockManagers();

        $createdSubscription = new Subscription();
        $createdSubscription->code =$subscription_code;
        $createdSubscription->type =$type;
        $createdSubscription->storage =$plan_storage;
        $createdSubscription->team =$team;

        $subscriptions->shouldReceive('create')
          ->with(\Mockery::on(function($t) use($team){
              return $t->id == $team->id;
          }), $plan_code)
          ->andReturn($createdSubscription);


        $plans->shouldReceive('storage')
            ->with($plan_code)
            ->andReturn($plan_storage);

        $this->asUser($user)->asTeam($team)
            ->put('teams/'.$team->id.'/subscriptions',compact('plan_code','type'))
            ->assertStatus(200);

        $this->assertDatabaseHas('teams',[
            'storage_limit' => $plan_storage
        ]);
    }

    public function testUpdate(){
        list($team,$user) = $this->getTeam();
        $team->customer_code = 'customer_code';
        $team->save();

        list($subscriptions, $plans) = $this->mockManagers();

        $existingSubscription = new Subscription();
        $existingSubscription->code ='sub_old';
        $existingSubscription->plan_code ='plan_old';
        $existingSubscription->type ='main';
        $existingSubscription->storage = 5;
        $existingSubscription->team_id = $team->id;
        $existingSubscription->save();

        $createdSubscription = new Subscription();
        $createdSubscription->code = 'sub_new';
        $createdSubscription->type = 'main';
        $createdSubscription->storage = 10;
        $createdSubscription->team = $team;

        $plan_code = 'plan_new';
        $type = 'main';

        $subscriptions->shouldReceive('update')
            ->with(\Mockery::on(function($sub) use($existingSubscription){
              return $sub->id == $existingSubscription->id;
            }), $plan_code)
            ->andReturn($createdSubscription);

        $plans->shouldReceive('storage')
              ->with($plan_code)
              ->andReturn($existingSubscription->storage);

        $this->asUser($user)->asTeam($team)
             ->put('teams/'.$team->id.'/subscriptions',compact('plan_code','type'))
             ->assertStatus(200);

        $this->assertDatabaseHas('teams',[
            'storage_limit' => $createdSubscription->storage
        ]);
    }

    public function testCancel(){
        list($team,$user) = $this->getTeam();
        $team->customer_code = 'customer_code';
        $team->save();

        list($subscriptions) = $this->mockManagers();

        $existingSubscription = new Subscription();
        $existingSubscription->code ='sub_old';
        $existingSubscription->plan_code ='plan_old';
        $existingSubscription->type ='main';
        $existingSubscription->storage = 5;
        $existingSubscription->team_id = $team->id;
        $existingSubscription->save();

        $subscriptions->shouldReceive('cancel')
            ->with(\Mockery::on(function($sub) use($existingSubscription){
              return $sub->id == $existingSubscription->id;
            }));

        $this->asUser($user)->asTeam($team)
            ->delete('teams/'.$team->id.'/subscriptions')
            ->assertStatus(200);
    }

}
