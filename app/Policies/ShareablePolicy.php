<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;
use App\Models\Teams\Team;
use App\Sharing\Shareable;
use Illuminate\Http\Request;

class ShareablePolicy extends BasePolicy {

    protected $type = 'item';

    public function __construct(Request $request) {
        parent::__construct($request);
        $this->messages['view'] = __('permissions.view',['item' => $this->type]);
        $this->messages['create'] = __('permissions.create',['item' => $this->type]);
        $this->messages['edit'] = __('permissions.edit',['item' => $this->type]);
        $this->messages['share'] = __('permissions.share',['item' => $this->type]);
        $this->messages['delete'] = __('permissions.delete',['item' => $this->type]);
    }

    public function view(User $user,Shareable $shareable){
        return $shareable->hasPermissionFor('view',$user->id);
    }

    public function create(User $user){
        $team = $this->request->getTeam();
        if($team->hasUnpaidSubscriptions()) return false;

        if ($this->request->has('folder_id')){
            /** @var Folder $folder */
            $folder = Folder::query()->find($this->request->input('folder_id'));

            //Guest cant create shareables on folders they own
            $is_guest = $user->hasRoleInTeam('guest',$team->id);
            if($is_guest && $folder->user_id === $user->id ) return false;

            return $folder->hasPermissionFor('edit',$user->id);
        }
        return !$user->hasRoleInTeam('guest',$team->id);
    }

    public function edit(User $user,Shareable $shareable){
        $team = $shareable->team;
        if($team->hasUnpaidSubscriptions()) return false;

        return $shareable->hasPermissionFor('edit',$user->id);
    }

    public function share(User $user,Shareable $shareable){
        $team = $shareable->team;
        if($team->hasUnpaidSubscriptions()) return false;

        return $shareable->hasPermissionFor('share',$user->id);
    }

    public function delete(User $user,Shareable $shareable){
        return
            // User is owner
            $shareable->isRecursivelyOwnedBy($user->id)
            ||
            // User has permission to edit
            $shareable->hasPermissionFor('edit', $user->id);
    }

}