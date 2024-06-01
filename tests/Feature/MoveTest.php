<?php

namespace Tests\Feature;

use App\Models\Teams\Team;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Models\Folder;
use App\Models\User;

class MoveTest extends TestCase {

    use DatabaseTransactions;

    public function testMineToMine() {
        list($team, $user) = $this->getTeam();

        $folder1 = factory(Folder::class)->create([
            'user_id'  => $user->id,
            'owner_id' => $user->id,
            'team_id'  => $team->id,
        ]);
        $folder2 = factory(Folder::class)->create([
            'user_id'  => $user->id,
            'owner_id' => $user->id,
            'team_id'  => $team->id,
        ]);
        $subfolder = factory(Folder::class)->create([
            'user_id'   => $user->id,
            'team_id'   => $team->id,
            'owner_id'  => $user->id,
            'folder_id' => $folder1->id
        ]);

        $this->asUser($user)->asTeam($team)
            ->put('folders/' . $subfolder->id . '/move', [
                'folder_id' => $folder2->id,
                'team_id'   => $team->id
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('folders', ['id' => $subfolder->id, 'folder_id' => $folder2->id]);
    }

    public function testMoveToSelf() {
        list($team, $user) = $this->getTeam();

        $folder = factory(Folder::class)->create([
            'user_id'  => $user->id,
            'owner_id' => $user->id,
            'team_id'  => $team->id,
        ]);

        $this->asUser($user)->asTeam($team)
            ->put('folders/' . $folder->id . '/move', [
                'folder_id' => $folder->id,
                'team_id'   => $team->id
            ])
            ->assertStatus(400);
    }

    public function testMoveToDescendant() {
        list($team, $user) = $this->getTeam();

        $folder = factory(Folder::class)->create([
            'user_id'  => $user->id,
            'owner_id' => $user->id,
            'team_id'  => $team->id,
        ]);
        $subfolder = factory(Folder::class)->create([
            'user_id'   => $user->id,
            'team_id'   => $team->id,
            'owner_id'  => $user->id,
            'folder_id' => $folder->id
        ]);

        $this->asUser($user)->asTeam($team)
            ->put('folders/' . $folder->id . '/move', [
                'folder_id' => $subfolder->id,
                'team_id'   => $team->id
            ])
            ->assertStatus(400);
    }

    public function testSharedToMine() {
        /** @var Team $team */
        list($team, $user1) = $this->getTeam();
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);

        $folder1 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);
        $folder2 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);

        $folder2->shareWith($user2, 'edit');

        $this->asUser($user1)->asTeam($team)
            ->put('folders/' . $folder2->id . '/move', [
                'folder_id' => $folder1->id,
                'team_id'   => $team->id
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('folders', [
            'id' => $folder1->id,
        ]);

        $this->assertDatabaseHas('folders', [
            'id'        => $folder2->id,
            'folder_id' => $folder1->id
        ]);
    }

    public function testMineToSharedParent() {
        /** @var Team $team */
        list($team, $user1) = $this->getTeam();
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);

        $folder1 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);
        $folder2 = factory(Folder::class)->create([
            'user_id'  => $user2->id,
            'owner_id' => $user2->id,
            'team_id'  => $team->id
        ]);

        $folder1->shareWith($user2, 'edit');

        $this->asUser($user2)->asTeam($team)
            ->put('folders/' . $folder2->id . '/move', [
                'folder_id' => $folder1->id,
                'team_id'   => $team->id
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('folders', [
            'id'        => $folder2->id,
            'folder_id' => $folder1->id
        ]);
    }

    public function testFromSharedParentToMine() {
        /** @var Team $team */
        list($team, $user1) = $this->getTeam();
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);

        $folder1 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);

        $folder1->shareWith($user2, 'edit');

        $folder2 = factory(Folder::class)->create([
            'user_id'           => $user2->id,
            'owner_id'          => $user2->id,
            'team_id'           => $team->id,
            'folder_id'         => $folder1->id,
            'has_shared_parent' => true
        ]);

        $this->asUser($user2)->asTeam($team)
            ->put('folders/' . $folder2->id . '/move', [
                'team_id' => $team->id
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('folders', [
            'id'        => $folder2->id,
            'folder_id' => null
        ]);
    }

    public function testSharedFromSubtreeToMine() {
        /** @var Team $team */
        list($team, $user1) = $this->getTeam();
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);

        $root_1 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);
        $shared_sub = factory(Folder::class)->create([
            'user_id'   => $user1->id,
            'owner_id'  => $user1->id,
            'team_id'   => $team->id,
            'folder_id' => $root_1->id
        ]);

        $shared_sub->shareWith($user2, 'edit');

