<?php

namespace Tests\Unit;

use App\Models\Shortcut;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Models\Folder;

class PathTest extends TestCase {

    use DatabaseTransactions;

    public function testRoot() {
        list($team, $user) = $this->getTeam();
        $folder = factory(Folder::class)->create(['user_id' => $user->id, 'team_id' => $team->id]);

        $this->assertEquals([
            [
                'id'        => $folder->id,
                'title'     => $folder->title,
                'folder_id' => null,
                'in_shared' => false
            ]
        ], $folder->getPathFor($user->id));
    }

    public function testNested() {
        list($team, $user) = $this->getTeam();

        $root = factory(Folder::class)->create(['user_id' => $user->id, 'team_id' => $team->id]);
        $middle = factory(Folder::class)->create(['user_id' => $user->id, 'team_id' => $team->id, 'folder_id' => $root->id]);
        $leaf = factory(Folder::class)->create(['user_id' => $user->id, 'team_id' => $team->id, 'folder_id' => $middle->id]);

        $path = $leaf->getPathFor($user->id);

        $this->assertEquals([
            [
                'id'        => $leaf->id,
                'title'     => $leaf->title,
                'folder_id' => $middle->id,
                'in_shared' => false
            ],
            [
                'id'        => $middle->id,
                'title'     => $middle->title,
                'folder_id' => $root->id,
                'in_shared' => false
            ],
            [
                'id'        => $root->id,
                'title'     => $root->title,
                'folder_id' => null,
                'in_shared' => false
            ],
        ], $path );
    }

    public function testShortcut() {
        list($team, $users) = $this->getTeamWithUsers(2);
        $user1 = $users->get(0);
        $user2 = $users->get(1);

        $user1_folder = factory(Folder::class)->create(['user_id' => $user1->id, 'team_id' => $team->id]);
        $user1_subfolder = factory(Folder::class)->create(['user_id' => $user1->id, 'team_id' => $team->id, 'folder_id' => $user1_folder->id]);

        $user2_folder = factory(Folder::class)->create(['user_id' => $user2->id, 'team_id' => $team->id]);

        $share = $user1_subfolder->shareWith($user2, 'edit');

        Shortcut::create([
            'share_id'  => $share->id,
            'folder_id' => $user2_folder->id,
            'user_id'   => $user2->id
        ]);

        $this->assertEquals([
            [
                'id'        => $user1_subfolder->id,
                'title'     => $user1_subfolder->title,
                'folder_id' => $user2_folder->id,
                'in_shared' => true
            ],
            [
                'id'        => $user2_folder->id,
                'title'     => $user2_folder->title,
                'folder_id' => null,
                'in_shared' => false
            ],
        ], $user1_subfolder->getPathFor($user2->id));
    }
}
