<?php

namespace App\Http\Controllers\Internal;

use App\Enums\Roles;
use App\Http\Controllers\Controller;
use App\Models\Share;
use App\Models\Teams\Team;
use App\Models\Teams\TeamShareable;
use App\Sharing\Shareable;
use App\Sharing\ShareableFactory;
use App\Sharing\ShareManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller {

    public function index(Request $request) {
        $this->validate($request, [
            'shareable_type' => 'string|in:bit,file,folder'
        ]);
        $team = $request->getTeam();

        $type = $request->input('shareable_type', 'folder');

        $shareables = ShareableFactory::query($type);

        $result = $shareables->whereHas('shares', function (Builder $shares) use ($request, $team) {
            $shares->where('user_id', Auth::id())
                ->where('team_id', $team->id);
        })
            ->when($request->input('shareable_type') == "bit", function (Builder $query) {
                return $query->with('type')->withColor(Auth::id());
            })
            ->withRenamedTitle(Auth::id())
            ->withLocked(Auth::id())
            ->withCount('shares')
            ->with('shares.shortcuts')
            ->orderBy('title', 'ASC')
            ->get()
            ->map(function (Shareable $item) {
                $share = $item->shares->where('user_id', '=', Auth::id())->first();
                $shortcut = $share->shortcuts->first();

                $item->share = $share;
                $item->folder_id = null;
                $item->shortcut_id = $shortcut ? $shortcut->id : null;

                return $item;
            });

        return $result;
    }

    public function store(Request $request) {
        $this->validate($request, [
            'team_id'        => 'required',
            'shareable_id'   => 'required',
            'shareable_type' => 'required|in:folder,bit,file',
            'share'          => 'required|in:0,1',
            'edit'           => 'required|in:0,1',
            'users'          => 'array',
            'users.*'        => 'integer|exists:users,id'
        ]);
        $item = ShareableFactory::find($request->input('shareable_type'), $request->input('shareable_id'));

        $shares = $this->createSingle($item, $request);

        ShareManager::store($shares, true, !$request->has('users'));

        return response()->json([
            'message'      => __("shares.created", ['item' => $item->getType()]),
            'shares_count' => $item->shares()->count(),
            'shares'       => $item->shares()->get()
        ]);
    }

    public function storeBulk(Request $request) {
        $this->validate($request, [
            'team_id'                => 'required',
            'items'                  => 'required|array|min:1',
            'items.*.shareable_id'   => 'required',
            'items.*.shareable_type' => 'required|in:folder,bit,file',
            'share'                  => 'required|in:0,1',
            'edit'                   => 'required|in:0,1',
            'users'                  => 'array',
            'users.*'                => 'integer|exists:users,id'
        ]);

        $all_shares = [];

        foreach ($request->input('items', []) as $item) {
            $shareable = ShareableFactory::find($item['shareable_type'], $item['shareable_id']);

            $all_shares = array_merge($all_shares, $this->createSingle($shareable, $request));
        }

        ShareManager::store($all_shares, true, !$request->has('users'));

        return response()->json([
            'message' => __('shares.created_bulk'),
            'data'    => $all_shares
        ]);
    }

    protected function createSingle(Shareable $item, Request $request) {
        $params = [
            'edit'  => $request->input('edit'),
            'share' => $request->input('share'),
        ];
        if ($request->has('users')) {
            $params['user_ids'] = $request->input('users');
        } else {
            $team = Team::find($request->input('team_id'));
            $existing = $item->shares()->pluck('user_id')->toArray();

            $user_ids = $team->users()
                ->where('id', '!=', Auth::id())
                ->whereNotIn('id', $existing)
                ->whereDoesntHave('roles', function ($query) use ($team) {
                    $query->where('label', Roles::GUEST)
                        ->where('user_team_roles.team_id', $team->id);
                })
                ->pluck('id')->toArray();
            $params['user_ids'] = $user_ids;
        }
        return ShareManager::make($item, $params);
    }

    public function permissions(Request $request) {
        $shareable = ShareableFactory::find($request->input('shareable_type'), $request->input('shareable_id'));

        $this->authorize('view', $shareable);

        $properties = [
            'shareable_type' => $shareable->getType(),
            'shareable_id'   => $shareable->id,
        ];

        $shares = $shareable->getAncestorSharesQuery(true)
            ->join('users', 'users.id', '=', 'shares.user_id')
            ->whereRaw('user_id != created_by_id')//Exclude owner shares
            ->select([
                'shares.id',
                'shares.shareable_id',
                'users.id AS user_id',
                'users.name',
                'users.email',
                'users.photo',
                'share',
                'edit'
            ])
            ->get();

        $owner = $shareable->owner;
        $creator = $shareable->creator;

        $team_shared = TeamShareable::where($properties)->exists();

        return response()->json(compact('shares', 'owner', 'creator', 'team_shared'));
    }

    public function update(Share $share, Request $request) {
        $this->authorize('update', $share);

        $share->edit = $request->input('edit', $share->edit);
        $share->share = $request->input('share', $share->share);
        $share->save();

        return $share;
    }

    public function destroy(Share $share) {
        ShareManager::revoke($share);

        return $share;
    }

    public function destroyBulk(Request $request) {
        $this->validate($request, [
            'shares'   => 'array',
            'shares.*' => 'integer'
        ]);

        $shares = Share::query()
            ->whereIn('id', $request->input('shares', []))
            ->where('user_id', Auth::id())
            ->get();

        $shares->each(function (Share $share) {
            ShareManager::revoke($share);
        });

        return $shares;
    }

    public function destroyTeam(Request $request) {
        $this->validate($request, [
            'shareable_id'   => 'required',
            'shareable_type' => 'required|in:folder,bit,file',
        ]);

        $properties = $request->only(['shareable_id', 'shareable_type']);
        $properties['team_id'] = $request->getTeam()->id;

        $shares = Share::where($properties)->get();

        $shares->each(function ($share) {
            ShareManager::revoke($share);
        });

        TeamShareable::where($properties)->delete();

        return $shares;
    }
}
