<?php

namespace App\Console\Commands\Index;

use Illuminate\Console\Command;
use App\Services\IndexingService;
use Log;

class Clear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:clear 
                            {--index= : The index to clear,ES_INDEX by default}
                            {--type=  : The mapping type to clear, ES_TYPE by default}
                            {--no-confirm : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes all documents from the specified index';

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
        $opts = [
            'index' => $this->option('index') ? $this->option('index') : config('elasticsearch.index'),
            'type'  => $this->option('type') ? $this->option('type') : config('elasticsearch.mapping_type'),
        ];
        
        if(!$this->option('no-confirm') &&
            !$this->confirm('Mapping type "'.$opts['type'].'" on index "'.$opts['index'].'" will be cleared, continue?')){
            $this->info("Operation aborted");
            return;
        }
        
        $this->service->remove([
            'match_all' => new \stdClass()
        ]);
        $this->info("Index cleared");
    }
}
