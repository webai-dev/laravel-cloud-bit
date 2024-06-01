<?php

namespace App\Http\Controllers\Internal;

use App\Models\Teams\Invitation;
use App\Models\Teams\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\InvitationCreated;
use App\Events\InvitationAccepted;
use App\Http\Controllers\Controller;

class InvitationController extends Controller {
    public function index() {
        $user = Auth::user();
        $invitations = $user->invitations()
            ->with(['team' => function ($team) {
                $team->select('id', 'name', 'photo');
            },
                'user' => function ($user) {
                    $user->select('id', 'name');
                }])
            ->where('status', 'pending')
            ->get();

        return $invitations;
    }

    public function store(Request $request) {
        $this->validate($request, [
            'invitations' => 'required|array|min:1',
            'invitations.*.contact' => 'required|string',
            'invitations.*.role_id' => 'required|integer|exists:roles,id',
            'team_id' => 'required|integer|exists:teams,id',
        ]);

        $team = Team::find($request->team_id);
        $this->authorize('update', $team);
        $contacts = collect($request->input('invitations',[]));

        $existing = $team->invitations()
            ->whereIn('contact', $contacts->pluck('contact')->toArray())
            ->get();

        if ($existing->count() > 0) {
            $existing = $existing->pluck('contact')->implode(", ");
            abort(400, __('invitations.existing_invitations', ['contacts' => $existing]));
        }

        $existing = $team->users()
            ->whereIn('email', $contacts->pluck('contact')->toArray())
            ->orWhereIn('phone', $contacts->pluck('contact')->toArray())
            ->get();

        if ($existing->count() > 0) {
            $existing = $existing->pluck('email')->implode(", ");
            abort(400, __('invitations.existing_users', ['contacts' => $existing]));
        }

        $failed = [];

        foreach ($contacts as $contact) {
            $invitation = Invitation::create([
                'team_id' => $team->id,
                'user_id' => Auth::id(),
                'role_id' => $contact['role_id'],
                'contact' => $contact['contact'],
            ]);

            try {
                $invitation->notify(new InvitationCreated());
            } catch (\Exception $e) {
                $invitation->delete();
                $failed[] = [
                    'contact' => $invitation->contact,
                    'reason' => $e->getMessage()
                ];
            }
        }

        if (count($failed) > 0) {
            return response()->json([
                'error' => 'InvitationSendingException',
                'message' => __('invitations.sending_failed'),
                'data' => $failed
            ], 500);
        }

        return response()->json([
            "message" => trans_choice('invitations.created', $contacts->count())
        ], 201);
    }

    public function update(Request $request, Invitation $invitation) {
        $user = Auth::user();

        if (strtolower($invitation->contact) != strtolower($user->email)
            && $invitation->contact != $user->phone) {
            abort(400, __('invitations.contact_mismatch'));
        }

        if ($invitation->status != Invitation::STATUS_PENDING) {
            abort(400, __('invitations.invalid_status', ['status' => $invitation->status]));
        }

        $invitation->status = $request->input('accepted') ? Invitation::STATUS_ACCEPTED : Invitation::STATUS_REJECTED;

        if ($invitation->status == Invitation::STATUS_ACCEPTED) {
            event(new InvitationAccepted($invitation, $user));
        }

        $invitation->save();

        $user->invitations()
            ->where('team_id', $invitation->team_id)
            ->update(['status' => $invitation->status]);

        return $invitation;
    }

    public function destroy(Invitation $invitation) {
        $this->authorize('delete', $invitation);

        $invitation->delete();
        return $invitation;
    }
}
