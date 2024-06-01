<?php

namespace App\Http\Controllers\Internal;

use App\Models\Folder;
use App\Models\Share;
use App\Models\Shortcut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class ShortcutController extends Controller {
    public function store(Request $request) {
        $this->validate($request, [
            'folder_id' => 'integer',
            'share_id'  => 'required|integer'
        ]);

        /** @var Share $share */
        $share = Share::findOrFail($request->input('share_id'));

        $this->authorize('create_shortcut', $share);

        /** @var Folder $folder */
        $folder = $request->has('folder_id') ? Folder::findOrFail($request->input('folder_id')) : null;

        Shortcut::checkTarget($folder);

        $data = [
            'user_id'  => Auth::id(),
            'share_id' => $share->id
        ];

        if (Shortcut::query()->where($data)->count() > 0) {
            abort(400, __('shortcuts.already_created'));
        }

        $data['folder_id'] = $request->input('folder_id', null);
        $shortcut = Shortcut::create($data);

        return $shortcut;
    }

    public function move(Shortcut $shortcut, Request $request) {
        $this->authorize('move', $shortcut);

        $this->validate($request, [
            'folder_id' => 'integer'
        ]);

        /** @var Folder $folder */
        $folder = $request->has('folder_id') ? Folder::findOrFail($request->input('folder_id')) : null;
        Shortcut::checkTarget($folder);

        $shortcut->folder_id = $request->input('folder_id', null);
        $shortcut->save();

        return response()->json([
            'message' => __('shortcuts.moved')
        ]);
    }

    public function destroy(Shortcut $shortcut) {
        $this->authorize('delete', $shortcut);

        $shortcut->delete();

        return response()->json([
            'message' => __('shortcuts.deleted')
        ]);
    }
}
