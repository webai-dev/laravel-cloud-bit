<?php

namespace App\Indexing\Processors\Files;

use App\Indexing\Processors\Processor;

use Storage;
use Jstewmc\Rtf\Document;

class RtfFileProcessor implements Processor{
    
    public function process($file){
        $contents = Storage::get($file->path);
        $doc = new Document($contents);
        return $doc->write('text');
    }
}