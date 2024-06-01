<?php

namespace App\Http\Controllers\Internal;

use App\Events\TeamUserRemoved;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AccountController extends Controller {

    public function index(Request $request) {
        $team = $request->getTeam();

        if ($team) {
            $user = Auth::user();
            $response = [
                'id'                        => $user->id,
                'email'                     => $user->email,
                'name'                      => $user->name,
                'phone'                     => $user->phone,
                'photo'                     => $user->photo,
                'superuser'                 => $user->superuser,
                'apparatus_id'              => $user->apparatus_id,
                'developer'                 => $user->teams()->findOrFail($team->id)->pivot->developer,
                'has_accepted_ybit_terms' => $user->hasAcceptedLatestTerms(),
            ];
            if ($user->integration_url) {
                $response['integration_url'] = $user->integration_url;
            }
            return $response;
        } else {
            $user = Auth::user();
            return response()->json(array_merge($user->toArray(), ['apparatus_id' => $user->apparatus_id,'has_accepted_ybit_terms' => $user->hasAcceptedLatestTerms()]));
        }
    }

    public function acceptTerms() {
        $user = Auth::user();
        $user->has_accepted_terms_on = Carbon::now();
        $user->save();

        return $user;
    }

    public function update(Request $request) {
        $user = Auth::user();

        $user->photo = $request->input('photo', $user->photo);
        $user->name = $request->input('name', $user->name);
        $user->save();

        return $user;
    }

    public function destroy() {
        /** @var User $user */
        $user = Auth::user();

        if ($user->owned_teams()->count() > 0) {
            abort(400, __('teams.still_owned'));
        }
        foreach ($user->teams as $team) {
            event(new TeamUserRemoved($user, $team));
        }

        $user->delete();
    }
}
