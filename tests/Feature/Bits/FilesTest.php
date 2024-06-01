<?php

namespace Tests\Feature\Bits;

use App\Models\Bits\Bit;
use App\Models\Bits\BitFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilesTest extends TestCase {
    use DatabaseTransactions;

    public function testUpload() {
        list($team, $user) = $this->getTeam();

        $bit = factory(Bit::class)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);

        Storage::fake('uploads');
        $filename = 'test.png';

        $response = $this->asBit($bit, $user)
            ->post('integration/' . $bit->type_id . '/files', [
                'data' => UploadedFile::fake()->create($filename, 5)
            ]);
        $this->assertResponse($response);

        $this->assertDatabaseHas('bit_files', [
            'bit_id'   => $bit->id,
            'filename' => $filename
        ]);
    }

    public function testDelete() {
        list($team, $user) = $this->getTeam();

        $bit = factory(Bit::class)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);

        $name = "test.jpg";
        $path = 'files/bits';
        $file = BitFile::create([
            'bit_id'    => $bit->id,
            'filename'  => $name,
            'mime_type' => 'image/jpg',
            'extension' => 'jpg',
            'size'      => 3,
            'path'      => $path . "/" . $name
        ]);

        Storage::fake('uploads');
        Storage::disk('uploads')->putFileAs($path, UploadedFile::fake()->create($name, 3), $name);

        $response = $this->asBit($bit, $user)
            ->delete('integration/' . $bit->type_id . '/files/' . $file->id);
        $this->assertResponse($response);

        $this->assertEquals(0, $bit->files()->count());
    }

}
