<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PinMap extends Migration
{
    public function up()
    {
        Schema::create('pin_map',function($table){
            $table->increments('id');
            $table->string('url'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pin_map');
    }
}
