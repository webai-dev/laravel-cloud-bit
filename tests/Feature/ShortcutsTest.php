<?php

namespace Tests\Feature;

use App\Models\Shortcut;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Models\User;
use App\Models\Teams\Team;
use App\Models\Folder;

class ShortcutsTest extends TestCase {

    use DatabaseTransactions;

    public function testCreateInRoot() {
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $team = factory(Team::class)->create(['user_id' => $user1->id]);
        $team->users()->attach([$user1->id, $user2->id]);

        /** @var Folder $folder */
        $root = factory(Folder::class)->create(['user_id' => $user1->id, 'team_id' => $team->id]);
        $folder = factory(Folder::class)->create(['user_id'   => $user1->id,
                                                  'team_id'   => $team->id,
                                                  'folder_id' => $root->id
        ]);

        $share = $folder->shareWith($user2, 'edit');

        $this->asUser($user2)->asTeam($team)
            ->post('shortcuts', [
                'share_id' => $share->id,
            ])
            ->assertStatus(200);

        $this->fetch('folders', ['team_id' => $team->id])
            ->assertStatus(200)
            ->assertJson([
                'folders' => [['id' => $folder->id, 'is_shortcut' => true, 'folder_id' => null]]
            ]);
    }

    public function testCreateInSubtree() {
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $team = factory(Team::class)->create(['user_id' => $user1->id]);
        $team->users()->attach([$user1->id, $user2->id]);

        /** @var Folder $folder */
        $root = factory(Folder::class)->create(['user_id' => $user1->id, 'team_id' => $team->id]);
        $folder = factory(Folder::class)->create(['user_id'   => $user1->id,
                                                  'team_id'   => $team->id,
                                                  'folder_id' => $root->id
        ]);

        $other_root = factory(Folder::class)->create(['user_id' => $user2->id, 'team_id' => $team->id]);

        $share = $folder->shareWith($user2, 'edit');

        $this->asUser($user2)->asTeam($team)
            ->post('shortcuts', [
                'share_id'  => $share->id,
                'folder_id' => $other_root->id
            ])
            ->assertStatus(200);

        $this->fetch('folders', ['team_id' => $team->id, 'folder_id' => $other_root->id])
            ->assertStatus(200)
            ->assertJson([
                'folders' => [['id' => $folder->id, 'is_shortcut' => true, 'folder_id' => $other_root->id]]
            ]);
    }

    public function testMove() {
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $team = factory(Team::class)->create(['user_id' => $user1->id]);
        $team->users()->attach([$user1->id, $user2->id]);

        /** @var Folder $folder */
        $folder = factory(Folder::class)->create(['user_id' => $user1->id, 'team_id' => $team->id]);

        $user2_folder = factory(Folder::class)->create(['user_id' => $user2->id, 'team_id' => $team->id]);

        $share = $folder->shareWith($user2, 'edit');

        $shortcut = Shortcut::create([
            'share_id'  => $share->id,
            'folder_id' => null,
            'user_id'   => $user2->id
        ]);

        $this->asUser($user2)->asTeam($team)
            ->put('shortcuts/' . $shortcut->id . '/move', [
                'share_id'  => $share->id,
                'folder_id' => $user2_folder->id
            ])
            ->assertStatus(200);

        $this->fetch('folders', ['folder_id' => $user2_folder->id, 'team_id' => $team->id])
            ->assertStatus(200)
            ->assertJson([
                'folders' => [['id' => $folder->id, 'is_shortcut' => true, 'folder_id' => $user2_folder->id]]
            ]);
    }

    public function testMoveInSharedSubtree() {
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $team = factory(Team::class)->create(['user_id' => $user1->id]);
        $team->users()->attach([$user1->id, $user2->id]);

        /** @var Folder $folder */
        $folder = factory(Folder::class)->create(['user_id' => $user1->id, 'team_id' => $team->id]);

        $user2_folder = factory(Folder::class)->create(['user_id' => $user2->id, 'team_id' => $team->id]);

        $share = $folder->shareWith($user2, 'edit');

        $shortcut = Shortcut::create([
            'share_id'  => $share->id,
            'folder_id' => $user2_folder->id,
            'user_id'   => $user2->id
        ]);

        $this->asUser($user2)->asTeam($team)
            ->put('shortcuts/' . $shortcut->id . '/move', [
                'share_id'  => $share->id,
                'folder_id' => $folder->id
            ])
            ->assertStatus(400);
    }

    public function testDelete() {
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $team = factory(Team::class)->create(['user_id' => $user1->id]);
        $team->users()->attach([$user1->id, $user2->id]);

        /** @var Folder $folder */
        $folder = factory(Folder::class)->create(['user_id' => $user1->id, 'team_id' => $team->id]);
        $user2root = factory(Folder::class)->create(['user_id'               => $user2->id,
                                                     'team_id'               => $team->id,
                                                     'shared_children_count' => 1
        ]);

        $share = $folder->shareWith($user2, 'edit');

        $shortcut = Shortcut::create([
            'share_id'  => $share->id,
            'folder_id' => $user2root->id,
            'user_id'   => $user2->id
        ]);

        $this->asUser($user2)->asTeam($team)
            ->delete('shortcuts/' . $shortcut->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('share_folders', [
            'share_id' => $share->id,
            'user_id'  => $user2->id
        ]);
    }

}
