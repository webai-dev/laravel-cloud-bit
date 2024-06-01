<?php

namespace App\Traits;

use App\Models\Share;
use App\Sharing\Shareable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Folder;
use App\Models\Lock;
use App\Indexing\Jobs\IndexMovedItem;

trait MovesItems {

    public function move($id, Request $request) {
        $this->validate($request, [
            'folder_id' => 'exists:folders,id',
        ]);

        $item = $this->find($id, $request);

        $folder = $request->has('folder_id') ? Folder::find($request->input('folder_id')) : null;

        $this->check($item, $folder);

        $item = $this->moveTo($item, $folder);

        $path = $item->getPathFor(Auth::id());

        return response()->json(compact('item', 'path'));
    }


    protected function check(Shareable $item, Folder $folder = null) {
        //Check source permissions
        $this->authorize('edit', $item);
        $user_id = Auth::id();
        $parent = $item->folder;

        $parent_is_inaccessible = $parent != null && !$parent->hasPermissionFor('view', $user_id);
        //A shared item cannot be moved if it is the top-level share for the current user
        if ($item->is_shared && $item->user_id != $user_id
            && ($parent_is_inaccessible || $parent == null)) {
            abort(400, __('permissions.move_shared_error', ['item' => $item->getType()]));
        }

        // A not owned item cannot be moved to a different subtree
        if ($item->owner_id != $user_id &&
            ($folder == null || !$folder->hasCommonAncestorWith($item))){
            abort(400, __('permissions.move_different_subtree'));
        }

        if ($folder == null) {
            return;
        }

        if ($folder->id == $item->folder_id){
            abort(400,__('permissions.move_same'));
        }

        // Check target permissions
        $this->authorize('edit', $folder);

        $item_shares = $item->shares;
        $folder_shares = $folder->getAncestorShares();
        $folder_shares = $folder_shares->merge($folder->shares);
        $shared_descendants = $item instanceof Folder ? $item->getFirstSharedDescendants() : [];

        /** @var Share $folder_share */
        foreach ($folder_shares as $folder_share) {
            foreach ($item_shares as $item_share) {
                // Cannot move shared items
                // into (directly or indirectly) shared folders
                // with higher permissions than the item for the same user
                if ($folder_share->user_id == $item_share->user_id
                    && $item_share->comparePermissions($folder_share) == 1) {
                    abort(400, __("shares.shared_movement"));
                }
            }

            // Cannot move folders with shared descendants
            // into (directly or indirectly) shared folders
            // with higher permissions than any one of the shared descendants for any of the shared users
            foreach ($shared_descendants as $shared_descendant) {
                /** @var Share $descendant_share */
                $descendant_share = $shared_descendant->getShareFor($folder_share->user_id);
                if ($descendant_share != null
                    && $descendant_share->comparePermissions($folder_share) == 1) {
                    abort(400, __("shares.shared_movement"));
                }
            }
        }

        // Folders cannot be moved into themselves, or into their descendants
        if ($item instanceof Folder && (
                $item->id == $folder->id ||
                $folder->isDescendantOf($item)
            )
        ) {
            abort(400, __('folders.move_error_same'));
        }
    }

    protected function moveTo(Shareable $item, Folder $folder = null) {
        $item->trace('move');

        Shareable::$update_index = false;

        $item->folder_id = $folder == null ? null : $folder->id;
        $item->save();

        $item->shares_count = $item->shares()->count();

        $locks = $item->locks()->get();

        foreach ($locks as $lock) {
            if (!$folder->isRecursivelySharedWith($lock->user_id)) {
                $lock->delete();
            }
        }

        Shareable::$update_index = true;

        dispatch(new IndexMovedItem($item));

        return $item;
    }

}