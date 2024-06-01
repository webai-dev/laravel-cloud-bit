<?php

namespace App\Indexing\Processors\Files;

use App\Indexing\Processors\Processor;

class FileProcessorFactory{
    
    protected static $bindings = [
        'txt'  => TextFileProcessor::class,
        'pdf'  => PdfFileProcessor::class,
        'rtf'  => RtfFileProcessor::class,
        'doc'  => DocFileProcessor::class,
        'docx' => TextArchiveFileProcessor::class,
        'odt'  => TextArchiveFileProcessor::class,
    ];

    /**
     * @param string $extension
     * @return Processor|null
     */
    public static function getForExtension($extension){
        if (array_key_exists($extension,self::$bindings)) {
            return new self::$bindings[$extension]();
        }else{
            return null;
        }
    }
}