<?php

namespace Tests\Unit;

use App\Models\Bits\Bit;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use App\Sharing\Visitors\OwnershipUpdateVisitor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OwnershipChangeVisitorTest extends TestCase {
    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testOwnerUpdated() {
        $owner = factory(User::class)->create();
        $folder = factory(Folder::class)->create(['owner_id' => $owner->id,'user_id' => $owner->id]);

        $subfolder = factory(Folder::class)->create(['folder_id' => $folder->id]);
        $leaf = factory(Folder::class)->create(['folder_id' => $subfolder->id]);

        $file = factory(File::class)->create(['folder_id' => $folder->id, 'user_id' => $owner->id]);
        $sub_file = factory(File::class)->create(['folder_id' => $leaf->id, 'user_id' => $owner->id]);

        $bit = factory(Bit::class)->create(['folder_id' => $folder->id, 'user_id' => $owner->id]);
        $sub_bit = factory(Bit::class)->create(['folder_id' => $leaf->id, 'user_id' => $owner->id]);

        $visitor = new OwnershipUpdateVisitor();

        $folder->accept($visitor);

        $this->assertDatabaseHas('folders', ['id' => $subfolder->id, 'owner_id' => $owner->id]);
        $this->assertDatabaseHas('folders', ['id' => $leaf->id, 'owner_id' => $owner->id]);

        $this->assertDatabaseHas('bits', ['id' => $bit->id, 'owner_id' => $owner->id]);
        $this->assertDatabaseHas('bits', ['id' => $sub_bit->id, 'owner_id' => $owner->id]);

        $this->assertDatabaseHas('files', ['id' => $file->id, 'owner_id' => $owner->id]);
        $this->assertDatabaseHas('files', ['id' => $sub_file->id, 'owner_id' => $owner->id]);
    }
}
