<?php

namespace App\Compression;

use ZipStream\ZipStream;
use Illuminate\Support\Collection;
use App\Models\File;
use App\Models\Folder;
use Storage,Auth;

class StreamArchive implements Archive{
    
    protected $name;
    protected $tree;
    protected $user;
    
    public function __construct($name){
        $this->name = $name;
        $this->tree = [];
        $this->user = Auth::user();
    }
    
    public function addFile(File $file,$path = ''){
        $this->tree[] = [
            'name' => $path.$file->title,
            'path' => $file->path
        ];
    }
    
    public function addFiles(Collection $files,$path = ''){
        foreach ($files as $file) {
            $this->addFile($file,$path);
        }
    }
    
    public function addFolder(Folder $folder,$path = ''){
        $this->addFiles($folder->files,$path.$folder->title.'/');
        $all_subfolders = Folder::where('folder_id',$folder->id)
                         ->orWhere(function($query) use($folder){
                             $query->shared($this->user->id,$folder->team_id,$folder->id);
                         })
                         ->get();
                         
        $this->addFolders($all_subfolders,$path.$folder->title.'/');
    }
    
    public function addFolders(Collection $folders,$path = ''){
        foreach ($folders as $folder) {
            $this->addFolder($folder,$path);
        }
    }
    
    public function compress(){
        
        return function(){
            Storage::getDriver()->getAdapter()->getClient()->registerStreamWrapper();
            
            $zip = new ZipStream($this->name, []);
            foreach ($this->tree as $item) {
        
                $bucket = config('filesystems.disks.s3.bucket');
                $resource = "s3://" . $bucket . "/" . $item['path'];
        
                $stream = fopen($resource, 'r');
                $zip->addFileFromStream($item['name'], $stream);
            }
        
            $zip->finish();
        };
    }
    
    public function setUser($user){
        $this->user = $user;
    }
}