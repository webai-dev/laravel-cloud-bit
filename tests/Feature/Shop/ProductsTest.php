<?php

namespace Tests\Feature;

use App\Billing\Managers\ProductManager;
use Tests\TestCase;


class ProductsTest extends TestCase {

    private function mockManager(){
        $products = \Mockery::mock(ProductManager::class);

        $this->app->singleton(ProductManager::class, function() use ($products){
            return $products;
        });
        return $products;
    }

    // returns 3 regular products and two custom plans
    // one for the team with $custom_team_id
    // and one for a random team;
    private function mockProductsIndex($custom_team_id) {
        $products = [];
        for($i = 1; $i <= 3; $i++){
            $p = new \stdClass();
            $p->id = 'prod_'.$i;
            $p->metadata  = new \stdClass();
            $p->metadata->type = 'main';
            $p->metadata->storage = 10;
            $p->metadata->custom = 'false';
            $p->metadata->team_id = null;
            $products[] = $p;
        }
        // add a random custom product
        $custom_product = new \stdClass();
        $custom_product->id = 'prod_custom';
        $custom_product->metadata  = new \stdClass();
        $custom_product->metadata->type = 'main';
        $custom_product->metadata->storage = 10;
        $custom_product->metadata->custom = 'true';
        $custom_product->metadata->team_id = 1728937;

        // add the teams custom product
        $custom_product = new \stdClass();
        $custom_product->id = 'prod_custom';
        $custom_product->metadata  = new \stdClass();
        $custom_product->metadata->type = 'main';
        $custom_product->metadata->storage = 10;
        $custom_product->metadata->custom = 'true';
        $custom_product->metadata->team_id = $custom_team_id;


        $products[] = $custom_product;

        return $products;
    }

    public function testWithCustomPlan() {
        list($team,$user) = $this->getTeam();
        $manager = $this->mockManager();

        // products will contain a custom plan for this team
        $products = $this->mockProductsIndex($team->id);

        $manager->shouldReceive('index')
            ->with()
            ->andReturn($products);

        $responce = $this->asUser($user)->asTeam($team)
            ->json('GET', 'teams/'.$team->id.'/products');
        $responce->assertStatus(200);

        $custom_products = 0;
        foreach ($responce->json() as $product) {
            if($product['metadata']['custom'] == true ) $custom_products++;
        }
        $this->assertEquals($custom_products, 1);
    }

    public function testWithoutCustomPlan() {
        list($team,$user) = $this->getTeam();
        $manager = $this->mockManager();

        // products wont contain a custom plan for this team
        $products = $this->mockProductsIndex($team->id - 1);

        $manager->shouldReceive('index')
            ->with()
            ->andReturn($products);

        $responce = $this->asUser($user)->asTeam($team)
            ->json('GET', 'teams/'.$team->id.'/products');
        $responce->assertStatus(200);

        $custom_products = 0;
        foreach ($responce->json() as $product) {
            if($product['metadata']['custom'] == true ) $custom_products++;
        }
        $this->assertEquals($custom_products, 0);
    }

}
