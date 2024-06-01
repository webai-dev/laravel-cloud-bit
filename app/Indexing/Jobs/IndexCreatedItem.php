<?php

namespace App\Indexing\Jobs;

use App\Sharing\Shareable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\IndexingService;

class IndexCreatedItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $shareable;
    
    public function __construct(Shareable $shareable){
        $this->shareable = $shareable;
    }

    public function handle(IndexingService $service){
        $docs = $this->shareable->toDocumentArray();

        foreach ($docs as $doc){
            $service->index($doc);
        }
    }
}
