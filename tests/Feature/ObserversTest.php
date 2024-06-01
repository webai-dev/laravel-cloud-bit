<?php

namespace Tests\Feature;

use App\Models\Bits\Bit;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ObserversTest extends TestCase {

    use DatabaseTransactions;

    public function itemProvider() {
        return [
            [File::class],
            [Folder::class],
            [Bit::class]
        ];
    }

    /**
     * @dataProvider itemProvider
     * @param $class
     */
    public function testCreatedActivity($class) {
        if ($class == File::class) {
            return; //File doesn't have created event
        }

        list($team, $user) = $this->getTeam();

        $item = factory($class)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);

        $this->assertDatabaseHas('activities', [
            'action'      => 'create',
            'major'       => 1,
            'target_id'   => $item->id,
            'target_type' => $item->getType()
        ]);
    }

    /**
     * @dataProvider itemProvider
     * @param $class
     */
    public function testUpdatingActivity($class) {
        if ($class == File::class) {
            return; //File doesn't have created event
        }

        list($team, $user) = $this->getTeam();

        $item = factory($class)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);
        $item->title = "Some other title";
        $item->save();

        $this->assertDatabaseHas('activities', [
            'action'      => 'edit',
            'major'       => 1,
            'target_id'   => $item->id,
            'target_type' => $item->getType()
        ]);
    }

    /**
     * @dataProvider itemProvider
     * @param $class
     */
    public function testDeletingActivity($class) {
        list($team, $user) = $this->getTeam();

        $item = factory($class)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);
        $item->delete();

        $this->assertDatabaseHas('activities', [
            'action'      => 'trash',
            'major'       => 1,
            'target_id'   => $item->id,
            'target_type' => $item->getType()
        ]);
    }

    public function testBitOpenedActivity() {
        list($team, $user) = $this->getTeam();
        $bit = factory(Bit::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id
        ]);

        $response = $this->asUser($user)->asTeam($team)
            ->fetch('/bits/' . $bit->id);

        $this->assertResponse($response);

        $this->assertDatabaseHas('activities', [
            'action'      => 'open',
            'major'       => 0,
            'target_id'   => $bit->id,
            'target_type' => 'bit'
        ]);
    }

    public function testFolderOpenedActivity() {
        list($team, $user) = $this->getTeam();
        $item = factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id
        ]);

        $response = $this->asUser($user)->asTeam($team)
            ->fetch('/folders', [
                'team_id'   => $team->id,
                'folder_id' => $item->id
            ]);

        $this->assertResponse($response);

        $this->assertDatabaseHas('activities', [
            'action'      => 'open',
            'major'       => 0,
            'target_id'   => $item->id,
            'target_type' => 'folder'
        ]);
    }

    public function testLockActivity() {
        list($team, $user) = $this->getTeam();

        $item = factory(Folder::class)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);

        //Lock the item
        $this->asUser($user)->asTeam($team)
            ->put('folders/' . $item->id . '/lock')
            ->assertStatus(200);
        $this->assertDatabaseHas('activities', [
            'action'      => 'lock',
            'major'       => 1,
            'target_id'   => $item->id,
            'target_type' => 'folder'
        ]);

        //Unlock the item
        $this->put('folders/' . $item->id . '/lock')
            ->assertStatus(200);
        $this->assertDatabaseHas('activities', [
            'action'      => 'unlock',
            'major'       => 1,
            'target_id'   => $item->id,
            'target_type' => 'folder'
        ]);
    }

    public function testFileUploadActivity() {
        list($team, $user) = $this->getTeam();
        $folder = factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id
        ]);

        Storage::fake('uploads');

        $this->asUser($user)->asTeam($team);

        $response = $this->post("files", [
            'team_id'   => $team->id,
            'folder_id' => $folder->id,
            'data'      => UploadedFile::fake()->create('sales.pdf', 40)
        ])
            ->assertStatus(200);

        $id = $response->json()['id'];

        $this->assertDatabaseHas('activities', [
            'action'      => 'upload',
            'major'       => 1,
            'target_id'   => $id,
            'target_type' => 'file'
        ]);
    }
}
