<?php

namespace Tests\Unit;

use App\Models\Folder;
use App\Models\File;
use App\Models\Share;
use App\Models\Teams\Team;
use App\Models\User;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FileDeleteTest extends TestCase
{
    protected $user;
    protected $user2;
    protected $team;
    protected $folder;
    protected $file;

    public function setUp() {
        parent::setUp();

        $this->initialize();
    }

    public function initialize() {
        $this->user = factory(User::class)->create();
        $this->user2 = factory(User::class)->create();
        $this->team = factory(Team::class)->create([
            'user_id' => $this->user->id
        ]);
        $this->team->users()->attach([$this->user->id, $this->user2->id]);
        $this->folder = factory(Folder::class)->create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id
        ]);

        Storage::fake('uploads');

        $this->asUser($this->user2)->asTeam($this->team);

        $this->post("files", [
            'team_id' => $this->team->id,
            'folder_id' => $this->folder->id,
            'data'    => UploadedFile::fake()->create('test.pdf', 40)
        ]);

        $this->file = factory(File::class)->create([
            'title'     => 'test.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'team_id'   => $this->team->id,
            'folder_id' => $this->folder->id,
            'user_id' => $this->user2->id,
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

    public function testDeleteFileCreatedByMeWithEditPermission() {
        $this->createShare($this->team, $this->user2, $this->folder, $this->user, 1, 0);

        $this->asUser($this->user2)->asTeam($this->team);
        $this->delete('files/' . $this->file->id)
            ->assertStatus(200);
    }

    public function testDeleteFileCreatedByMeWithSharePermission() {
        $this->createShare($this->team, $this->user2, $this->folder, $this->user, 1, 1);

        $this->asUser($this->user2)->asTeam($this->team);
        $this->delete('files/' . $this->file->id)
            ->assertStatus(200);
    }

    public function testDeleteFileCreatedByMeWithViewPermission() {
        $this->createShare($this->team, $this->user2, $this->folder, $this->user, 0, 0);

        $this->asUser($this->user2)->asTeam($this->team);
        $this->delete('files/' . $this->file->id)
            ->assertStatus(403);
    }
}