        $root_2 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);

        $this->asUser($user1)->asTeam($team)
            ->put('folders/' . $shared_sub->id . '/move', [
                'team_id'   => $team->id,
                'folder_id' => $root_2->id
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('folders', [
            'id' => $root_1->id,
        ]);
        $this->assertDatabaseHas('folders', [
            'id'        => $root_2->id,
            'folder_id' => null
        ]);
    }

    public function testFromSharedSubtreeToMine() {
        /** @var Team $team */
        list($team, $user1) = $this->getTeam();
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);

        $folder1 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);

        $folder1->shareWith($user2, 'edit');

        $folder2 = factory(Folder::class)->create([
            'user_id'   => $user2->id,
            'owner_id'  => $user2->id,
            'team_id'   => $team->id,
            'folder_id' => $folder1->id,
        ]);
        $folder3 = factory(Folder::class)->create([
            'user_id'   => $user2->id,
            'owner_id'  => $user2->id,
            'team_id'   => $team->id,
            'folder_id' => $folder2->id,
        ]);

        $this->asUser($user2)->asTeam($team)
            ->put('folders/' . $folder3->id . '/move', [
                'team_id' => $team->id
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('folders', [
            'id'        => $folder3->id,
            'folder_id' => null
        ]);
    }

    public function testFromMineToSharedSubtree() {
        /** @var Team $team */
        list($team, $user1) = $this->getTeam();
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);

        $folder1 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);

        $folder1->shareWith($user2, 'edit');

        $folder2 = factory(Folder::class)->create([
            'user_id'   => $user2->id,
            'owner_id'  => $user2->id,
            'team_id'   => $team->id,
            'folder_id' => $folder1->id,
        ]);
        $folder3 = factory(Folder::class)->create([
            'user_id'  => $user2->id,
            'owner_id' => $user2->id,
            'team_id'  => $team->id
        ]);

        $this->asUser($user2)->asTeam($team)
            ->put('folders/' . $folder3->id . '/move', [
                'team_id'   => $team->id,
                'folder_id' => $folder2->id
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('folders', [
            'id'        => $folder3->id,
            'folder_id' => $folder2->id
        ]);
    }

    public function testCannotMoveOthersSharedToMine() {
        /** @var Team $team */
        list($team, $user1) = $this->getTeam();
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);

        $folder1 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);

        $folder1->shareWith($user2, 'edit');

        $folder2 = factory(Folder::class)->create([
            'user_id'  => $user2->id,
            'owner_id' => $user2->id,
            'team_id'  => $team->id
        ]);

        $this->asUser($user2)->asTeam($team)
            ->put('folders/' . $folder1->id . '/move', [
                'team_id'   => $team->id,
                'folder_id' => $folder2->id
            ])
            ->assertStatus(400)
            ->assertJson([
                'message' => __('permissions.move_shared_error', ['item' => 'folder'])
            ]);
    }

    public function testCannotMoveSharedToSharedWithMorePermissions() {
        /** @var Team $team */
        list($team, $user1) = $this->getTeam();
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);

        $folder1 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);
        $folder2 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);

        $folder1->shareWith($user2, 'edit');
        $folder2->shareWith($user2, 'share');

        $this->asUser($user1)->asTeam($team)
            ->put('folders/' . $folder2->id . '/move', [
                'team_id'   => $team->id,
                'folder_id' => $folder1->id
            ])
            ->assertStatus(400)
            ->assertJson([
                'message' => __('shares.shared_movement')
            ]);
    }

    public function testCannotMoveSharedToSharedSubtreeWithMorePermissions() {
        /** @var Team $team */
        list($team, $user1) = $this->getTeam();
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);

        // Shared subtree
        $folder1 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);
        $folder1->shareWith($user2, 'edit');
        $subfolder = factory(Folder::class)->create([
            'user_id'   => $user1->id,
            'owner_id'  => $user1->id,
            'team_id'   => $team->id,
            'folder_id' => $folder1->id
        ]);

        // Shared
        $folder2 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);
        $folder2->shareWith($user2, 'share');

        $this->asUser($user1)->asTeam($team)
            ->put('folders/' . $folder2->id . '/move', [
                'team_id'   => $team->id,
                'folder_id' => $subfolder->id
            ])
            ->assertStatus(400)
            ->assertJson([
                'message' => __('shares.shared_movement')
            ]);
    }

    public function testCannotMoveParentOfSharedToSharedWithLessPermissions() {
        /** @var Team $team */
        list($team, $user1) = $this->getTeam();
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);

        // Parent of shared
        $folder1 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id,
        ]);
        $subfolder = factory(Folder::class)->create([
            'user_id'   => $user1->id,
            'owner_id'  => $user1->id,
            'team_id'   => $team->id,
            'folder_id' => $folder1->id
        ]);
        $subfolder->shareWith($user2, 'share');

        // Shared
        $folder2 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);
        $folder2->shareWith($user2, 'edit');

        $this->asUser($user1)->asTeam($team)
            ->put('folders/' . $folder1->id . '/move', [
                'team_id'   => $team->id,
                'folder_id' => $folder2->id
            ])
            ->assertStatus(400)
            ->assertJson([
                'message' => __('shares.shared_movement')
            ]);
    }

    public function testCannotMoveParentOfSharedToSharedSubtreeWithMorePermissions() {
        /** @var Team $team */
        list($team, $user1) = $this->getTeam();
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);

        // Parent of shared
        $folder1 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id,
        ]);
        $subfolder1 = factory(Folder::class)->create([
            'user_id'   => $user1->id,
            'owner_id'  => $user1->id,
            'team_id'   => $team->id,
            'folder_id' => $folder1->id
        ]);
        $subfolder1->shareWith($user2, 'share');

        //Shared subtree
        $folder2 = factory(Folder::class)->create([
            'user_id'  => $user1->id,
            'owner_id' => $user1->id,
            'team_id'  => $team->id
        ]);
        $folder2->shareWith($user2, 'edit');
        $subfolder2 = factory(Folder::class)->create([
            'user_id'   => $user1->id,
            'owner_id'  => $user1->id,
            'team_id'   => $team->id,
            'folder_id' => $folder2->id,
        ]);

        $this->asUser($user1)->asTeam($team)
            ->put('folders/' . $folder1->id . '/move', [
                'team_id'   => $team->id,
                'folder_id' => $subfolder2->id
            ])
            ->assertStatus(400)
            ->assertJson([
                'message' => __('shares.shared_movement')
            ]);
    }
}
