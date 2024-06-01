<?php

namespace App\Sharing;

use App\Models\Folder;
use App\Models\Share;

use App\Models\Teams\Team;
use App\Models\User;
use App\Sharing\Visitors\ShareableVisitor;
use App\Traits\ExecutesSQL;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Indexing\Documents\ShareableDocument;
use App\Indexing\Documentable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\AddsLockedScope;
use App\Indexing\Indexable;
use App\Tracing\Traits\Traceable;
use Illuminate\Support\Facades\Auth;

/**
 * Class Shareable
 * @package App\Sharing
 * @property int id
 * @property int user_id
 * @property int owner_id
 * @property int team_id
 * @property int folder_id
 * @property string title
 * @property Folder folder
 * @property boolean is_shared
 * @property-write boolean in_shared
 * @property-write integer shortcut_id
 * @property boolean has_shared_parent Whether this item has a shared ancestor (deprecated)
 * @property User owner
 * @property Team team
 * @property Collection tags
 * @property Collection shares
 * @property Collection activities
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property int shares_count
 * @property-write Share share
 * @method static Builder trashedBefore($date)
 */
class Shareable extends Model implements Documentable {

    use HandlesSharing, HandlesRenaming;
    use Indexable, Traceable, SoftDeletes, AddsLockedScope;
    use ExecutesSQL;

    public function getType() {
        return 'shareable';
    }

    public function owner() {
        return $this->belongsTo('App\Models\User', 'owner_id');
    }

    public function creator() {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function shares() {
        return $this->morphMany(Share::class, 'shareable');
    }

    public function folder() {
        return $this->belongsTo('App\Models\Folder');
    }

    public function team() {
        return $this->belongsTo('App\Models\Teams\Team');
    }

    public function locks() {
        return $this->morphMany('App\Models\Lock', 'lockable');
    }

    public function toDocumentArray() {
        $item = $this;
        $docs = [];
        $shared_with = [];

        //Walk to root collecting all user_ids this item is shared with
        do {

            $shares = $item->shares;
            if ($shares->count() > 0) {
                $shared_with = array_merge($shared_with, $shares->pluck('user_id')->toArray());
                $shared_with[] = $item->owner->id;
            }

            $item = $item->folder()->withoutGlobalScopes()->first();

            if ($item != null && $item->deleted_at != null) {
                return [];
            }

        } while ($item != null);

        //Create the owners document
        $document = $this->toDocument();
        $owner_id = $document->owner_id;
        $docs[] = $document;

        //Create a document for each user the item is visible to
        foreach ($shared_with as $user_id) {
            if ($user_id != $owner_id) {
                $document = $this->toDocument();
                $document->user_id = $user_id;
                $docs[] = $document;
            }
        }
        return $docs;
    }

    public function toDocument() {
        return new ShareableDocument([
            'user_id'        => $this->user_id, // Will actually be changed for shared
            'owner_id'       => $this->user_id,
            'team_id'        => $this->team_id,
            'folder_id'      => $this->folder_id,
            'shareable_type' => $this->getType(),
            'shareable_id'   => $this->id,
            'data'           => '',
            'tags'           => $this->tags ? $this->tags->pluck('text')->toArray() : null,
            'title'          => $this->title,
            'last_modified'  => $this->updated_at->toDateTimeString(),
        ]);
    }

    public function accept(ShareableVisitor $visitor) {
        //no op
    }

    public function hasCommonAncestorWith(Shareable $other) {
        if ($other->folder_id == null) {
            return false;
        }

        $other_ancestors = $other->getAncestors();
        $this_ancestors = $this->getAncestors();

        return $other_ancestors
                ->intersect($this_ancestors)
                ->count() > 0;
    }

    public function getAncestors() {
        if ($this->folder_id == null) {
            return collect([]);
        }
        $ancestors = $this->getQuery('ancestors', [
            'folder_id' => $this->folder_id
        ]);

        $folder = new Folder();

        return $folder->hydrate($ancestors);
    }

    public function scopeTrashedBefore($query, $date) {
        return $query->onlyTrashed()
            ->where('deleted_at', '<=', $date);
    }

    /**
     * Returns a query for the shareables with all details for the current user
     * @param int/null $folder_id
     * @param string sort_by
     * @param string sort_order
     * @param int team_id
     * @return Builder
     */
    public static function getIndexQuery($team_id, $sort_by, $sort_order = 'ASC', $folder_id = null) {
        return self::query()
            ->where('team_id', $team_id)
            ->when(!is_null($folder_id), function (Builder $query) use ($folder_id) {
                $query->where('folder_id', $folder_id);
            })
            ->when(is_null($folder_id), function (Builder $query) {
                $query->whereNull('folder_id')
                    ->where('user_id', Auth::id());
            })
            ->withLocked(Auth::id())
            ->withCount('shares')
            ->with('shares')
            ->orderBy($sort_by, $sort_order);
    }
}
