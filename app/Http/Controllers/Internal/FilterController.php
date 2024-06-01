<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\DefaultFilter;
use App\Models\FolderFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FilterController extends Controller{
    
    public function show(Request $request){
        
        $this->validate($request,[
            'team_id' => 'required|integer',
            'is_shares' => 'required'
        ]);
        
        $user = Auth::user();
        
        return $this->getFoldersFilters($request, $user);
    }
    
    protected function getFoldersFilters($request, $user){
        
        $folder_id = $request->folder_id == 'null' ? null : $request->folder_id;
        
        $filters = FolderFilter::select([
                'sort_by',
                'bits_order',
                'folders_order',
                'files_order',
                'bits_collapse',
                'folders_collapse',
                'files_collapse',
                'fill_gaps'
            ])
            ->where('user_id',$user->id)
            ->where('folder_id',$folder_id)
            ->where('is_shares',$request->is_shares)
            ->first();
        
        return $filters ? $filters : $this->getDefaultFilters($request, $user);
    }
    
    protected function getDefaultFilters($request, $user){
         
         $filters = DefaultFilter::select([
                'sort_by',
                'bits_order',
                'folders_order',
                'files_order',
                'bits_collapse',
                'folders_collapse',
                'files_collapse',
                'fill_gaps'
            ])
            ->where('user_id',$user->id)
            ->where('team_id',$request->team_id)
            ->first();
        
        return $filters ? $filters : $this->getPresetFilters();
    }
    
    protected function getPresetFilters(){
        return [
                'sort_by' => 'alphabetical_ascending',
                'bits_order' => 0,
                'folders_order' => 1,
                'files_order' => 2,
                'bits_collapse' => false,
                'folders_collapse' => false,
                'files_collapse' => false,
                'fill_gaps' => true
            ];
    }
    
    public function store(Request $request){
        
        $user_id = Auth::user()->id;
        
        $filters = $this->getFiltersFromRequest($request); 
        
        if(!$request->has('team_id')){
            
            $folder_id = $request->folder_id == 'null' ? null : $request->folder_id;
            
            $existing = FolderFilter::select(['id'])
                ->where('user_id',$user_id)
                ->when($folder_id == null, function($query){
                    return $query->whereNull('folder_id');
                })
                ->when($folder_id != null, function($query) use($folder_id){
                    return $query->where('folder_id', $folder_id);
                })
                ->where('is_shares',$request->is_shares)
                ->first();
            
            $payload = [
                    'user_id' => $user_id,
                    'folder_id' => $folder_id,
                    'is_shares' => $request->is_shares,
                ];
            $payload = array_merge($filters, $payload);
            
            if($existing == null){
                FolderFilter::create($payload);
            } else {
                FolderFilter::where('id',$existing->id)->update($payload);
            }
            
        } else {
            
            $existing = DefaultFilter::select(['id'])
                ->where('user_id',$user_id)
                ->where('team_id',$request->team_id)
                ->first();
                
            $payload = [
                'user_id' => $user_id,
                'team_id' => $request->team_id,
            ];
            $payload = array_merge($filters, $payload);
            
            if($existing == null){
                DefaultFilter::create($payload);    
            } else {
                 DefaultFilter::where('id',$existing->id)->update($payload);  
            }
            
        }
        
        return 'OK !';
    }
    
    protected function getFiltersFromRequest(Request $request){
        $filters = [
            'sort_by' => 'alphabetical_ascending',
            
            'bits_order' => 0,
            'folders_order' => 1,
            'files_order' => 2,
            
            'bits_collapse' => false,
            'folders_collapse' => false,
            'files_collapse' => false,
            
            'fill_gaps' => true
        ];
        
        foreach ($filters as $key => $default){
            $filters[$key] = $request->input($key, $default);
        }
        
        return $filters;
    }
    
}