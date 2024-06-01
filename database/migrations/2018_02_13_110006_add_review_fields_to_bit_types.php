<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReviewFieldsToBitTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bit_types',function($table){
            $table->boolean('draft')->default(1);
            $table->boolean('reviewed')->default(0);
            $table->timestamp('published_at')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        
        Schema::table('bit_types',function($table){
            $table->dropColumn(['draft','reviewed','published_at']); 
        });
    }
}
