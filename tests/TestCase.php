<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication,AttachesJWT,AttachesServerVars,ProvidesTeams,DatabaseTransactions;

    protected function setUp() {
        parent::setUp();
        $this->seed('RolesSeeder');
    }

    protected function fetch($url,$params = [],$headers =[]){
        return $this->get($url.'?'.http_build_query($params),$headers);
    }

    protected function assertResponse(TestResponse $response,$code = 200){
        $this->assertEquals($code,$response->getStatusCode(),"Failed asserting status code, response was: ".$response->getContent());
        return $response;
    }
}
