<?php

namespace App\Indexing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\IndexingService;

class IndexSharedItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $share;

    public function __construct($share){
        $this->share = $share;
    }

    public function handle(IndexingService $service){
        $this->service = $service;
        $shareable = $this->share->shareable;

        //A non-folder item has no children, so index is up-to-date
        if ($shareable->getType() != 'folder') {
            $this->createItemIndex($shareable);
            return;
        }

        //Recursively add children to index
        $this->createFolderIndex($shareable);
    }

    protected function createFolderIndex($folder){
        $this->createItemIndex($folder);

        foreach ($folder->files as $file) {
            $this->createItemIndex($file);
        }
        foreach($folder->bits as $bit){
            $this->createItemIndex($bit);
        }
        foreach($folder->folders as $folder){
            $this->createFolderIndex($folder);
        }
    }

    protected function createItemIndex($shareable){

        $docs = array_map(function($doc) use($shareable){
            $doc->shareable_type      = $shareable->getType();
            $doc->shareable_id        = $shareable->id;
            $doc->share_id            = $this->share->id;
            return $doc;
        },$shareable->toDocumentArray());

        foreach ($docs as $doc){
            $this->service->index($doc);
        }
    }
}
