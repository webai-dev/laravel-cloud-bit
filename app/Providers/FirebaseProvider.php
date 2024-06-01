<?php

namespace App\Providers;

use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\ServiceProvider;

class FirebaseProvider extends ServiceProvider
{
    protected $defer = true;

    public function register() {
        $this->app->singleton(FirestoreClient::class, function(){
            return new FirestoreClient([
                'keyFilePath' => config('firebase.config')
            ]);
        });
    }

    public function provides() {
        return [FirestoreClient::class];
    }
}