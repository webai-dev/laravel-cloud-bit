<?php

namespace Tests\Unit;

use App\Models\Folder;
use App\Models\Share;
use App\Models\Teams\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FolderDeleteTest extends TestCase {
    use DatabaseTransactions;

    protected $user;
    protected $user2;
    protected $team;
    protected $folder;
    protected $subfolder;

    public function setUp() {
        parent::setUp();

        $this->initialize();
    }

    public function initialize() {
        $this->user = factory(User::class)->create();
        $this->user2 = factory(User::class)->create();
        $this->team = factory(Team::class)->create(['user_id' => $this->user->id]);
        $this->team->users()->attach([$this->user->id, $this->user2->id]);
        $this->folder = $this->createFolder($this->team, $this->user);
    }

    public function createFolder($team, $user, $folder = NULL) {
        return factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'folder_id' => $folder !== NULL ? $folder->id : NULL,
        ]);
    }

    public function createShare($team, $user, $folder, $creator, $edit = 0, $share = 0) {
        return Share::create([
            'team_id'        => $team->id,
            'user_id'        => $user->id,
            'edit'           => $edit,
            'share'          => $share,
            'shareable_type' => 'folder',
            'shareable_id'   => $folder->id,
            'created_by_id'  => $creator->id
        ]);
    }

    public function testDeleteSharedFolderCreatedByOthersWithEditPermission() {
        $this->subfolder = $this->createFolder($this->team, $this->user, $this->folder);

        $this->createShare($this->team, $this->user2, $this->folder, $this->user, 1, 0);

        $this->asUser($this->user2)->asTeam($this->team);
        $this->delete('folders/' . $this->subfolder->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('folders', ['id' => $this->subfolder->id]);
    }

    public function testDeleteSharedFolderCreatedByOthersWithSharePermission() {
        $this->subfolder = $this->createFolder($this->team, $this->user, $this->folder);

        $this->createShare($this->team, $this->user2, $this->folder, $this->user, 1, 1);

        $this->asUser($this->user2)->asTeam($this->team);
        $this->delete('folders/' . $this->subfolder->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('folders', ['id' => $this->subfolder->id]);
    }

    public function testDeleteSharedFolderCreatedByOthersWithViewPermission() {
        $this->subfolder = $this->createFolder($this->team, $this->user, $this->folder);

        $this->createShare($this->team, $this->user2, $this->folder, $this->user, 0, 0);

        $this->asUser($this->user2)->asTeam($this->team);
        $this->delete('folders/' . $this->subfolder->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('folders', ['id' => $this->subfolder->id]);
    }

    public function testDeleteSharedFolderCreatedByMeWithEditPermission() {
        $this->subfolder = $this->createFolder($this->team, $this->user2, $this->folder);

        $this->createShare($this->team, $this->user2, $this->folder, $this->user, 1, 0);

        $this->asUser($this->user2)->asTeam($this->team);
        $this->delete('folders/' . $this->subfolder->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('folders', ['id' => $this->subfolder->id]);
    }

    public function testDeleteSharedFolderCreatedByMeWithSharePermission() {
        $this->subfolder = $this->createFolder($this->team, $this->user2, $this->folder);

        $this->createShare($this->team, $this->user2, $this->folder, $this->user, 1, 1);

        $this->asUser($this->user2)->asTeam($this->team);
        $this->delete('folders/' . $this->subfolder->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('folders', ['id' => $this->subfolder->id]);
    }

    public function testDeleteSharedFolderCreatedByMeWithViewPermission() {
        $this->subfolder = $this->createFolder($this->team, $this->user2, $this->folder);

        $this->createShare($this->team, $this->user2, $this->folder, $this->user, 0, 0);

        $this->asUser($this->user2)->asTeam($this->team);
        $this->delete('folders/' . $this->subfolder->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('folders', ['id' => $this->subfolder->id]);
    }
}