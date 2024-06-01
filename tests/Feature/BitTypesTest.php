<?php

namespace Tests\Feature;

use App\Models\Bits\Type;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BitTypesTest extends TestCase {

    use DatabaseTransactions;

    public function testToggle() {
        list($team, $user) = $this->getTeam();
        $type = factory(Type::class)->create();
        $type->teams()->attach($team->id);

        $response = $this->asUser($user)
                         ->asTeam($team)
                         ->put('bits/types/' . $type->id . '/toggle');

        $this->assertResponse($response);

        $this->assertDatabaseHas('bit_type_teams', [
            'type_id' => $type->id,
            'team_id' => $team->id,
            'enabled' => false,
        ]);

        $response = $this->fetch('bits/types', ['enabled' => true]);

        $this->assertResponse($response)
             ->assertJsonMissing([
                 'id' => $type->id,
             ]);
    }
}
