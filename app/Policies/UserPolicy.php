<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Stripe\Error\Base;

class UserPolicy extends BasePolicy
{
    use HandlesAuthorization;

   public function ban() {
       return false;
   }
}
