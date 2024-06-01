<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEnabledToTeamBits extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('bit_type_teams', function (Blueprint $table) {
            $table->boolean('enabled')->default(true);
        });

        $types = \App\Models\Bits\Type::query()->where('public',true)->get();
        $teams = \App\Models\Teams\Team::all();

        foreach ($types as $type) {
            $type->teams()->attach($teams->pluck('id')->toArray());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('bit_type_teams', function (Blueprint $table) {
            $table->dropColumn('enabled');
        });

        $types = \App\Models\Bits\Type::query()->where('public',true)->get();
        $teams = \App\Models\Teams\Team::all();

        foreach ($types as $type) {
            $type->teams()->detach($teams->pluck('id')->toArray());
        }
    }
}
