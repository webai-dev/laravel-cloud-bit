<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFlagsToShareables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('folders',function(Blueprint $table){
            $table->boolean('is_shared')->default(false);
            $table->boolean('has_shared_parent')->default(false);
        });
        Schema::table('files',function(Blueprint $table){
            $table->boolean('is_shared')->default(false);
            $table->boolean('has_shared_parent')->default(false);
        });
        Schema::table('bits',function(Blueprint $table){
            $table->boolean('is_shared')->default(false);
            $table->boolean('has_shared_parent')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('folders',function(Blueprint $table){
            $table->dropColumn(['is_shared','has_shared_parent']);
        });
        Schema::table('files',function(Blueprint $table){
            $table->dropColumn(['is_shared','has_shared_parent']);
        });
        Schema::table('bits',function(Blueprint $table){
            $table->dropColumn(['is_shared','has_shared_parent']);
        });
    }
}
