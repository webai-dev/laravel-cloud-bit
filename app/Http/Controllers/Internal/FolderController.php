<?php

namespace App\Http\Controllers\Internal;

use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\InteractsWithShareables;
use App\Http\Controllers\Controller;

class FolderController extends Controller {
    use InteractsWithShareables;

    public function store(Request $request) {
        $this->validate($request, [
            'title'   => 'required|max:255',
            'team_id' => 'required|exists:teams,id',
        ]);

        $this->authorize('create', Folder::class);

        $user = Auth::user();
        $folder = new Folder($request->all());

        if ($request->has('folder_id')) {
            /** @var Folder $parent */
            $parent = Folder::findOrFail($request->input('folder_id'));
            $this->authorize('edit', $parent);

            $folder->owner_id = $parent->owner_id;
        } else {
            $folder->owner_id = Auth::id();
        }

        $folder->user_id = $user->id;
        $folder->save();

        $folder->shares_count = 0;

        return $folder;
    }

    public function update(Request $request, Folder $folder) {
        //Only view permissions required, because the title can be different for every shared user
        $this->authorize('view', $folder);

        $folder->trace('rename');
        $folder->title = $request->input('title', $folder->title);
        $folder->save();

        return $folder;
    }

    public function destroy(Folder $folder) {
        $this->authorize('delete', $folder);

        $folder->forceDelete();

        return $folder;
    }
}
