<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('files/public/{token}', 'FileController@showPublic');

Route::group(['namespace' => 'Auth'], function () {
    Route::post('apparatus', 'ApparatusController@connect');
    Route::get('apparatus', 'ApparatusController@token');
    Route::post('apparatus/sync', 'ApparatusController@sync');
    Route::post('apparatus/magic-link', 'ApparatusController@sendMagicLink');
    Route::post('magic-link', 'AuthController@link');
    Route::get('apparatus/terms', 'ApparatusController@terms');
    Route::post('apparatus/accept-terms', 'ApparatusController@acceptTerms');

});

Route::group(['middleware' => ['integration.check','jwt.auth']], function () {
    Route::get('auth/firebase', 'Auth\FirebaseController@token');

    Route::get('account', 'AccountController@index');
    Route::put('account', 'AccountController@update');
    Route::delete('account', 'AccountController@destroy');
    Route::post('account/accept-terms', 'AccountController@acceptTerms');

    Route::get('teams', 'TeamController@index');
    Route::post('teams', 'TeamController@store');

    Route::get('teams/subdomain', 'TeamController@validateSubdomain');

    Route::get('invitations', 'InvitationController@index');
    Route::delete('invitations/{invitation}', 'InvitationController@destroy');
    Route::put('invitations/{invitation}', 'InvitationController@update');

    Route::get('roles', 'RoleController@index');

    Route::resource('photos', 'PhotoController', ['only' => ['store']]);

    Route::get('maintenances/active', 'MaintenanceController@getActiveRecord');
    Route::resource('maintenances', 'MaintenanceController');
    Route::get('{target_type}/{target_id}/activity', 'ActivityController@index');
});



Route::group(['middleware' => ['integration.check', 'jwt.auth', 'team.check', 'ban.check']], function () {

    Route::get('roles/{team}', 'RoleController@show');

    Route::group(['prefix' => 'teams/{team}/billing'], function () {
        Route::get('/cards', 'Shop\BillingController@cards');
        Route::get('/invoices', 'Shop\BillingController@invoices');
        Route::post('/', 'Shop\BillingController@create');
        Route::put('/', 'Shop\BillingController@update');
    });
    Route::post('/invoice/pay', 'Shop\BillingController@payInvoice');

    Route::get('teams/{team}/products', 'Shop\ProductController@index');
    Route::get('/plans', 'Shop\PlanController@index');

    Route::get('/teams/{team}/storage', 'Shop\StorageController@index');

    Route::group(['prefix' => 'teams/{team}/subscriptions'], function () {
        Route::get('/', 'Shop\SubscriptionController@index');
        Route::put('/', 'Shop\SubscriptionController@change');
        Route::post('/contact', 'Shop\SubscriptionController@request');
        Route::delete('{subscription}', 'Shop\SubscriptionController@cancel');
    });

    Route::group(['prefix' => 'teams/{team}/users'], function () {
        Route::delete('{user}', 'TeamUserController@remove');
        Route::put('{user}/ban', 'TeamUserController@ban');
        Route::put('{user}/developer', 'TeamUserController@developer');
        Route::put('{user}', 'TeamUserController@update');
        Route::get('/', 'TeamUserController@index');
    });

    Route::get('teams/{team}/search', 'TeamController@search');
    Route::put('teams/{team}/transfer', 'TeamController@transfer');
    Route::put('teams/{team}/suspend', 'TeamController@suspend');
    Route::get('teams/{team}/invitations', 'TeamController@invitations');

    Route::resource('teams/{team}/integrations', 'TeamIntegrationController', ['except' => ['edit', 'create', 'show']]);
    Route::get('integration/folders', 'TeamIntegrationController@folders');

    Route::resource('teams', 'TeamController', ['except' => ['edit', 'create', 'index', 'store']]);

    Route::get('bits/types/recent', 'BitTypeController@recent');
    Route::resource('bits/types', 'BitTypeController', ['only' => ['index', 'show', 'store']]);
    Route::put('bits/types/{id}/toggle', 'BitTypeController@toggle');

    Route::put('bits/{id}/lock', 'BitController@toggle_locked');
    Route::get('bits/locked', 'BitController@show_locked');
    Route::put('bits/{id}/move', 'BitController@move');
    Route::delete('bits/{bit}/trash', 'BitController@trash');
    Route::resource('bits', 'BitController', ['except' => ['edit', 'create']]);

    Route::put('folders/{id}/lock', 'FolderController@toggle_locked');
    Route::get('folders/locked', 'FolderController@show_locked');
    Route::put('folders/{folder}/move', 'FolderController@move');
    Route::delete('folders/{folder}/trash', 'FolderController@trash');
    Route::resource('folders', 'FolderController', ['except' => ['edit', 'create', 'show']]);

    Route::put('files/{id}/lock', 'FileController@toggle_locked');
    Route::get('files/locked', 'FileController@show_locked');
    Route::get('files/path', 'FileController@getPath');
    Route::post('files/create', 'FileController@createFile');

    Route::group(['prefix' => 'files/{file}'], function () {
        Route::put('copy', 'FileController@copy');
        Route::put('move', 'FileController@move');
        Route::get('link', 'FileController@link');
        Route::put('publish', 'FileController@publish');
        Route::put('unpublish', 'FileController@unpublish');
        Route::delete('trash', 'FileController@trash');

        Route::resource('versions', 'FileVersionController', ['except' => ['edit', 'create']]);
    });

    Route::resource('files', 'FileController', ['except' => ['edit', 'create']]);

    Route::post('invitations', 'InvitationController@store');

    Route::get('shares/permissions', 'ShareController@permissions');
    Route::post('shares/bulk', 'ShareController@storeBulk');
    Route::delete('shares/team', 'ShareController@destroyTeam');
    Route::delete('shares/bulk', 'ShareController@destroyBulk');

    Route::resource('shares', 'ShareController', ['except' => ['edit', 'create', 'show']]);

    Route::get('pins/types', 'PinController@types');
    Route::put('pins/{id}/favourite', 'PinController@favourite');
    Route::resource('pins', 'PinController', ['except' => ['edit', 'create']]);

    Route::group(['prefix' => 'bulk'], function () {
        Route::get('download', 'BulkController@download');
        Route::put('move', 'BulkController@move');
        Route::delete('trash', 'BulkController@trash');
    });

    Route::group(['prefix' => 'shortcuts'], function () {
        Route::post('/', 'ShortcutController@store');
        Route::put('/{shortcut}/move', 'ShortcutController@move');
        Route::delete('/{shortcut}', 'ShortcutController@destroy');
    });

    Route::get('filters', 'FilterController@show');
    Route::post('filters', 'FilterController@store');


});

Route::group(['middleware' => ['jwt.auth', 'team.check', 'ban.check', 'developer.check']], function () {
    Route::group(['prefix' => 'sandbox'], function () {
        Route::resource('types', 'Sandbox\SandboxTypeController', ['only' => ['store', 'update', 'destroy', 'index']]);
        Route::resource('types/{type}/instances', 'Sandbox\SandboxInstanceController',
            ['only' => ['store', 'update', 'destroy', 'show']]);
    });

});

Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'admin'], function () {
    Route::delete('teams/{team}', 'TeamController@destroy');
    Route::put('users/{id}/ban', 'UserController@ban');
});

Route::post('log', 'CommonController@log');
