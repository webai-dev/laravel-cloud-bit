<?php

namespace App\Http\Controllers;

use App\Policies\BasePolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function authorize($ability, $resource) {
        if (Gate::allows($ability,$resource)){
            return;
        }

        $policy = Gate::getPolicyFor($resource);

        if ($policy == null || !$policy instanceof BasePolicy){
            throw new \RuntimeException("Invalid policy found when checking for '$ability'");
        }

        throw new AuthorizationException($policy->getMessageForAction($ability));
    }
}
