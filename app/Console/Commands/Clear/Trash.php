<?php

namespace App\Console\Commands\Clear;

use Illuminate\Console\Command;

use App\Models\File;
use App\Models\Folder;
use App\Models\Bits\Bit;

use App\Util\FileUtils;

use App\Services\Bits\BitService;

use App\Exceptions\BitServiceException;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Trash extends Command{

    protected $signature = 'clear:trash
                            {days=30 : The number of days after which to clear deleted files}
                            {--no-confirm : Whether to skip confirmation prompt}';
                            
    protected $description = 'Deletes all trashed items older than days specified';

    protected $service;

    public function __construct(BitService $service) {
        $this->service = $service;
        parent::__construct();
    }

    public function handle(){
        $date = Carbon::now()->subDays($this->argument('days'));
        
        $files = File::trashedBefore($date)->get();
        $folders = Folder::trashedBefore($date)->get();
        $bits = Bit::trashedBefore($date)->get();
                    
        if (!$this->option('no-confirm')) {
            $size = FileUtils::getHumanSize($files->sum('size'));
            
            $confirmed = $this->confirm("A total of ".
                $files->count()." files ($size), ".
                $folders->count()." folders and ".
                $bits->count()." bits ".
                "will be deleted. Continue?");
                
            if (!$confirmed) {
                $this->info("Operation aborted");
                return;
            }
        }
        
        $this->info("Deleting files...");
        foreach ($files as $file) {
            Storage::delete($file->path);
            $file->forceDelete();
        }
        
        $this->info("Deleting bits...");
        foreach ($bits as $bit) {
            $this->service->setType($bit->type);
            
            try {
                $this->service->remove($bit);
            } catch (BitServiceException $e) {
                $this->warn("Error when deleting bit  ".$bit->id." from service: ".$e->getMessage());
            }
            
            $bit->forceDelete();
        }
        
        $this->info("Deleting folders...");
        Folder::whereIn("id",$folders->pluck('id')->toArray())->forceDelete();
        
        $this->info("Completed");
    }
}
