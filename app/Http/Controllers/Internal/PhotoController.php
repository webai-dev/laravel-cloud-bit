<?php

namespace App\Http\Controllers\Internal;

use Illuminate\Http\Request;
use Image,Storage,Auth;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;

class PhotoController extends Controller
{
    protected $sizes = [
        'xs' => [50,50],
        'md' => [400,400],
        'lg' => [1000,1000]
    ];
    
    public function index(){
        $files = Storage::files("images");
        $files = collect($files)->map(function($file){
            return Storage::cloud()->url($file);
        });
        return response()->json($files);
    }
    
    public function store(Request $request){
        $this->validate($request,[
            'photo'=>'required|image',
            'size' => 'in:'.implode(",",array_keys($this->sizes))
        ]);
        
        $urls = [];
        $ext = $request->photo->extension();
        $base_name = time();
        
        $image = File::get($request->photo);
        
        if($request->has('size')){
            $image = Image::make($request->photo);
            $dimension = $this->sizes[$request->size];
            $image = $image->fit($dimension[0], $dimension[1]);
            $image = (string) $image->encode('jpg',75);
        }
        
        $user = Auth::user();
        $filename = $base_name."_".$request->input('size','original').".$ext";
        $path = "images/user_".$user->id."/$filename";
        
        Storage::put($path,$image);
        $urls[] = Storage::cloud()->url($path);
        
        return response()->json([
            'message'=> 'Image Uploaded',
            'urls'    => $urls
        ]);
    }
    
    public function destroy($photo,Request $request){
        Storage::delete("images/$photo");
        return response()->json([
            'message'=>'Image Deleted',
        ]);
    }
}
