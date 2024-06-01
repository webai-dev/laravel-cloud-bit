<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddS3PerTeam extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('teams',function($table){
            $table->boolean('uses_external_storage')->default(false);
            $table->string('aws_key')->nullable();
            $table->string('aws_secret')->nullable();
            $table->string('aws_region')->nullable();
            $table->string('aws_bucket')->nullable();
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
            $table->dropColumn(['uses_external_storage','aws_key','aws_secret','aws_region','aws_bucket']); 
        });
    }
}
