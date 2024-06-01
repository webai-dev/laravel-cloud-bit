<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCdnUrlToTeams extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('cdn_url', 500)->nullable();
            $table->string('aws_secret',1000)->nullable()->change();
            $table->string('aws_key',1000)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('cdn_url');
        });
    }
}
