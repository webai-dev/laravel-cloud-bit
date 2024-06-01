<?php

namespace App\Indexing\Processors\Files;

use App\Indexing\Processors\Processor;

use App\Models\File;
use Storage;
use Smalot\PdfParser\Parser;

class DocFileProcessor implements Processor{
    public function process($file){
        $line       = Storage::get($file->path);
        $lines      = explode(chr(0x0D), $line);
        
        $text   = '';
        
        foreach ($lines as $current_line) {
            
            $pos = strpos($current_line, chr(0x00));
            
            if ( ($pos !== FALSE) || (strlen($current_line) == 0) ) {
                
            } else {
                $text .= $current_line . ' ';
            }
        }
        
        $stripped = preg_replace('/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/', '', $text);
        return $stripped;
    }
}