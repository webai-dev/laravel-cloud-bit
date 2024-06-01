<?php

namespace App\Indexing\Jobs;

use App\Models\Folder;
use App\Sharing\Shareable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\IndexingService;

class IndexMovedItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $shareable;

    /** @var IndexingService */
    protected $service;
    
    public function __construct(Shareable $shareable){
        $this->shareable = $shareable;
    }

    public function handle(IndexingService $service){
        $this->service = $service;
        
        $item = $this->shareable;
        
        $created = new IndexCreatedItem($item);
        $created->handle($service);
        
        //Non-folder items don't need to further update the index
        if ($item instanceof Folder) {
            $this->indexFolder($item);
        }
    }
    
    public function indexFolder(Folder $folder){
        $docs = [];
        foreach ($folder->folders()->get() as $item) {
            $docs = array_merge($docs,$item->toDocumentArray());
            $this->indexFolder($item);
        }
        
        foreach ($folder->files()->get() as $item) {
            $docs = array_merge($docs,$item->toDocumentArray());
        }
        
        foreach ($folder->bits()->get() as $item) {
            $docs = array_merge($docs,$item->toDocumentArray());
        }
        
        if (count($docs) > 0) {
            foreach ($docs as $doc){
                $this->service->index($doc);
            }
        }
    }
}
