<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BitTypeSizes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bit_type_sizes',function($table){
            $table->increments('id');
            $table->integer('type_id')->unsigned();
            $table->integer('width');
            $table->integer('height');
            
            $table->foreign('type_id')->references('id')->on('bit_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bit_type_sizes');
    }
}
