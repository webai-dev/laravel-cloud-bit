<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBgTaglineToBitTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bit_types', function (Blueprint $table) {
            $table->string('background')->nullable();
            $table->string('tagline')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bit_types', function (Blueprint $table) {
            $table->dropColumn('background');
            $table->dropColumn('tagline');
        });
    }
}
