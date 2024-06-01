<?php

namespace App\Http\Controllers\Internal\Auth;

use App\Http\Controllers\Controller;
use App\Util\FirebaseUtils;
use Illuminate\Support\Facades\Auth;

class FirebaseController extends Controller
{
    public function token() {
        $user = Auth::user();
        return FirebaseUtils::token($user->id);
    }
}
