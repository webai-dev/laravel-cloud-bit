<?php

namespace App\Traits;

use App\Models\File;
use App\Models\Folder;
use App\Models\Bits\Bit;
use App\Models\Shortcut;
use App\Sharing\Shareable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait FindsItems {

    public function index(Request $request) {
        $parent = null;
        $path = [];

        if ($request->has('folder_id')) {
            $parent = $this->findWithDetails($request->input('folder_id'));

            $path = $parent->getPathFor(Auth::id());

            $parent->triggerEvent('opened');
        }

        $folder_id = $request->input('folder_id', null);
        $team_id = $request->getTeam()->id;

        $model = $this->findModel($request);
        $items = $model::getIndexQuery(
            $team_id,
            $request->input('sort_by', 'title'),
            $request->input('sort_order', 'ASC'),
            $folder_id
        )->get();

        $type = (new $model())->getType();

        /** @var Collection $shortcuts */
        $shortcuts = Shortcut::query()
            ->where('user_id', Auth::id())
            ->when(is_null($folder_id), function (Builder $query) {
                $query->whereNull('folder_id');
            })
            ->when(!is_null($folder_id), function (Builder $query) use ($folder_id) {
                $query->where('folder_id', $folder_id);
            })
            ->whereHas('share', function ($query) use ($request, $type,$team_id) {
                $query
                    ->where('shareable_type', $type)
                    ->where('team_id', $team_id);
            })
            ->with([
                'share.shareable' => function ($query) use ($type) {
                    $query
                        ->withLocked(Auth::id())
                        ->withRenamedTitle(Auth::id())
                        ->withCount('shares')
                        ->with('shares')
                        ->when($type == 'bit', function ($query) {
                            $query->with('type');
                        });
                }
            ])
            ->get();

        $result = $shortcuts->filter(function ($shortcut) {
            return $shortcut->share && $shortcut->share->shareable;
        })->map(function ($shortcut) {
            $shareable = $shortcut->share->shareable;
            $shareable->is_shortcut = true;
            $shareable->shortcut_id = $shortcut->id;
            $shareable->folder_id = $shortcut->folder_id;
            $shareable->share_id = $shortcut->share_id;

            return $shareable;
        })->merge($items);

        if ($type == 'folder') {
            return response()->json([
                'folders' => $result,
                'path'    => $path,
                'parent'  => $parent
            ]);
        }

        return $result;
    }

    public function findWithDetails($folder_id) {
        /** @var Folder $parent */
        $parent = Folder::where('id', $folder_id)
            ->withCount('shares')
            ->with('shares')
            ->firstOrFail();

        $this->authorize('view', $parent);

        $share = $parent->getShareFor(Auth::id());

        //If the parent is not shared with the current user, it's not a shortcut
        if ($share == null) {
            return $parent;
        }

        $parent->share_id = $share->id;

        $shortcut = Shortcut::where('user_id', Auth::id())
            ->where('share_id', $share->id)
            ->first();

        if ($shortcut != null) {
            $parent->folder_id = $shortcut->folder_id;
            $parent->is_shortcut = true;
            $parent->shortcut_id = $shortcut->id;
            $parent->setRenamedTitle($share->rename);
        }

        return $parent;
    }

    /**
     * Finds a shareable item corresponding to the request by id
     *
     * @param $id
     * @param $request
     * @return Shareable|Collection
     */
    public function find($id, Request $request) {
        $model = $this->findModel($request);
        return $model::findOrFail($id);
    }

    /**
     * Finds a shareable model using the request path
     * @param Request $request
     * @return Shareable
     */
    public function findModel(Request $request) {
        $path_mappings = [
            'files/*'              => File::class,
            'files'                => File::class,
            'folders/*'            => Folder::class,
            'folders'              => Folder::class,
            'integrations/folders' => Folder::class,
            'bits/*'               => Bit::class,
            'bits'                 => Bit::class
        ];

        foreach ($path_mappings as $path => $model) {
            if ($request->is($path)) {
                return $model;
            }
        }

        abort(404, "Entity not found");

        return null;
    }
}