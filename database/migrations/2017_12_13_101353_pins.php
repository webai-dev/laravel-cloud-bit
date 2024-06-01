<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Pins extends Migration
{
    public function up()
    {
        Schema::create('pins',function($table){
            $table->increments('id');
            $table->integer('type_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->integer('team_id')->unsigned();
            
            $table->integer('content_id')->unsigned();
            $table->string('content_type');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('pins');
    }
}
