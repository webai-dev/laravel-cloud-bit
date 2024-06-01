<?php

namespace App\Jobs;

use App\Models\Folder;
use App\Models\User;
use App\Sharing\Shareable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ItemUnshared implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $shareable;

    /**
     * ItemUnshared constructor.
     * @param Shareable $shareable The item that was unshared
     * @param User $user The user from whom it was unshared
     */
    public function __construct(Shareable $shareable, User $user) {
        $this->shareable = $shareable;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $shareable = $this->shareable;
        $user_id = $this->user->id;

        //Remove shareable from locked dashboard of the users that won't view it anymore
        $shareable->locks()->where('user_id',$user_id)->delete();

        if ($shareable instanceof Folder){
            $shareable->transferDescendants($user_id,$shareable->user_id);
            $shareable->unlockDescendants($user_id);
        }
    }
}
