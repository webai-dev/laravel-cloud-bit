<?php

namespace App\Console\Commands\Index;

use Illuminate\Console\Command;
use App\Services\IndexingService;
use App\Models\File;
use App\Models\Folder;
use App\Models\Bits\Bit;
use App\Indexing\Document;
use App\Indexing\Jobs\IndexCreatedItem;

class Shareables extends Command
{
    const CHUNK_SIZE = 10;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:shareables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Indexes all shareables in elasticsearch';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(IndexingService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->info("Indexing files...");
        File::query()->with(['team.users'])
            ->chunk(self::CHUNK_SIZE,function($items){
                $this->index($items);
            });

        $this->info("Indexing bits...");
        Bit::query()->with(['tags','team.users'])
            ->chunk(self::CHUNK_SIZE,function($items){
                $this->index($items);
            });

        $this->info("Indexing folders...");
        Folder::query()->with(['team.users'])
            ->chunk(self::CHUNK_SIZE,function($items){
                $this->index($items);
            });
    }


    protected function index($shareables){
        $docs = [];

        $bar = $this->output->createProgressBar(count($shareables));
        foreach ($shareables as $shareable) {
            $docs = array_merge($docs,$shareable->toDocumentArray());
            $bar->advance();
        }
        $bar->finish();
        $this->info("");

        foreach($docs as $doc){
            $this->service->index($doc);
        }
    }
}
