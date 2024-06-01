<?php

namespace App\Indexing\Processors\Files;

use App\Indexing\Processors\Processor;

use Storage,ZipArchive,DOMDocument;

class TextArchiveFileProcessor implements Processor{
    
    public function process($file){
        $filename = microtime(true)."_".$file->id;
        $path = "/tmp/_ybit_$filename";
        
        file_put_contents($path,Storage::get($file->path));
        
        $main = $file->extension == 'docx' ? "word/document.xml" : "content.xml";

        $zip = new ZipArchive();
        $text = null;
        
        if ($zip->open($path)) {
            if (($index = $zip->locateName($main)) !== false) {
                $text = $zip->getFromIndex($index);
                
                $doc = new DOMDocument();
                $doc->loadXml($text, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);

                $text = strip_tags($doc->saveXML());
            }
            $zip->close();
        }
        
        unlink($path);
        
        return $text;
    }
}