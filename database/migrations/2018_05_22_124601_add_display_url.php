<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDisplayUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bit_types',function($table){
            $table->string('display_url')->nullable();
        });
        \DB::table('bit_types')
          ->update(['display_url' => DB::raw('base_url')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bit_types',function($table){
            $table->dropColumn('display_url');
        });
    }
}
