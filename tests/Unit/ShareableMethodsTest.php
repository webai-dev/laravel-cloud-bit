<?php

namespace Tests\Unit;

use App\Models\Folder;
use Tests\TestCase;

class ShareableMethodsTest extends TestCase {

    public function testHasCommonParent() {
        $ancestor = factory(Folder::class)->create();
        $child_1 = factory(Folder::class)->create(['folder_id' => $ancestor->id]);
        $child_2 = factory(Folder::class)->create(['folder_id' => $ancestor->id]);

        $this->assertTrue($child_1->hasCommonAncestorWith($child_2));
        $this->assertTrue($child_2->hasCommonAncestorWith($child_1));
    }

    public function testHasCommonAncestor(){
        $ancestor = factory(Folder::class)->create();
        $child_1 = factory(Folder::class)->create(['folder_id' => $ancestor->id]);
        $child_2 = factory(Folder::class)->create(['folder_id' => $ancestor->id]);
        $grandchild = factory(Folder::class)->create(['folder_id' => $child_2->id]);

        $this->assertTrue($grandchild->hasCommonAncestorWith($child_1));
        $this->assertTrue($child_1->hasCommonAncestorWith($grandchild));
    }

    public function testHasNoCommonParent(){
        $ancestor = factory(Folder::class)->create();
        $child_1 = factory(Folder::class)->create(['folder_id' => $ancestor->id]);
        $child_2 = factory(Folder::class)->create();

        $this->assertFalse($child_2->hasCommonAncestorWith($child_1));
        $this->assertFalse($child_1->hasCommonAncestorWith($child_2));
    }

    public function testHasNoCommonAncestor(){
        $ancestor = factory(Folder::class)->create();
        $child_1 = factory(Folder::class)->create(['folder_id' => $ancestor->id]);
        $child_2 = factory(Folder::class)->create();
        $grandchild = factory(Folder::class)->create(['folder_id' => $child_2->id]);

        $this->assertFalse($grandchild->hasCommonAncestorWith($child_1));
        $this->assertFalse($child_1->hasCommonAncestorWith($grandchild));
    }
}
