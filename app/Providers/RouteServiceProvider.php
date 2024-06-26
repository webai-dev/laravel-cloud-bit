<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider {
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot() {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map() {
        $this->mapApiRoutes();
        $this->mapBitRoutes();
        $this->mapIntegrationRoutes();
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes() {
        Route::middleware('api')
            ->namespace($this->namespace . "\Internal")
            ->group(base_path('routes/api.php'));
    }

    protected function mapBitRoutes() {
        Route::middleware('api')
            ->prefix("integration/{type}")
            ->namespace($this->namespace . "\Bits")
            ->group(base_path('routes/bits.php'));
    }

    protected function mapIntegrationRoutes() {
        Route::prefix("integrations")
            ->namespace($this->namespace . "\Integrations")
            ->group(base_path('routes/integration.php'));
    }
}
