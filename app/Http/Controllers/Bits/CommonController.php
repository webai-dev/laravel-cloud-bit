<?php

namespace App\Http\Controllers\Bits;

use App\Models\Activity;
use App\Models\Bits\Type;
use App\Notifications\BitNotification;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class CommonController extends IntegrationController {

    public function teammates(Type $type, Request $request) {
        $bit = $this->findBit($type);

        return $bit->team->users()
            ->when($request->has('search'), function (Builder $query) use ($request) {
                $query->where('name', 'ILIKE', $request->input('search') . '%');
            })
            ->select(['id', 'name', 'photo'])->get();
    }

    public function metadata(Type $type, Request $request) {
        $bit = $this->findBit($type);

        $this->validate($request, [
            'metadata' => 'required|string|max:8000'
        ]);

        $bit->metadata = $request->input('metadata');
        $bit->save();

        return response()->json([
            'message' => 'Metadata updated'
        ]);
    }

    public function user(Type $type) {
        $claims = $this->getClaims($type);
        $bit = $this->findBit($type);

        return $bit->team->users()
            ->select(['id', 'name', 'photo'])
            ->where('id', $claims->get('sub'))
            ->firstOrFail();
    }

    public function notify(Type $type, Request $request) {
        $this->validate($request, [
            'content' => 'required',
            'users'   => 'array',
            'users.*' => 'required_with:users|integer|exists:users,id'
        ]);

        $bit = $this->findBit($type);

        $notification = new BitNotification($bit,
            $request->input('content', ''),
            $request->input('subject', null));

        $users = $request->has('users') ?
            $bit->team->users()->whereIn('id', $request->input('users', []))->get() :
            $bit->team->users()->canSee($bit)->get();

        Notification::send($users, $notification);

        return response()->json([
            'message' => 'Notification sent'
        ]);
    }

    public function log(Type $type, Request $request) {
        $bit = $this->findBit($type);
        $user = $this->findUser($type);

        $this->validate($request, [
            'major' => 'required|boolean',
            'action' => 'required',
            'changes' => 'json',
            'metadata' => 'json',
            'changes.*' => 'json',
            'changes.*.action' => 'required',
        ]);

        $activity = new Activity();

        $activity->major = $request->input('major', 0);
        $activity->action = $request->input('action');
        $activity->user_id = $user->id;
        $activity->target_id = $bit->id;
        $activity->target_type = 'bit';
        $activity->changes = $request->input('changes');
        $activity->metadata = $request->input('metadata');
        $activity->created_at = \Carbon\Carbon::now();

        $activity->save();

        return $activity;
    }
}
