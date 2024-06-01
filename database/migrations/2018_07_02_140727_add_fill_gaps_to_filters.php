<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFillGapsToFilters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('folder_filters', function($table){
            $table->boolean('fill_gaps')->nullable();
        });
        
        Schema::table('default_filters', function($table){
            $table->boolean('fill_gaps')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('folder_filters', function($table){
           $table->dropColumn('fill_gaps');
        });
        
        Schema::table('default_filters', function($table){
           $table->dropColumn('fill_gaps');
        });
    }
}
