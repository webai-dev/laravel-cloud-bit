<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PinVideo extends Migration
{

    public function up()
    {
        Schema::create('pin_video',function($table){
            $table->increments('id');
            $table->string('title'); 
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
        Schema::drop('pin_video');
    }
}
