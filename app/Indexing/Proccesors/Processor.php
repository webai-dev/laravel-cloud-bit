<?php

namespace App\Indexing\Processors;

interface Processor{
    public function process($item);
}