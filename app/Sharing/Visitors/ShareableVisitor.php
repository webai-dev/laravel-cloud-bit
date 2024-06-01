<?php

namespace App\Sharing\Visitors;


use App\Models\Bits\Bit;
use App\Models\File;
use App\Models\Folder;

abstract class ShareableVisitor {

    abstract function visitFolder(Folder $folder);

    abstract function visitFile(File $file);

    abstract function visitBit(Bit $bit);

}