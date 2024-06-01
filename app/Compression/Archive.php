<?php

namespace App\Compression;

use Illuminate\Support\Collection;
use App\Models\File;
use App\Models\Folder;

interface Archive{
    public function addFile(File $file,$path = '');
    
    public function addFiles(Collection $files,$path = '');
    
    public function addFolder(Folder $folder,$path = '');
    
    public function addFolders(Collection $folders,$path = '');
    
    public function compress();
}