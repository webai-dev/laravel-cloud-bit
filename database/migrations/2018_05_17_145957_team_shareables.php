<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TeamShareables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_shareables',function($table){
            
            $table->increments('id');
            $table->integer('team_id')->unsigned();
            $table->integer('shareable_id')->unsigned();
            $table->integer('created_by_id')->unsigned();
            $table->string('shareable_type');
            
            $table->boolean('share')->default(false);
            $table->boolean('edit')->default(false);
            $table->timestamps();
            
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('team_shareables');
    }
}
