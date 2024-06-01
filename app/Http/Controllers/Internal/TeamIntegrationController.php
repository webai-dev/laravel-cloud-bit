<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Teams\Integration;
use App\Models\Teams\Team;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TeamIntegrationController extends Controller {

    public function index(Team $team) {
        $this->authorize('view',Integration::class);
        return $team->integrations()
            ->with('folders')
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function store(Team $team, Request $request) {
        $this->authorize('create',Integration::class);

        $this->validate($request, [
            'url' => 'required|string',
            'folders' => 'required|array'
        ]);

        $user = Auth::user();
        $folders = Folder::whereIn('id', $request->input('folders'))->get();
        foreach($folders as $folder){
            if(!$folder->hasPermissionFor('share',$user->id))
                abort(400, "You don't have the permission to share these folders");
        }

        $integration = new Integration();
        $integration->team_id = $team->id;
        $integration->user_id = $user->id;
        $integration->name = $request->input('name','New Integration');
        $integration->key = Str::random(16);
        $integration->secret = encrypt(Str::random(64));
        $integration->url = $request->input('url', '');
        $integration->save();

        $integration->folders()->attach($folders);

        return $integration;
    }

    public function update(Team $team,Integration $integration, Request $request) {
        $this->authorize('update',Integration::class);

        $this->validate($request, [
            'url' => 'string',
            'folders' => 'array'
        ]);

        $user = Auth::user();

        $integration->user_id = $user->id;
        if($request->input('url')) {
            $integration->url = $request->input('url', '');
        }
        if($request->input('folders')){
            $folders = Folder::whereIn('id', $request->input('folders'))->get();
            foreach($folders as $folder){
                if(!$folder->hasPermissionFor('share',$user->id))
                    abort(400, "You don't have the permission to share these folders");
            }
            $integration->folders()->detach();
            $integration->folders()->attach($folders);
        }
        $integration->save();

        return $integration;
    }

    public function destroy(Team $team,$id) {
        $this->authorize('delete',Integration::class);

        $integration = $team->integrations()->findOrFail($id);
        $integration->delete();
        return $integration;
    }


    public function folders(Request $request){
        return $request->getIntegration()->folders;
    }
}
