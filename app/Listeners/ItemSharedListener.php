<?php

namespace App\Listeners;

use App\Events\ItemShared;
use App\Indexing\Jobs\IndexSharedItem;
use App\Mail\ItemShared as ItemSharedMail;
use App\Notifications\ShareNotification;
use Illuminate\Support\Facades\Mail;

class ItemSharedListener{

    public function handle(ItemShared $event){
        $share = $event->share;
        $shareable = $share->shareable;

        $shareable->is_shared = true;
        $shareable->save();

        dispatch(new IndexSharedItem($share));

        if ($event->notify){
            Mail::to($share->recipient->email)->send(new ItemSharedMail($share));
            $share->recipient->notify(new ShareNotification($share));
        }
    }
}
