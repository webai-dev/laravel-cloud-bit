<?php

namespace App\Indexing\Processors\Files;

use App\Indexing\Processors\Processor;

use App\Models\File;
use Storage;
use Smalot\PdfParser\Parser;

class PdfFileProcessor implements Processor{
    public function process($file){
        $parser = new Parser();
        try {
            $pdf = $parser->parseContent(Storage::get($file->path));
            return $pdf->getText();
        } catch (\Exception $e) {
            //Unsupported PDF format
            return '';
        }
    }
}