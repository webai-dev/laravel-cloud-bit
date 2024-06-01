<?php

namespace App\Http\Controllers\Internal;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function ban($id, Request $request) {
        $this->authorize('ban', User::class);

        $this->validate($request, [
            'banned' => 'required|boolean'
        ]);

        /** @var User $user */
        $user = User::findOrFail($id);
        $user->banned = (boolean) $request->input('banned',false);
        $user->save();
    }
}
