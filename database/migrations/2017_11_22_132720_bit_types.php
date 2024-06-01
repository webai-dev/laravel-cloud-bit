<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BitTypes extends Migration
{

    public function up()
    {
        Schema::create('bit_types',function($table){
            $table->increments('id');
            $table->string('name');
            $table->integer('user_id')->unsigned()->nullable();
            $table->boolean('public')->default(0);
            $table->text('jwt_key')->nullable();
            $table->string('base_url');
            $table->json('schema')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::drop('bit_types');
    }
}
