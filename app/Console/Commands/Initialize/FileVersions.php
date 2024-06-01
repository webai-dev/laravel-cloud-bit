<?php

namespace App\Console\Commands\Initialize;

use App\Models\File;
use Illuminate\Console\Command;

class FileVersions extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:versions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initializes the version entries of the files';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }


    public function handle() {
        $count = File::query()->doesntHave('versions')->count();
        $bar = $this->output->createProgressBar($count);

        File::query()->with([
            'activities' => function ($query) {
                $query->whereIn('action', ['upload', 'reupload'])
                    ->orderBy('created_at', 'DESC');
            }
        ])->chunk(100, function ($files) use ($bar) {
            foreach ($files as $file) {
                $this->createVersions($file);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->info("File versions initialized.");
    }

    protected function createVersions(File $file) {
        $current_version = $file->s3_version_id;
        $current_size = $file->size;
        $version_count = $file->activities->count();

        foreach ($file->activities as $i => $activity) {
            $metadata = json_decode($activity->metadata);
            $current_filename = $metadata->original_filename;
            $is_current =  $i == 0;

            $file->versions()->create([
                's3_id'      => $current_version,
                'filename'   => $current_filename,
                'name'       => 'Version ' . $version_count,
                'user_id'    => $activity->user_id,
                'created_at' => $activity->created_at,
                'size'       => $current_size,
                'current'    => $is_current
            ]);

            if ($activity->action == "reupload") {
                $changes = json_decode($activity->changes);

                $current_version = $changes->s3_version_id->before;
                if (isset($changes->size)){
                    $current_size = $changes->size->before;
                }
            }
            $version_count--;
        }
    }
}
