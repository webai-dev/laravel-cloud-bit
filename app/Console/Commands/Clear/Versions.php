<?php

namespace App\Console\Commands\Clear;

use App\Models\FileVersion;
use App\Uploading\S3OperationsManager;
use Carbon\Carbon;
use Illuminate\Console\Command;

class Versions extends Command {

    protected $signature = 'clear:versions';
    protected $description = 'Deletes file versions older than the configured duration';

    protected $manager;

    public function __construct(S3OperationsManager $manager) {
        parent::__construct();
        $this->manager = $manager;
    }

    public function handle() {
        $interval = \DateInterval::createFromDateString(config('filesystems.file_versions_ttl'));
        $threshold = Carbon::now()->sub($interval);
        $this->info("Deleting file versions older than $threshold");

        $query = FileVersion::query()
            ->where('created_at', '<=', $threshold)
            ->where('keep', false)
            ->where('current', false)
            ->whereDoesntHave('file', function ($query) {
                $query->where('keep', true);
            });

        $bar = $this->output->createProgressBar($query->count());

        $query->with('file')->chunk(100, function ($versions) use ($bar) {
            /** @var FileVersion $version */
            foreach ($versions as $version) {
                $this->manager->deleteS3Version($version->file, $version->s3_id);
                $version->delete();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->info('Finished');
    }
}
