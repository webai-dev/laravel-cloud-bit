<?php

namespace App\Indexing\Searches;

interface Search{
    public function toQuery();
    public function parseResults($results);
}