<?php

namespace App\Console\Commands\Sharing;

use App\Models\Folder;
use App\Sharing\Shareable;
use App\Sharing\Visitors\OwnershipUpdateVisitor;
use App\Sharing\Visitors\ShareableTreeCommandVisitor;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class Owners extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sharing:owners';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the original owners of shared items';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() {
        Shareable::$update_index = false;

        Folder::query()
            ->whereNull('folder_id')
            ->withoutGlobalScopes()
            ->with('folder')
            ->chunk(100, function ($folders) {
                /** @var Folder $folder */
                foreach ($folders as $folder) {
                    $this->updateShareable($folder, 0);
                }
            });
    }

    public function updateShareable(Shareable $shareable, $depth) {
        $this->updateOwners($shareable);
        $this->log($shareable,$depth);

        if ($shareable instanceof Folder) {
            foreach ($shareable->folders as $folder) {
                $this->updateShareable($folder, $depth + 1);
            }
            foreach ($shareable->bits as $bit) {
                $this->updateOwners($bit);
                $this->log($bit,$depth);

            }
            foreach ($shareable->files as $file) {
                $this->updateOwners($file);
                $this->log($file,$depth);
            }
        }
    }

    function updateOwners(Shareable $shareable) {
        if ($shareable->folder_id == null) {
            $shareable->owner_id = $shareable->user_id;
            $shareable->save();
        } else {
            $shareable->owner_id = $shareable->folder()->withoutGlobalScopes()->first()->owner_id;
            $shareable->save();
        }
    }

    protected function log(Shareable $shareable, $depth) {
        $style = "white";
        if ($shareable->getType() == "file") $style = "yellow";
        if ($shareable->getType() == "bit") $style = "blue";

        $style = new OutputFormatterStyle($style);
        $this->getOutput()->getFormatter()->setStyle('info', $style);

        $text = "$shareable->title ($shareable->owner_id)";
        $this->info(str_repeat(" ", $depth * 2) . $text);
    }
}
