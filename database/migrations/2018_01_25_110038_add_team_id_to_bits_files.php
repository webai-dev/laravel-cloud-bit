<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTeamIdToBitsFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bits',function($table){
            $table->integer('team_id')->unsigned()->nullable();
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null'); 
        });
        Schema::table('files',function($table){
            $table->integer('team_id')->unsigned()->nullable();
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bits',function($table){
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id'); 
        });
        Schema::table('files',function($table){
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id'); 
        });
    }
}
