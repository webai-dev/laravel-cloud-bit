<?php

namespace App\Http\Controllers\Internal\Sandbox;

use App\Exceptions\BitServiceException;
use App\Http\Controllers\Controller;
use App\Indexing\Indexable;
use App\Models\Bits\Bit;
use App\Models\Bits\Type;
use App\Services\Bits\BitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SandboxInstanceController extends Controller {

    protected $service;

    public function __construct(BitService $service) {
        Indexable::$update_index = false;
        $this->service = $service;
    }

    public function store(Type $type, Request $request) {
        if (!$type->draft) {
            abort(422, __('bits.draft_warning'));
        }

        $this->validate($request, [
            'title' => 'required|string|max:255',
        ]);

        $instance = new Bit($request->only(['title']));
        $instance->type_id = $type->id;

        $user = Auth::user();
        $instance->user_id = $user->id;
        $instance->owner_id = $user->id;
        $instance->folder_id = null;
        $instance->team_id = $request->getTeam()->id;
        $instance->sandbox = true;
        $instance->save();

        $this->service->setType($type);
        try {
            $this->service->create($instance);
        } catch (BitServiceException $e) {
            $created = Bit::query()->findOrFail($instance->id);
            $created->forceDelete();

            return response()->json([
                'error'   => 'BitServiceException',
                'message' => $e->getMessage(),
            ], 503);
        }

        return $instance;
    }

    public function update(Type $type, Request $request, Bit $instance) {
        if (!$instance->sandbox) {
            abort(422, 'Only sandbox bits are permitted');
        }

        $instance->fill($request->all());

        $instance->save();
        return $instance;
    }


    public function destroy(Type $type, Request $request, $instance_id) {
        $instance = Bit::find($instance_id);
        if (!$instance->sandbox) {
            abort(422, 'Only sandbox bits are permitted');
        }

        $this->service->setType($type);
        try {
            $this->service->remove($instance);
        } catch (BitServiceException $e) {
            return response()->json([
                'error'   => 'BitServiceException',
                'message' => $e->getMessage(),
            ], 503);
        }

        $instance->forceDelete();
        return $instance;
    }


    public function show(Type $type, Request $request, Bit $instance) {
        $user = Auth::user();

        $this->service->setType($type);
        $token = $this->service->getToken($instance, $user);

        return [
            'token'       => $token->__toString(),
            'display_url' => $type->display_url,
            'fullscreen'  => $type->fullscreen,
            'width'       => $type->width,
            'height'      => $type->height,
            'title'       => $instance->title,
        ];
    }

}