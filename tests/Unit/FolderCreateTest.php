<?php

namespace Tests\Unit;

use App\Models\Folder;
use App\Models\Share;
use App\Models\Teams\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FolderCreateTest extends TestCase {
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
            'owner_id' => $user->id,
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

    public function testCreateFolderInsideShareFolderWithSharePermission() {
        $this->createShare($this->team, $this->user2, $this->folder, $this->user, 1, 1);

        $this->asUser($this->user2)->asTeam($this->team);
        $response = $this->post('folders', [
            'team_id' => $this->team->id,
            'title'   => "My test folder",
            'user_id' => $this->user->id,
            'folder_id' => $this->folder->id,
            'owner_id' => $this->user->id,
        ])->assertStatus(200);

        $this->assertDatabaseHas('folders', ['id' => $response->baseResponse->original->id]);
    }

    public function testCreateFolderInsideShareFolderWithEditPermission() {
        $this->createShare($this->team, $this->user2, $this->folder, $this->user, 1, 0);

        $this->asUser($this->user2)->asTeam($this->team);
        $response = $this->post('folders', [
            'team_id' => $this->team->id,
            'title'   => "My test folder",
            'user_id' => $this->user->id,
            'folder_id' => $this->folder->id,
            'owner_id' => $this->user->id,
        ])->assertStatus(200);

        $this->assertDatabaseHas('folders', ['id' => $response->baseResponse->original->id]);
    }

    public function testCreateFolderInsideShareFolderWithViewPermission() {
        $this->createShare($this->team, $this->user2, $this->folder, $this->user, 0, 0);

        $this->asUser($this->user2)->asTeam($this->team);
        $response = $this->post('folders', [
            'team_id' => $this->team->id,
            'title'   => "My test folder",
            'user_id' => $this->user->id,
            'folder_id' => $this->folder->id,
            'owner_id' => $this->user->id,
        ])->assertStatus(403);

        $response->assertSee('AuthorizationException');
    }
}