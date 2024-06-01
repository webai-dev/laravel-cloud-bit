<?php

namespace App\Console\Commands\Initialize;

use App\Uploading\PreviewManager;
use Illuminate\Console\Command;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class ImageFilePreviews extends Command{

    protected $signature = 'init:image_file_previews';
    protected $description = 'Generates preview URLs for image files';

    public function handle(PreviewManager $manager){
        $files = File::whereNull('preview_url')
                     ->whereIn('extension',['jpg','png','gif'])
                     ->get();
                     
        $this->info("Generating previews for ".$files->count()." files");
        $bar = $this->output->createProgressBar($files->count());
        
        
        foreach ($files as $file) {
            $data = Storage::get($file->path);
            $file->preview_url = $manager->generatePreviewUrl($file,$data);
            $file->save();
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->info("Finished generating previews");
    }
}
