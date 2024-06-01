<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFullscreenToBitTypes extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('bit_types', function (Blueprint $table) {
            $table->boolean('fullscreen')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('bit_types', function (Blueprint $table) {
            $table->dropColumn('fullscreen');
        });
    }
}
