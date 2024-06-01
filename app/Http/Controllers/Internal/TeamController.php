<?php

namespace App\Http\Controllers\Internal;

use App\Models\Bits\Type;
use App\Models\User;
use App\Models\Teams\Team;
use App\Models\Teams\InvalidSubdomain;
use App\Indexing\Searches\TeamShareablesSearch;
use Illuminate\Http\Request;
use App\Services\IndexingService;
use Illuminate\Support\Facades\Auth;
use App\Events\TeamDeleted;
use App\Http\Controllers\Controller;

class TeamController extends Controller {

    public function index() {
        $user = Auth::user();
        return $user->teams->map(function ($team) {
            $team->locked = $team->hasUnpaidSubscriptions();
            if($team->storage_limit == 0) {
                $team->storage_percentage = 0;
            } else {
                $team->storage_percentage = $team->getTotalUsedStorage() / $team->storage_limit;
            }
            return $team;
        });
    }

    public function validateSubdomain(Request $request) {
        $this->validate($request, [
            'subdomain' => 'required|regex:/(^[a-z0-9-]+$)/u|max:255|min:5|unique:teams,subdomain'
        ]);

        $invalid = InvalidSubdomain::where('subdomain', $request->input('subdomain'))->first();

        if (!is_null($invalid)) {
            abort(422, __('teams.invalid_subdomain'));
        }

        return response()->json(["message" => __('teams.valid_subdomain')]);
    }

    public function store(Request $request) {
        $this->validate($request, [
            'name'  => 'required|max:255',
            'photo' => 'max:255'
        ]);
        $this->validateSubdomain($request);

        $team = new Team($request->all());
        $team->user_id = Auth::id();
        $team->storage_limit = config('filesystems.default_storage_limit');
        $team->save();

        $team->users()->attach([$team->user_id]);

        $public_types = Type::query()->where('public', true)->pluck('id');
        $team->bitTypes()->attach($public_types->toArray());

        return $team;
    }

    public function show(Team $team) {
        $this->authorize('view', $team);
        return $team;
    }

    public function search(Team $team, Request $request, IndexingService $service) {
        $this->authorize('view', $team);
        $user = Auth::user();

        $search_terms = $request->input("search", "");

        $search = new TeamShareablesSearch();
        $search->setText($search_terms)
            ->addUser($user->id)
            ->addTeam($team->id)
            ->addDate($request->input('date'))
            ->addTypes($request->input('shareable_type'))
            ->addTags($request->input('tags'))
            ->addSharedWith($request->input('shared_with'))
            ->addOwner($request->input('owner'));

        $results = $service->search($search);

        $parsed = $request->input('type') == "index" ?
            $search->parseResultsDetailed($results)
            : $search->parseResults($results);

        $parsed['insight'] = $search->getInsight($results);

        return response()->json($parsed);
    }

    public function invitations(Team $team) {
        $this->authorize('view', $team);

        $invitations = $team->invitations()->with('user', 'role')->get();

        return $invitations;
    }

    public function update(Request $request, Team $team) {
        $this->authorize('update', $team);

        $this->validate($request, [
            'name'      => 'required|max:255',
            'subdomain' => 'required|regex:/(^[a-z0-9-]+$)/u|max:255|min:5|unique:teams,subdomain,' . $team->id,
            'photo'     => 'max:255',
        ]);

        if (!is_null(InvalidSubdomain::where('subdomain', $request->input('subdomain'))->first())) {
            abort(422, __('teams.invalid_subdomain'));
        }

        $team->update($request->all());

        return $team;
    }

    public function suspend(Team $team, Request $request) {
        $this->authorize('suspend', $team);
        $this->validate($request, [
            'suspended' => 'required|boolean'
        ]);

        $team->suspended = $request->input('suspended');
        $team->save();

        return $team;
    }

    public function destroy(Team $team) {
        $this->authorize('close', $team);

        $team->delete();

        event(new TeamDeleted($team));
        return $team;
    }

    public function transfer(Team $team, Request $request) {
        $this->authorize('update', $team);

        /** @var User $user */
        $user = User::findOrFail($request->input('user_id'));

        if (!$user->isInTeam($team->id)) {
            abort(400, __('teams.invalid_owner'));
        }

        $user->roles()->where('team_id', $team->id)->detach();

        $team->user_id = $user->id;
        $team->save();

        Auth::user()->setRoleInTeam('admin', $team->id);

        return $team;
    }

}
