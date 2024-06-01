<?php

namespace App\Indexing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\IndexingService;

class IndexUnsharedItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $share;
    
    public function __construct($share_id){
        $this->share = $share_id;
    }

    public function handle(IndexingService $service){
        $this->service = $service;
        $share = $this->share;

        $service->remove([
            'bool' => [
                'filter' => [
                    'term' => [ 'share_id' => $share ]
                ]
            ]    
        ]);
    }
    
}
