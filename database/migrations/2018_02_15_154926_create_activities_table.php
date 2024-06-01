<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('major')->default(1);
            $table->string('action');
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('target_id');
            $table->string('target_type');
            $table->text('changes')->nullable();
            $table->text('metadata')->nullable();
            $table->datetime('created_at');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities');
    }
}
