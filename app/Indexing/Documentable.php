<?php

namespace App\Indexing;

interface Documentable{
    public function toDocument();
    
    public function toDocumentArray();
}