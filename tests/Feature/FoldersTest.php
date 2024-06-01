<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Teams\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FoldersTest extends TestCase {
    use DatabaseTransactions;

    public function testCreate() {
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create(['user_id' => $user->id]);
        $team->users()->attach($user->id);

        $this->asUser($user)->asTeam($team);

        $this->post('folders', [
            'team_id' => $team->id,
            'title'   => "My test folder"
        ])
            ->assertStatus(200);

        $this->assertDatabaseHas('folders', ['title' => "My test folder"]);
    }

    public function testCreateSubfolder() {
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create(['user_id' => $user->id]);
        $team->users()->attach($user->id);

        $this->asUser($user)->asTeam($team);
        $folder = factory(Folder::class)->create(['team_id' => $team->id, 'user_id' => $user->id]);

        $data = [
            'team_id'   => $team->id,
            'title'     => "My test subfolder",
            'folder_id' => $folder->id
        ];
        $this->post('folders', $data)->assertStatus(200);

        $this->assertDatabaseHas('folders', $data);
    }

    public function testUpdate() {
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create(['user_id' => $user->id]);
        $team->users()->attach($user->id);

        $this->asUser($user)->asTeam($team);
        $folder = factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'title'   => "The initial name"
        ]);

        $data = [
            'title'   => "The changed name",
            'team_id' => $team->id
        ];
        $this->put('folders/' . $folder->id, $data)
            ->assertStatus(200);

        $this->assertDatabaseHas('folders', $data);
    }

    public function testSoftDelete() {
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create(['user_id' => $user->id]);
        $team->users()->attach($user->id);
        $folder = factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $this->asUser($user)->asTeam($team);
        $this->delete('folders/' . $folder->id . '/trash')
            ->assertStatus(200);

        $this->assertSoftDeleted('folders', ['id' => $folder->id]);
    }

    public function testDelete() {
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create(['user_id' => $user->id]);
        $team->users()->attach($user->id);
        $folder = factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $this->asUser($user)->asTeam($team);
        $this->delete('folders/' . $folder->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('folders', ['id' => $folder->id]);
    }

    public function testDeleteRecursivelyOwned() {
        $user = factory(User::class)->create();
        $user2 = factory(User::class)->create();
        $team = factory(Team::class)->create(['user_id' => $user->id]);
        $team->users()->attach([$user->id, $user2->id]);

        $folder = factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);
        $subfolder = factory(Folder::class)->create([
            'team_id'   => $team->id,
            'user_id'   => $user2->id,
            'folder_id' => $folder->id
        ]);

        $this->asUser($user)->asTeam($team);
        $this->delete('folders/' . $subfolder->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('folders', ['id' => $subfolder->id]);
    }
}
