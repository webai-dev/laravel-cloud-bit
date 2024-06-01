<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDefaultFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('default_filters', function($table){
            $table->increments('id');

            $table->integer('user_id')->unsigned();
            $table->integer('team_id')->unsigned()->nullable();
            
            $table->string('sort_by');
            
            $table->integer('bits_order')->unsigned();
            $table->integer('folders_order')->unsigned();
            $table->integer('files_order')->unsigned();

            $table->boolean('bits_collapse');
            $table->boolean('folders_collapse');
            $table->boolean('files_collapse');
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('default_filters');
    }
}
