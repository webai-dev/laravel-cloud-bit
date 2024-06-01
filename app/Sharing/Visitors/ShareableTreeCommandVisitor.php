<?php

namespace App\Sharing\Visitors;


use App\Models\Bits\Bit;
use App\Models\File;
use App\Models\Folder;
use App\Sharing\Shareable;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ShareableTreeCommandVisitor extends ShareableTreeVisitor {

    protected $command;

    protected $visitor;

    public function __construct(ShareableTreeVisitor $visitor,Command $command) {
        $this->command = $command;
        $this->visitor = $visitor;
    }

    public function visitFile(File $file) {
        $this->setOutputFormatterStyle('yellow');
        $this->log($file);

        parent::visitFile($file);
    }

    public function visitBit(Bit $bit) {
        $this->setOutputFormatterStyle('blue');
        $this->log($bit);

        parent::visitBit($bit);
    }

    public function visitFolder(Folder $folder) {
        $this->setOutputFormatterStyle('white');
        $this->log($folder);

        parent::visitFolder($folder);
    }

    protected function setOutputFormatterStyle($style) {
        $style = new OutputFormatterStyle($style);
        $this->command->getOutput()->getFormatter()->setStyle('info', $style);
    }

    protected function log(Shareable $shareable) {
        $depth = $this->depth;
        $text = "$shareable->title ($shareable->owner_id)";
        $this->command->info(str_repeat(" ", $depth * 2) . $text);
    }

    function visitShareable(Shareable $shareable) {
        $this->visitor->visitShareable($shareable);
    }
}