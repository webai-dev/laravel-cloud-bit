<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use GuzzleHttp\Ring\Client\MockHandler;

use App\Services\IndexingService;
use App\Indexing\Handlers\AwsEsHandler;

class IndexingServiceProvider extends ServiceProvider {

    public function boot() {

        $this->app->bind('App\Services\IndexingService', function ($app) {
            $service = new IndexingService();

            switch (config('elasticsearch.env')) {
                case 'test':
                    $service->setHandler(new MockHandler([
                        'status'         => 200,
                        'transfer_stats' => [
                            'total_time' => 100
                        ],
                        'body'           => null
                    ]));
                    break;
                case 'aws':
                    $service->setHandler(new AwsEsHandler(config('elasticsearch.region')));
                    break;
                default:
                    break;
            }

            return $service;
        });
    }
}
