<?php

namespace App\Console\Commands\Sharing;

use App\Models\Share;
use App\Sharing\Shareable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class Conflicts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sharing:conflicts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detects any potential conflicts in shared folders (shared within shared)';


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        //Only fetch shared items that have a parent
        $total = Share::query()->count();

        $this->info("Found $total shares,searching for conflicts");
        $bar = $this->output->createProgressBar($total);

        $conflicts = [];

        Share::query()
            ->with('shareable')
            ->chunk(100,function(Collection $shares) use($bar,&$conflicts){
            foreach ($shares as $share) {
                $conflict = $this->findConflicts($share);

                if ($conflict != null){
                    /** @var Shareable $shareable */
                    $shareable = $share->shareable;

                    $conflicts[] = [
                        'id' => $shareable->id,
                        'type' => $shareable->getType(),
                        'title' => $shareable->title,
                        'team_id' => $shareable->team_id,
                        'team' => $shareable->team->name
                    ];
                }

                $bar->advance();
            }
        });

        $bar->finish();

        $conflict_count = count($conflicts);
        if ($conflict_count == 0){
            $this->info("\nNo conflicts found");
            return;
        }

        $this->warn("\nFound $conflict_count conflicts:");
        $this->table(array_keys($conflicts[0]),$conflicts);
    }


    /**
     * Returns the first folder in the tree that is shared if it exists
     * @param Share $share
     * @return \App\Models\Folder|null
     * @throws
     */
    protected function findConflicts(Share $share){
        if ($share->shareable == null){
            //Bug, should be unshared when it's soft deleted
            return null;
        }

        $current = $share->shareable->folder;
        while($current != null){
            if ($current->shares()->count() > 0){
                return $current;
            }
            $current = $current->folder;
        }
        return null;
    }
}
