<?php

namespace Tests\Feature;

use App\Models\File;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileVersionsTest extends TestCase {
    use DatabaseTransactions;

    public function testIndex() {
        list($team, $user) = $this->getTeam();

        $path = 'files/user_' . $user->id . '/1234.jpg';
        $file = factory(File::class)->create([
            'team_id'   => $team->id,
            'user_id'   => $user->id,
            'title'     => 'magazine.jpg',
            'mime_type' => 'image/jpg',
            'size'      => 30,
            'path'      => $path
        ]);
        $file->versions()->create([
            'name'     => 'Version 1',
            'filename' => 'test',
            'user_id'  => $user->id,
            's3_id'    => 'test'
        ]);

        $this->asUser($user)->asTeam($team);

        $this->get("files/" . $file->id . "/versions")
            ->assertStatus(200)
            ->assertJson([
                ['name' => 'Version 1']
            ]);
    }

    public function testCreateError() {
        list($team, $user) = $this->getTeam();

        $path = 'files/user_' . $user->id . '/1234.jpg';
        $file = factory(File::class)->create([
            'team_id'   => $team->id,
            'user_id'   => $user->id,
            'title'     => 'magazine.jpg',
            'mime_type' => 'image/jpg',
            'size'      => 30,
            'path'      => $path
        ]);

        Storage::fake('uploads');

        $this->asUser($user)->asTeam($team);

        $this->post("files/" . $file->id . "/versions", [
            'team_id' => $team->id,
            'data'    => UploadedFile::fake()->create('sales.pdf', 40)
        ])->assertStatus(400);

        $this->assertDatabaseMissing('files', [
            'id'        => $file->id,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
        ]);

        Storage::disk('uploads')->assertMissing($path);
    }

    public function testCreateSuccess() {
        list($team, $user) = $this->getTeam();

        $path = 'files/user_' . $user->id . '/1234.jpg';
        $file = factory(File::class)->create([
            'team_id'   => $team->id,
            'user_id'   => $user->id,
            'title'     => 'magazine.jpg',
            'mime_type' => 'image/jpg',
            'extension' => 'jpg',
            'size'      => 30,
            'path'      => $path
        ]);
        $file->versions()->create([
            'name'     => 'Version 1',
            'filename' => 'test',
            'user_id'  => $user->id,
            's3_id'    => 'test'
        ]);

        Storage::fake('uploads');

        $this->asUser($user)->asTeam($team);

        $response = $this->post("files/" . $file->id . "/versions", [
            'team_id' => $team->id,
            'data'    => UploadedFile::fake()->create('sales.jpg')
        ]);

        $this->assertResponse($response);

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
        ]);

        $this->assertDatabaseHas('file_versions',[
           'file_id' => $file->id,
           'name' => 'Version 2'
        ]);

        Storage::disk('uploads')->assertExists($path);
    }

    public function testShow() {
        list($team, $user) = $this->getTeam();

        $path = 'files/user_' . $user->id . '/1234.jpg';
        $file = factory(File::class)->create([
            'team_id'   => $team->id,
            'user_id'   => $user->id,
            'title'     => 'magazine.jpg',
            'mime_type' => 'image/jpg',
            'size'      => 30,
            'path'      => $path
        ]);
        $version = $file->versions()->create([
            'name'     => 'Version 1',
            'filename' => 'test',
            'user_id'  => $user->id,
            's3_id'    => 'test'
        ]);

        $this->asUser($user)->asTeam($team);

        $this->get("files/" . $file->id . "/versions/" . $version->id)->assertStatus(200);
    }

    public function testUpdate() {
        list($team, $user) = $this->getTeam();

        $path = 'files/user_' . $user->id . '/1234.jpg';
        $file = factory(File::class)->create([
            'team_id'   => $team->id,
            'user_id'   => $user->id,
            'title'     => 'magazine.jpg',
            'mime_type' => 'image/jpg',
            'size'      => 30,
            'path'      => $path
        ]);
        $version = $file->versions()->create([
            'name'     => 'Version 1',
            'filename' => 'test',
            'user_id'  => $user->id,
            's3_id'    => 'test'
        ]);

        $this->asUser($user)->asTeam($team);

        $this->put("files/" . $file->id . "/versions/" . $version->id, [
            'name' => 'Test Version Rename',
            'keep' => true
        ])->assertStatus(200);

        $this->assertDatabaseHas('file_versions', [
            'name'    => 'Test Version Rename',
            'keep'    => true,
            'file_id' => $file->id
        ]);
    }

    public function testDelete() {
        list($team, $user) = $this->getTeam();

        $path = 'files/user_' . $user->id . '/1234.jpg';
        $file = factory(File::class)->create([
            'team_id'   => $team->id,
            'user_id'   => $user->id,
            'title'     => 'magazine.jpg',
            'mime_type' => 'image/jpg',
            'size'      => 30,
            'path'      => $path
        ]);
        $version = $file->versions()->create([
            'name'     => 'Version 1',
            'filename' => 'test',
            'user_id'  => $user->id,
            's3_id'    => 'test'
        ]);

        $this->asUser($user)->asTeam($team);

        $this->delete("files/" . $file->id . "/versions/" . $version->id)->assertStatus(200);

        $this->assertDatabaseMissing('file_versions', [
            'file_id' => $file->id
        ]);
    }

    public function testDeleteError(){
        list($team, $user) = $this->getTeam();

        $path = 'files/user_' . $user->id . '/1234.jpg';
        $file = factory(File::class)->create([
            'team_id'   => $team->id,
            'user_id'   => $user->id,
            'title'     => 'magazine.jpg',
            'mime_type' => 'image/jpg',
            'size'      => 30,
            'path'      => $path
        ]);
        $version = $file->versions()->create([
            'name'     => 'Version 1',
            'filename' => 'test',
            'user_id'  => $user->id,
            's3_id'    => 'test',
            'current'  => true
        ]);

        $this->asUser($user)->asTeam($team);

        $this->delete("files/" . $file->id . "/versions/" . $version->id)->assertStatus(400);

        $this->assertDatabaseHas('file_versions', [
            'file_id' => $file->id
        ]);
    }

    public function testDeleteRestore(){
        list($team, $user) = $this->getTeam();

        $path = 'files/user_' . $user->id . '/1234.jpg';
        /** @var File $file */
        $file = factory(File::class)->create([
            'team_id'   => $team->id,
            'user_id'   => $user->id,
            'title'     => 'magazine.jpg',
            'mime_type' => 'image/jpg',
            'size'      => 30,
            'path'      => $path,
            'keep'      => false
        ]);
        $this->be($user);
        $version1 = $file->makeVersion();
        Carbon::setTestNow(Carbon::now()->addMinute());
        $file->s3_version_id = "a wrong version";
        $version2 = $file->makeVersion();

        $this->asUser($user)->asTeam($team);

        $response = $this->delete("files/" . $file->id . "/versions/" . $version2->id);
        $this->assertResponse($response);

        $this->assertDatabaseHas('file_versions', [
            'id' => $version1->id,
            'current' => true
        ]);

        $this->assertDatabaseHas('files',[
           'id' => $file->id,
           's3_version_id' => $version1->s3_id
        ]);
    }
}
