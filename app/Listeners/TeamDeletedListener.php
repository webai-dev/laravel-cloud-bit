<?php

namespace App\Listeners;

use App\Billing\Managers\SubscriptionManager;
use App\Events\TeamDeleted;
use App\Models\Bits\Bit;
use App\Models\File;
use App\Models\Folder;
use App\Models\Share;
use App\Services\Bits\BitService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;

class TeamDeletedListener implements ShouldQueue{

    protected $subscriptions;

    public function __construct(SubscriptionManager $manager, BitService $service) {
        $this->subscriptions = $manager;
        $this->service = $service;
    }

    public function handle(TeamDeleted $event){
        $team = $event->team;

        foreach ($team->subscriptions as $subscription) {
            $this->subscriptions->cancel($subscription);
        }

        File::where('team_id',$team->id)
        ->withTrashed()
        ->chunk(200,function($files){
            foreach ($files as $file) {
                Storage::delete($file->path);
                $file->forceDelete();
            }
        });

        Bit::where('team_id',$team->id)
        ->withTrashed()
        ->chunk(200,function($bits){
            foreach ($bits as $bit) {
                $this->service->setType($bit->type);

                try {
                    $this->service->remove($bit);

                    foreach($bit->files as $file) {
                        Storage::delete($file->path);
                        $file->forceDelete();
                    }

                    $bit->forceDelete();
                } catch (\Exception $e){
                    \Log::error('Error while deleting bit: '. $bit->id .$e->getMessage()."\n");
                }
            }
        });

        Folder::where('team_id',$team->id)
              ->withTrashed()
              ->forceDelete();

        Share::where('team_id',$team->id)->delete();

        $team->forceDelete();
    }
}