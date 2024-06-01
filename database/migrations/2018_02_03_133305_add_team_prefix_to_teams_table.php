dev<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTeamPrefixToTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('teams',function($table){
            $table->string('subdomain')->after('id');
        });

        $teams = \App\Models\Teams\Team::withTrashed()->get();
        $count=1;

        foreach($teams as $team){
            $team->subdomain = 'default-'.$count;
            $team->save();
            $count++;
        }
        Schema::table('teams',function($table){
            $table->unique('subdomain');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('teams',function($table){
            $table->dropColumn('subdomain'); 
        });
    }
}
