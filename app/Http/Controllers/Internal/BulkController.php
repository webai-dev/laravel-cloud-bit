<?php

namespace App\Http\Controllers\Internal;

use App\Models\Shortcut;
use App\Sharing\Shareable;
use Illuminate\Http\Request;

use App\Models\File;
use App\Models\Folder;
use App\Models\Bits\Bit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Traits\MovesItems;
use App\Compression\StreamArchive;
use App\Http\Controllers\Controller;

class BulkController extends Controller{
    
    use MovesItems;

    public function move(Request $request){
        $this->validate($request,[
            'folder_id' => 'exists:folders,id',
            'team_id'   => 'required',
            'bits'      => 'array',
            'files'     => 'array',
            'folders'   => 'array',
            'shortcuts' => 'array'
        ]);

        list($files,$folders,$bits,$shortcuts) = $this->getModels($request);

        $folder = $request->has('folder_id') ? Folder::find($request->input('folder_id')) : null;

        $items = $bits->merge($files)->merge($folders);

        //Check all items before movement, so either everything is moved or nothing
        foreach($items as $item){
            $this->check($item,$folder);
        }

        /** @var Shortcut $shortcut */
        foreach ($shortcuts as $shortcut) {
            $this->authorize('move',$shortcut);
            Shortcut::checkTarget($folder);
        }

        $response = [];

        foreach ($items as $item) {
            $item = $this->moveTo($item,$folder);
            $path = $item->getPathFor(Auth::id());

            $response[] = compact('item','path');
        }

        foreach ($shortcuts as $shortcut) {
            $shortcut->folder_id = $request->input('folder_id');
            $shortcut->save();
        }

        return response()->json($response);
    }
    
    public function trash(Request $request){
        $this->validate($request,[
            'bits'    => 'array',
            'files'   => 'array',
            'folders' => 'array',
            'shortcuts' => 'array'
        ]);

        /** @var Collection $files*/
        list($files,$folders,$bits,$shortcuts) = $this->getModels($request);

        $items = $files->merge($folders)->merge($bits);

        $items->each(function($item){
            $this->authorize('delete',$item);
        });

        $shortcuts->each(function($shortcut){
           $this->authorize('delete',$shortcut);
        });

        $items->each(function(Shareable $item){
            $item->unshare();
            $item->delete(); 
        });

        $shortcuts->each(function (Shortcut $shortcut){
           $shortcut->delete();
        });
        
        return response()->json(['message'=>trans_choice('bulk.trash_success',$items->count())]);
    }

    public function download(Request $request){
        list($files,$folders) = $this->getModels($request);

        foreach ($files as $file) {
            $this->authorize('view',$file);
        }

        foreach ($folders as $folder) {
            $this->authorize('view',$folder);
        }

        $archive = new StreamArchive("stuff.zip");
        $archive->addFiles($files);
        $archive->addFolders($folders);

        return response()->stream($archive->compress());
    }

    protected function getModels(Request $request){
        $files = File::query()->whereIn('id',$request->input('files',[]))->get();

        $folders = Folder::query()->whereIn('id',$request->input('folders',[]))->get();

        $bits = Bit::query()->whereIn('id',$request->input('bits',[]))->get();

        $shortcuts = Shortcut::query()->whereIn('id',$request->input('shortcuts',[]))->get();

        return [$files,$folders,$bits,$shortcuts];
    }
}
