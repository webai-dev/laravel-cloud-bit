<?php
namespace App\Sharing\Visitors;


use App\Sharing\Shareable;

class OwnershipUpdateVisitor extends ShareableTreeVisitor{

    function visitShareable(Shareable $shareable) {
        if ($shareable->folder_id == null){
            $shareable->owner_id = $shareable->user_id;
            $shareable->save();
        }else{
            $shareable->owner_id = $shareable->folder()->withoutGlobalScopes()->first()->owner_id;
            $shareable->save();
        }
    }
}