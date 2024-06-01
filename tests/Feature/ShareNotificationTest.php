<?php

namespace Tests\Feature;

use App\Enums\NotificationCategories;
use App\Enums\NotificationSubcategories;
use App\Enums\NotificationTypes;
use App\Events\ItemShared;
use App\Models\Folder;
use App\Models\Share;
use App\Models\Teams\Team;
use App\Models\User;
use App\Services\NotificationService;
use App\Util\NotificationPayload;
use Mockery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ShareNotificationTest extends TestCase
{
    public function testShareFolder()
    {
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $team = factory(Team::class)->create(['user_id'=>$user1->id]);
        $team->users()->attach([$user1->id,$user2->id]);

        $folder = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id]);

        $data = [
            'user_id'        => $user2->id,
            'team_id'        => $team->id,
            'shareable_type' => 'folder',
            'shareable_id'   => $folder->id,
            'share'          => 1,
            'edit'           => 1,
            'created_by_id'  => $user1->id
        ];

        $share  = factory(Share::class)->create($data);

        $arg = NotificationPayload::instance()
            ->forUser($user2->id)
            ->inTeam($team->id)
            ->ofType(NotificationTypes::SHARE_FOLDER)
            ->withCategory(NotificationCategories::TEAM_ACTIONS)
            ->withSubcategory(NotificationSubcategories::TEAM_SHARE)
            ->withPayload([
                "shareable" => [
                    "id" => $folder->id,
                    "title" => $folder->title,
                ],
                "creator" => [
                    "id" => $user1->id,
                    "email" => $user1->email,
                    "name"  => $user1->name,
                ]
            ]);

        $mock = Mockery::mock(NotificationService::class);
        $mock->shouldReceive('send')->once()->with(equalTo($arg));

        $this->app->instance(NotificationService::class, $mock);

        event(new ItemShared($share));
    }
}
