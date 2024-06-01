<?php

namespace App\Console\Commands\Index;

use Illuminate\Console\Command;
use App\Services\IndexingService;
use Log;

class Delete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:delete 
                            {--index= : The index to clear,ES_INDEX by default}
                            {--no-confirm : Whether to skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes the specified index';

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

        $index =$this->option('index') ? $this->option('index') : config('elasticsearch.index');
        
        if(!$this->option('no-confirm') && !$this->confirm("Index '$index' will be deleted, continue?")){
            $this->info("Operation aborted");
            return;
        }
        
        $this->service->getClient()->indices()->delete(compact('index'));
        $this->info("Index deleted");
    }
}
