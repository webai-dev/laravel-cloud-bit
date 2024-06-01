<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Http\Request;

class BasePolicy {

    protected $messages = [];
    protected $request;

    const ADMIN_API_HEADER = 'x-ybit-admin';

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function before(User $user,$ability){
        if (!$this->request->hasHeader(self::ADMIN_API_HEADER)){
            return null;
        }

        return $user->superuser;
    }

    public function getMessageForAction($action){
        if (array_key_exists($action,$this->messages)){
            return $this->messages[$action];
        }else{
            return __('auth.unauthorized_default');
        }
    }
}