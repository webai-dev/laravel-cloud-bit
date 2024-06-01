<?php

namespace App\Indexing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Log;
use App\Services\IndexingService;

class IndexDeletedItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $shareable;
    
    public function __construct($shareable){
        $this->shareable = $shareable;
    }

    public function handle(IndexingService $service){
        $this->service = $service;
        $shareable = $this->shareable;
        
        //A non-folder item has no children, so index is up-to-date
        if ($shareable->getType() != 'folder') {
            //Clear all indexes related to this item
            $this->clearItemIndex($shareable);
        }else{
            //Remove folder contents from index
            $this->clearFolderIndex($shareable);
        }
        
    }
    
    protected function clearFolderIndex($folder){
        $this->clearItemIndex($folder);
        
        //Deleting an item will recursively trigger deletion actions
        foreach ($folder->files as $file) {
            $file->delete();
        }
        foreach($folder->folders as $folder){
            $folder->delete();
        }
        //Bits are only removed from the index, so they can be recovered
        foreach($folder->bits as $bit){
            $this->clearItemIndex($bit);
        }
    }
    
    protected function clearItemIndex($shareable){
        $query = [
            'bool' => [
                'filter' => [
                    [ 'term' => [ 'shareable_type' => $shareable->getType() ] ],
                    [ 'term' => [ 'shareable_id' => $shareable->id ] ],
                    [ 'term' => [ 'team_id' => $shareable->team_id ] ],
                ]
            ]
        ];
        $this->service->remove($query);
    }
}
