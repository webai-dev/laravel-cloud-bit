<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamInvalidSubdomains extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_invalid_subdomains', function (Blueprint $table) {
            $table->string('subdomain')->unique();
            $table->primary('subdomain');
        });
        $invalid_subdomains = [
            'api',
            'admin',
            'reports',
            'developers',
            'stage',
            'live',
            'blog',
            'docs',
            'extensions',
            'store',
            'init',
            'intro',
            'news',
            'beta',
            'alpha',
            'testing',
            'support',
            'pricing',
            
            'ybit',
            'monospace',
            'monospacelabs',
            'webthatmatters',
            'wtm',
            'apparatus',
            
            'google',
            'facebook',
            'twitter',
            'instagram',
        ];
        foreach($invalid_subdomains as $sub)
            \App\Models\Teams\InvalidSubdomain::create(['subdomain'=>$sub]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_invalid_subdomains');
    }
}
