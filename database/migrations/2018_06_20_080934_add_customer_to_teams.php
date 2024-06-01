<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomerToTeams extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('teams',function(Blueprint $table){
            $table->string('customer_code')->nullable();
            $table->dateTime('customer_created_at')->nullable();
            $table->dateTime('customer_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('teams',function(Blueprint $table){
           $table->dropColumn(['customer_code','customer_created_at','customer_updated_at']);
        });
    }
}
