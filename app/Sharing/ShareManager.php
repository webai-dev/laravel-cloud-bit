<?php

namespace App\Sharing;

use App\Events\ItemShared;
use App\Events\PermissionRevoked;
use App\Models\Folder;
use App\Models\Share;
use App\Models\Teams\TeamShareable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ShareManager {

    /**
     * Creates an array of Shares for the specified user ids, running all relevant checks
     * @param Shareable $item
     * @param array $attributes An array of attributes for the shares: user_ids, edit, share
     * @param boolean $skip_existing Whether to skip existing shared users
     * @return array
     */
    public static function make(Shareable $item, array $attributes, $skip_existing = false) {
        Gate::authorize('share', $item);

        $details = [
            'title' => $item->title,
            'item'  => $item->getType(),
        ];
        $user_ids = array_get($attributes, 'user_ids', []);

        $shares = [];
        $shared_descendants = $item instanceof Folder ? $item->getFirstSharedDescendants() : [];

        $permission = 'view';

        if (array_get($attributes, 'edit'))
            $permission = 'edit';
        if (array_get($attributes, 'share'))
            $permission = 'share';

        foreach ($user_ids as $user_id) {
            $existing = $item->getShareForRecursively($user_id);

            // If the item is directly shared, it can't be shared again with the same person
            if ($existing != null && $existing->shareable_id == $item->id && !$skip_existing) {
                abort(400, __('shares.already_shared', $details));
            }

            if ($item->hasPermissionFor($permission, $user_id)) {
                $details['permission'] = $permission;
                abort(400, __('shares.has_permission', $details));
            }

            $share = new Share([
                'shareable_id'   => $item->id,
                'shareable_type' => $item->getType(),
                'user_id'        => $user_id,
                'edit'           => array_get($attributes, 'edit', false),
                'share'          => array_get($attributes, 'share', false),
                'team_id'        => $item->team_id,
                'created_by_id'  => Auth::id()
            ]);

            //If the item is recursively shared, the existing share must not have more permissions
            if ($existing != null && $existing->comparePermissions($share) == 1) {
                abort(400, __('shares.already_shared', $details));
            }

            //Cannot share folders with shared descendants
            //at least one of which has higher permissions for this user
            foreach ($shared_descendants as $shared_descendant) {
                /** @var Share $descendant_share */
                $descendant_share = $shared_descendant->getShareFor($user_id);
                if ($descendant_share != null && $descendant_share->comparePermissions($share) == 1) {
                    abort(400, __("shares.shared_movement"));
                }
            }

            $shares[] = $share;
        }

        return $shares;
    }


    /**
     * Saves the specified array of Shares, triggering relevant events
     * @param array $shares
     * @param bool $notify Whether to notify the recipients of the shares
     * @param bool team_share Whether to assure a team share exists
     */
    public static function store(array $shares, $notify = true, $team_share = false) {
        /** @var Share $share */
        foreach ($shares as $share) {
            $share->save();

            if ($team_share) {
                TeamShareable::updateOrCreate([
                    'team_id'        => $share->team_id,
                    'shareable_id'   => $share->shareable_id,
                    'shareable_type' => $share->shareable_type,
                    'created_by_id'  => $share->created_by_id
                ], [
                    'edit'  => $share->edit,
                    'share' => $share->share
                ]);
            }

            event(new ItemShared($share, $notify));
        }
    }

    /**
     * Removes the specified share, triggering relevant events
     * @param Share $share
     * @return void
     * @throws \Exception
     */
    public static function revoke(Share $share) {
        Gate::authorize('delete', $share);

        event(new PermissionRevoked($share));

        $shareable = $share->shareable;
        $share->delete();

        if ($shareable->shares()->count() == 0) {
            $shareable->is_shared = false;
            $shareable->save();
        }
    }
}