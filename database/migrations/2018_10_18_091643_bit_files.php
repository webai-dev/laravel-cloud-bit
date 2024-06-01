<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BitFiles extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('bit_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('bit_id')->unsigned();

            $table->string('filename');
            $table->string('path');
            $table->string('extension');
            $table->bigInteger('size')->unsigned();
            $table->string('mime_type');

            $table->foreign('bit_id')->references('id')->on('bits');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('bit_files');
    }
}
