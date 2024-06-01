<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Sharing\Permissions;
use App\Exceptions\PermissionException;

use App\Models\User;
use App\Models\Teams\Team;
use App\Models\Folder;
use App\Models\Share;

class PermissionsTest extends TestCase
{
    use DatabaseTransactions;

    public function testCreatorSharedSubfolderPermissions(){
        list($team,$users) = $this->getTeamWithUsers(2);
        $user1 = $users->get(0);
        $user2 = $users->get(1);

        $folder = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id]);
        $folder->shareWith($user2,'edit');

        $subfolder = factory(Folder::class)->create(['user_id'=>$user2->id,'team_id'=>$team->id,'folder_id'=>$folder->id]);

        $this->assertTrue($subfolder->hasPermissionFor('share',$user1->id));
    }

    public function testRootOwnerPermissions(){
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $team = factory(Team::class)->create(['user_id'=>$user1->id]);
        $team->users()->attach([$user1->id,$user2->id]);

        $folder = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id]);

        $this->assertEquals(true,$folder->hasPermissionFor('share',$user1->id));
        $this->assertEquals(false,$folder->hasPermissionFor('view',$user2->id));
    }

    public function testRootSharedPermissions(){
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $team = factory(Team::class)->create(['user_id'=>$user1->id]);
        $team->users()->attach([$user1->id,$user2->id]);

        $folder = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id]);

        Share::create(['user_id'=>$user2->id,'team_id'=>$team->id,'shareable_id'=>$folder->id,'shareable_type'=>'folder']);

        $this->assertEquals(true,$folder->hasPermissionFor('view',$user2->id));
        $this->assertEquals(false,$folder->hasPermissionFor('edit',$user2->id));
    }

    public function testNestedOwnerPermissions(){
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $team = factory(Team::class)->create(['user_id'=>$user1->id]);
        $team->users()->attach([$user1->id,$user2->id]);

        $folder = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id]);
        $nested_folder1 = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id,'folder_id'=>$folder->id]);
        $nested_folder2 = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id,'folder_id'=>$nested_folder1->id]);

        $this->assertEquals(false,$nested_folder2->hasPermissionFor('view',$user2->id));
    }

    public function testNestedSharedPermissions(){
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $team = factory(Team::class)->create(['user_id'=>$user1->id]);
        $team->users()->attach([$user1->id,$user2->id]);

        $folder = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id]);
        $nested_folder1 = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id,'folder_id'=>$folder->id]);
        $nested_folder2 = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id,'folder_id'=>$nested_folder1->id]);

        Share::create(['user_id'=>$user2->id,'team_id'=>$team->id,'shareable_id'=>$folder->id,'shareable_type'=>'folder']);

        $this->assertEquals(true,$nested_folder2->hasPermissionFor('view',$user2->id));
        $this->assertEquals(false,$nested_folder2->hasPermissionFor('edit',$user2->id));
    }

    public function testMovedSharedPermissions(){
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $team = factory(Team::class)->create(['user_id'=>$user1->id]);
        $team->users()->attach([$user1->id,$user2->id]);

        $folder = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id]);
        $user2_folder = factory(Folder::class)->create(['user_id'=>$user2->id,'team_id'=>$team->id]);

        $share = Share::create(['user_id'=>$user2->id,'team_id'=>$team->id,'shareable_id'=>$folder->id,'shareable_type'=>'folder']);
        $share->folders()->attach($user2_folder,['user_id'=>$user2->id]);

        $this->assertEquals(true,$folder->hasPermissionFor('true',$user2->id));
        $this->assertEquals(false,$folder->hasPermissionFor('edit',$user2->id));
    }

    public function testShareWithEditForView(){
        list($team,$users) = $this->getTeamWithUsers(2);
        $user1 = $users->get(0);
        $user2 = $users->get(1);

        $folder = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id]);
        $folder->shareWith($user2,'edit');

        $this->assertTrue($folder->hasPermissionFor('view',$user2->id));
        $this->assertFalse($folder->hasPermissionFor('share',$user2->id));
    }

    public function testShareWithShareForView(){
        list($team,$users) = $this->getTeamWithUsers(2);
        $user1 = $users->get(0);
        $user2 = $users->get(1);

        $folder = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id]);
        $folder->shareWith($user2,'share');

        $this->assertTrue($folder->hasPermissionFor('view',$user2->id));
    }

    public function testShareWithShareForEdit(){
        list($team,$users) = $this->getTeamWithUsers(2);
        $user1 = $users->get(0);
        $user2 = $users->get(1);

        $folder = factory(Folder::class)->create(['user_id'=>$user1->id,'team_id'=>$team->id]);
        $folder->shareWith($user2,'share');

        $this->assertTrue($folder->hasPermissionFor('edit',$user2->id));
    }
}
