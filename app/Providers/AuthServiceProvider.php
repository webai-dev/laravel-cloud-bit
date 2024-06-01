<?php

namespace App\Providers;

use App\Models\Bits\Bit;
use App\Models\File;
use App\Models\Folder;
use App\Models\Maintenance;
use App\Models\Pins\Pin;
use App\Models\Share;
use App\Models\Shortcut;
use App\Models\Teams\Integration;
use App\Models\Teams\Invitation;
use App\Models\Teams\Team;
use App\Models\User;
use App\Policies\BitPolicy;
use App\Policies\FilePolicy;
use App\Policies\FolderPolicy;
use App\Policies\IntegrationPolicy;
use App\Policies\InvitationPolicy;
use App\Policies\MaintenancePolicy;
use App\Policies\PinPolicy;
use App\Policies\SharePolicy;
use App\Policies\ShortcutPolicy;
use App\Policies\TeamPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Guards\JwtGuard;
use Illuminate\Support\Facades\Auth;
use WebThatMatters\Apparatus\ApparatusService;
use WebThatMatters\Apparatus\Configuration\Configuration;

class AuthServiceProvider extends ServiceProvider {
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Team::class        => TeamPolicy::class,
        Invitation::class  => InvitationPolicy::class,
        Folder::class      => FolderPolicy::class,
        File::class        => FilePolicy::class,
        Bit::class         => BitPolicy::class,
        Share::class       => SharePolicy::class,
        Pin::class         => PinPolicy::class,
        Shortcut::class    => ShortcutPolicy::class,
        User::class        => UserPolicy::class,
        Integration::class => IntegrationPolicy::class,
        Maintenance::class => MaintenancePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {
        $this->registerPolicies();

        Auth::extend('jwt', function () {
            return new JwtGuard();
        });

        $this->app->bind(ApparatusService::class, function () {
            $config = new Configuration();
            $config->setEmailUrl(config('auth.apparatus.email_url'))
                ->setApiVersion(config('auth.apparatus.api_version'))
                ->setIntegrationId(config('auth.apparatus.integration_id'))
                ->setKey(config('auth.apparatus.jwt_secret'))
                ->setIssuer(config('app.url'));
            return new \App\Services\ApparatusService($config);
        });
    }
}
