<?php

namespace App\Sharing;

use App\Models\Folder;
use App\Models\Share;
use App\Models\Teams\TeamShareable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HandlesSharing {

    /**
     * Creates a share for this item with the specified user and permissions
     * Note: only for use in tests - does not perform checks or dispatch events
     * @param User $user The user to share the item with
     * @param string $permission The permission to share the item with
     * @param null $by The id of the user that created the share, or the creator by default
     * @return Share|\Illuminate\Database\Eloquent\Model
     */
    public function shareWith(User $user, $permission = 'view', $by = null) {
        $share = Share::create([
            'team_id'        => $this->team_id,
            'user_id'        => $user->id,
            'shareable_type' => $this->getType(),
            'shareable_id'   => $this->id,
            'edit'           => ($permission == 'edit' || $permission == 'share') ? 1 : 0,
            'share'          => $permission == 'share' ? 1 : 0,
            'created_by_id'  => $by == null ? $this->user_id : $by
        ]);

        $this->is_shared = true;
        $this->save();

        return $share;
    }

    /**
     * Removes all direct and team shares from this item
     * @throws \Exception
     */
    public function unshare() {
        if (!$this->is_shared) {
            return;
        }

        $attributes = [
            'shareable_type' => $this->getType(),
            'shareable_id'   => $this->id
        ];

        TeamShareable::where($attributes)->delete();

        $share_ids = Share::where($attributes)->pluck('id')->toArray();

        //Shortcuts are deleted with cascade
        Share::query()->whereIn('id', $share_ids)->delete();

        $this->is_shared = false;
        $this->save();
    }

    /**
     * Returns whether this item is shared recursively with at least one of the specified user(s)
     * @param int/array $user_id
     * @return bool
     */
    public function isRecursivelySharedWith($user_id) {
        $current = $this;
        do {
            $share = $current->getShareFor($user_id);

            if ($share != null) {
                return true;
            }

            $current = $current->folder;
        } while ($current != null);

        return false;
    }

    public function isParentRecursivelySharedWith($user_id) {
        if ($this->folder) {
            return $this->folder->isRecursivelySharedWith($user_id);
        }

        return false;
    }

    /**
     * Returns whether the specified item is directly shared with at least one of the specified user/users
     * @param int|array $user_id
     * @return bool
     */
    public function isDirectlySharedWith($user_id) {
        return $this->getShareFor($user_id) != null;
    }

    /**
     * Returns the share of this item for the specified user/users, or null if not shared
     * @param int|array $user_id
     * @return Share|null
     */
    public function getShareFor($user_id) {
        if (!$this->is_shared) {
            return null;
        }

        return $this->shares()
            ->where(function (Builder $query) use ($user_id) {
                if (is_array($user_id)) {
                    $query->whereIn('user_id', $user_id);
                } else {
                    $query->where('user_id', $user_id);
                }
            })->first();
    }

    /**
     * Returns the first share of this item for the specified user/users recursively,
     * including itself, or null if not recursively shared
     * @param int|array $user_id
     * @return Share|null
     */
    public function getShareForRecursively($user_id) {
        if ($this->is_shared) {
            return $this->getShareFor($user_id);
        }

        if ($this->folder == null) {
            return null;
        }
        /** @var Folder $folder */
        $folder = $this->folder;
        $share = $folder->getShareFor($user_id);

        if ($share != null) {
            return $share;
        }

        return $folder->getShareForRecursively($user_id);
    }

    /**
     * Returns all the shares of the ancestors of this item
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getAncestorShares() {
        return $this->getAncestorSharesQuery()->get();
    }

    public function getAncestorSharesQuery($include_self = false) {
        $table = $this->getTable();
        $id = $this->id;
        $sql = "shares.id IN (
                        WITH RECURSIVE ancestry AS (
                          SELECT id,user_id,folder_id
                          FROM
                            $table
                            WHERE id = $id
                          UNION ALL
                            SELECT folders.id,folders.user_id,folders.folder_id
                            FROM folders
                          INNER JOIN ancestry ON ancestry.folder_id = folders.id
                        )
                        SELECT shares.id FROM shares
                        INNER JOIN ancestry ON ancestry.folder_id = shares.shareable_id AND shareable_type = 'folder'
                    )";

        if ($include_self) {
            $type = $this->getType();
            $sql = "($sql OR (shares.shareable_type = '$type' AND shares.shareable_id = $id))";
        }
        return Share::query()
            ->whereRaw($sql);
    }

    /**
     * Returns the path of the item for the specified user
     * @param $user_id
     * @return array|null
     */
    public function getPathFor($user_id) {
        $path = [];
        $current = $this;

        do {
            $path[] = [
                'id'          => $current->id,
                'title'       => $current->title,
                'folder_id'   => $current->folder_id,
                'in_shared'   => $current->is_shared && $current->user_id != $user_id,
                'shared_with' => $current->shares()->pluck('user_id')
            ];

            $share = $current->getShareFor($user_id);

            if ($share == null) {
                //If the item isn't shared and has no parent, only the creator can view it
                if ($current->folder_id == null && $current->user_id != $user_id) {
                    return null;
                }

                //If the item isn't shared, maybe its parent is
                $current = $current->folder;
            } else {
                //Continue moving up the path for the current user
                $current = $share->folders()
                    ->where('share_folders.user_id', '=', $user_id)
                    ->first();

                if ($current != null) {
                    //Amend previous item's parent to correct one
                    $path[count($path) - 1]['folder_id'] = $current->id;

                    $current->setRenamedTitle($share->rename);
                }
            }

        } while ($current != null);

        return $path;
    }

    /**
     * Returns whether this item has the specified permission for the specified user
     * @param string $permission The permission to check for
     * @param int $user_id The id of the user
     * @return boolean
     */
    public function hasPermissionFor($permission, $user_id) {
        return self::query()
            ->where('id', $this->id)
            ->where(function (Builder $builder) use ($permission, $user_id) {
                $table = $this->getTable();
                $id = $this->id;

                //View permissions
                $condition = '(true)';

                if ($permission == 'edit') {
                    $condition = "shares.edit";
                }

                if ($permission == 'share') {
                    $condition = "shares.edit AND shares.share";
                }

                $builder->whereHas('shares', function (Builder $query) use ($user_id, $condition) {
                    $query->whereRaw("($condition)")
                        ->where('user_id', $user_id);
                })
                    ->orWhere('owner_id', $user_id)
                    //Is recursively shared
                    ->orWhereRaw(
                        "$user_id IN (
                        WITH RECURSIVE permissions AS (
                          SELECT id,user_id,folder_id
                          FROM
                            $table
                            WHERE id = $id
                          UNION ALL
                            SELECT folders.id,folders.user_id,folders.folder_id
                            FROM folders
                          INNER JOIN permissions ON permissions.folder_id = folders.id
                        )
                        SELECT shares.user_id FROM shares
                        INNER JOIN permissions ON permissions.folder_id = shares.shareable_id AND shareable_type = 'folder'
                        WHERE ($condition)
                    )     
                ");
            })
            ->exists();
    }

    /**
     * Returns whether this item is recursively owned by the specified user
     * (i.e. they own a folder higher in the hierarchy)
     * @param $user_id
     * @return bool
     */
    public function isRecursivelyOwnedBy($user_id) {
        return $this->owner_id == $user_id;
    }

    public function scopeShared(Builder $query, $user_id, $team_id, $folder_id) {
        return
            $query->whereHas('shares.folders', function (Builder $folders) use ($team_id, $user_id, $folder_id) {
                $folders->where('share_folders.user_id', $user_id)
                    ->where('share_folders.folder_id', $folder_id);
            });
    }

}