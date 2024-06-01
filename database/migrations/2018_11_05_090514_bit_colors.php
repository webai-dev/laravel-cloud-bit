<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BitColors extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('bit_colors', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('bit_id')->unsigned();

            $table->string('color');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('bit_id')->references('id')->on('bits')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('bit_colors');
    }
}
