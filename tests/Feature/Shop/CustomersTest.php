<?php

namespace Tests\Feature;

use App\Billing\Managers\CustomerManager;
use Tests\TestCase;


class CustomersTest extends TestCase {

    private function mockManager(){
        $customers = \Mockery::mock(CustomerManager::class);

        $this->app->singleton(CustomerManager::class, function() use ($customers){
            return $customers;
        });
        return $customers;
    }

    public function testCreate() {
        $customers = $this->mockManager();
        list($team,$user) = $this->getTeam();

        $token = [
            'id' => 'tok_example',
            'email' => 'e@mail.com',
            'card' => [
                'name' => 'John Doe',
                'address_line1' => 'address 1',
                'address_city' => 'city',
                'address_zip' => '1234',
                'address_country' => 'USA',
                'address_state' => 'CO'
            ]
        ];
        $customer =  new \stdClass();
        $customer->id = 'cus_example';

        $customers->shouldReceive('create')
            ->with(\Mockery::on(function($t) use($team){
              return $t->id == $team->id;
            }), $token)
            ->andReturn($customer);

        $this->asUser($user)->asTeam($team)
             ->post('teams/'.$team->id.'/billing',compact('token'))
             ->assertStatus(200);

        $this->assertDatabaseHas('teams',[
            'customer_code' => $customer->id
        ]);
    }

    public function testUpdate() {
        $customers = $this->mockManager();
        list($team,$user) = $this->getTeam();

        $token = [
            'id' => 'tok_example',
            'email' => 'e@mail.com',
            'card' => [
                'name' => 'John Doe',
                'address_line1' => 'address 1',
                'address_city' => 'city',
                'address_zip' => '1234',
                'address_country' => 'USA',
                'address_state' => 'CO'
            ]
        ];

        $team->customer_code = 'cus_old';
        $team->save();

        $new_customer =  new \stdClass();
        $new_customer->id = 'cus_new';

        $customers->shouldReceive('update')
                  ->with(\Mockery::on(function($t) use($team){
                      return $t->id == $team->id;
                  }), $token)
                  ->andReturn($new_customer);

        $this->asUser($user)->asTeam($team)
             ->put('teams/'.$team->id.'/billing',compact('token'))
             ->assertStatus(200);

        $this->assertDatabaseHas('teams',[
            'customer_code' => $new_customer->id
        ]);
    }

}
