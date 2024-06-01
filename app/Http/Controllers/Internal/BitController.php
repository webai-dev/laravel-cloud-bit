<?php

namespace App\Http\Controllers\Internal;

use App\Models\Bits\Bit;
use App\Models\Bits\Color;
use App\Models\Bits\Type;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Services\Bits\BitService;
use App\Traits\TagsResources;
use App\Traits\InteractsWithShareables;

use App\Exceptions\BitServiceException;
use App\Http\Controllers\Controller;

class BitController extends Controller {

    use TagsResources, InteractsWithShareables;

    protected $service;

    public function __construct(BitService $bitService) {
        $this->service = $bitService;
    }

    public function store(Request $request) {
        $this->validate($request, [
            'title'   => 'required',
            'type_id' => 'required',
            'team_id' => 'required|exists:teams,id',
            'tags'    => 'array',
        ]);
        $this->authorize('create', Bit::class);

        $user = Auth::user();

        $has_shared_parent = false;
        $bit = new Bit($request->only(['title', 'type_id', 'folder_id', 'team_id']));

        if ($request->has('folder_id')) {
            /** @var Folder $parent */
            $parent = Folder::findOrFail($request->input('folder_id'));
            $this->authorize('edit', $parent);

            $bit->owner_id = $parent->owner_id;
            $has_shared_parent = $parent->is_shared || $parent->has_shared_parent;
        }else{
            $bit->owner_id = Auth::id();
        }

        $type = Type::where('id', $request->input('type_id'))
            ->where(function (Builder $query) use ($request) {
                $query->where('public', 1)
                    ->orWhereHas('teams', function (Builder $query) use ($request) {
                        $query->where('teams.id', $request->input('team_id'));
                    });
            })
            ->firstOrFail();

        $bit->user_id = $user->id;
        $bit->has_shared_parent = $has_shared_parent;
        $bit->sandbox = false;

        $bit->save();

        if ($request->has('color')) {
            $this->updateColor($bit, $request->input('color'));
        }

        $this->service->setType($type);

        try {
            $this->service->create($bit);
        } catch (BitServiceException $e) {
            //When the service throws an exception, the bit is not considered created
            $bit->delete();

            return response()->json([
                'error'   => 'BitServiceException',
                'message' => $e->getMessage(),
            ], 503);
        }

        $this->tag($bit->id, $request);

        $bit->shares_count = 0;
        $bit->color = $request->input('color');

        return $bit;
    }

    public function show($id, Request $request) {
        $user = Auth::user();
        /** @var Bit $bit */
        $bit = Bit::where('id', $id)
            ->with('type', 'tags')
            ->withColor($user->id)
            ->withLocked($user->id)
            ->withRenamedTitle($user->id)
            ->withCount('shares')
            ->with('shares')
            ->addSelect('bits.user_id')
            ->firstOrFail();

        $this->authorize('view', $bit);

        if ($request->input('view') == "simple") {
            if ($bit->user_id != $user->id && $bit->is_shared) {
                $bit->folder_id = null;
            }

            $response = $bit;
        } else {
            $bit->triggerEvent('opened');
            $this->service->setType($bit->type);
            $token = $this->service->getToken($bit, $user);

            $response = [
                'tags'  => $bit->tags->pluck('text'),
                'token' => $token->__toString(),
                'url'   => $bit->type->display_url,
                'path'  => $bit->getPathFor($user->id),
                'fullscreen' => $bit->type->fullscreen
            ];
        }

        return response()->json($response);
    }

    public function update(Request $request, Bit $bit) {
        $this->validate($request, [
            'team_id' => 'required',
        ]);

        $this->authorize('edit', $bit);

        $bit->title = $request->input('title', $bit->title);
        $bit->save();

        if ($request->has('color')) {
            $this->updateColor($bit, $request->input('color'));
        }

        $this->tag($bit->id, $request);
        $bit->load('tags');
        $bit->color = $request->input('color');

        return $bit;
    }

    protected function updateColor(Bit $bit, $color_code) {
        $params = [
            'user_id' => Auth::id(),
            'bit_id'  => $bit->id,
        ];

        if ($color_code == null) {
            Color::query()->where($params)->delete();
            return;
        }

        $color = Color::query()->firstOrNew($params, ['color' => $color_code]);

        if ($color->exists) {
            Color::query()->where($params)->update(['color' => $color_code]);
            return;
        }
        $color->save();
    }

    public function destroy(Bit $bit) {
        $this->authorize('delete', $bit);
        $this->service->setType($bit->type);

        try {
            $this->service->remove($bit);
        } catch (BitServiceException $e) {
            return response()->json([
                'error'   => 'BitServiceException',
                'message' => $e->getMessage(),
            ], 503);
        }

        $bit->forceDelete();

        return $bit;
    }

    public function show_locked(Request $request) {
        $this->validate($request, ['team_id' => 'required']);

        $locked = Bit::whereHas('locks', function (Builder $query) use ($request) {
            $query->where('user_id', Auth::id())
                ->where('team_id', $request->input('team_id'));
        })
            ->select('bits.*')
            ->withColor(Auth::id())
            ->with('type')
            ->withCount('shares')
            ->get();

        return $locked;
    }

    protected function get_taggable_type() {
        return 'bit';
    }
}
