<?php

namespace App\Policies;

use App\Models\Teams\Invitation;
use App\Models\User;
use Illuminate\Http\Request;

class InvitationPolicy extends BasePolicy {

    public function __construct(Request $request) {
        parent::__construct($request);
        $this->messages['delete'] = __('invitations.delete_error');
    }

    public function delete(User $user,Invitation $invitation){
        return $user->id == $invitation->user_id;
    }
}