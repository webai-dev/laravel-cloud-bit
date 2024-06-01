<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap(array_merge([
            'bit'       => \App\Models\Bits\Bit::class,
            'folder'    => \App\Models\Folder::class,
            'file'      => \App\Models\File::class,
            'pin'       => \App\Models\Pins\Pin::class,
            'team'      => \App\Models\Teams\Team::class,
            'user'      => \App\Models\User::class,
            'version'   => \App\Models\FileVersion::class
        ],\App\Models\Pins\Pin::MORPH_MAP));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->alias('bugsnag.multi', \Illuminate\Contracts\Logging\Log::class);
        $this->app->alias('bugsnag.multi', \Psr\Log\LoggerInterface::class);
    }
}
