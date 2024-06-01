<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use DB,Auth;

class FavouriteScope implements Scope{

    public function apply(Builder $builder, Model $model){
        $user_id = Auth::id();
        $table = $model->getTable();
        
        $builder->leftJoin(DB::raw(
            "(SELECT favourite_id,COUNT(*) AS favourite
                FROM user_favourites WHERE user_id=$user_id
                AND favourite_type = '$table'
                GROUP BY favourite_id
             ) AS favourites"),'favourites.favourite_id','=',$table.'.id'
        );
    }
}