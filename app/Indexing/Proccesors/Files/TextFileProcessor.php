<?php

namespace App\Indexing\Processors\Files;

use App\Indexing\Processors\Processor;

use App\Models\File;
use Storage;

class TextFileProcessor implements Processor{
    public function process($file){
        $contents = Storage::get($file->path);
        return $contents;
    }
}