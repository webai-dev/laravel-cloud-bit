<?php

namespace Tests\Unit;

use App\Util\URL;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UrlUtilsTest extends TestCase {
    use DatabaseTransactions;

   public function testTrailingSlash(){
       $url = "http://test.com";
       $result = URL::ensureSlash($url);
       $this->assertEquals($url . "/",$result);

       $result2 = URL::ensureSlash($result);
       $this->assertEquals($result2,$result);
   }
}
