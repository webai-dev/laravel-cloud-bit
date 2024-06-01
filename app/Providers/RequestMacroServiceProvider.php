<?php

namespace App\Providers;

use App\Util\JWT;
use App\Models\Teams\Team;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class RequestMacroServiceProvider extends ServiceProvider {

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        Request::macro('getIntegration', function () {
            $jwt = $this->bearerToken();

            list($integration, $payload) = JWT::getIntegrationPayload($jwt);

            return $integration;
        });

        Request::macro('getTeam', function () {

            $integration = $this->getIntegration();

            if ($integration != null) {

                return $integration->team;
            }

            $referer = $this->headers->get('referer');
            if ($referer == null) {
                abort(422, __('exceptions.referer_invalid'));
            }

            $host = parse_url($referer, PHP_URL_HOST);
            if (!$host) {
                abort(422, __('exceptions.referer_invalid'));
            }

            $subdomain = array_first(explode(".", $host));
            if ($subdomain) {
                return Team::query()
                           ->where('subdomain', $subdomain)
                           ->first();
            }
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {
        //
    }
}
