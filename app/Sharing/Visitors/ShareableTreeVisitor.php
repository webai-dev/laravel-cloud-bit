<?php

namespace App\Sharing\Visitors;

use App\Models\Bits\Bit;
use App\Models\File;
use App\Models\Folder;
use App\Sharing\Shareable;

abstract class ShareableTreeVisitor extends ShareableVisitor {

    protected $depth = 0;

    function visitFolder(Folder $folder) {
        $this->depth += 1;
        $this->visitShareable($folder);

        foreach ($folder->folders()->withoutGlobalScopes()->get() as $subfolder) {
            $subfolder->accept($this);
        }
        foreach ($folder->bits()->withoutGlobalScopes()->get() as $bit) {
            $bit->accept($this);
        }
        foreach ($folder->files()->withoutGlobalScopes()->get() as $file) {
            $file->accept($this);
        }
        $this->depth -= 1;
    }

    function visitFile(File $file) {
        $this->visitShareable($file);
    }

    function visitBit(Bit $bit) {
        $this->visitShareable($bit);
    }

    /**
     * @return int
     */
    public function getDepth() {
        return $this->depth;
    }

    abstract function visitShareable(Shareable $shareable);
}