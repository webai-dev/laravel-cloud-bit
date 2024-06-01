<?php

namespace Tests\Feature;

use App\Models\Teams\Integration;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IntegrationsTest extends TestCase {
    use DatabaseTransactions;

    public function testIndex() {
        list($team, $user) = $this->getTeam();
        $integration = factory(Integration::class)->create(['team_id' => $team->id]);
        $this->asUser($user)
            ->asTeam($team)
            ->get('teams/' . $team->id . '/integrations')
            ->assertSuccessful()
            ->assertJson([
                [
                    'id'     => $integration->id,
                    'secret' => $integration->secret
                ]
            ]);
    }

    public function testCreate() {
        list($team, $user) = $this->getTeam();
        $this->asUser($user)
            ->asTeam($team)
            ->post('teams/' . $team->id . '/integrations', [
                'name' => 'test'
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('team_integrations', [
            'team_id' => $team->id,
            'name'    => 'test'
        ]);
    }

    public function testUpdate() {
        list($team, $user) = $this->getTeam();
        $integration = factory(Integration::class)->create(['team_id' => $team->id]);
        $this->asUser($user)
            ->asTeam($team)
            ->put('teams/' . $team->id . '/integrations/' . $integration->id, [
                'name' => $integration->name . "_test"
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('team_integrations', [
            'id'   => $integration->id,
            'name' => $integration->name . '_test'
        ]);
    }

    public function testDelete() {
        list($team, $user) = $this->getTeam();
        $integration = factory(Integration::class)->create(['team_id' => $team->id]);
        $this->asUser($user)
            ->asTeam($team)
            ->delete('teams/' . $team->id . '/integrations/' . $integration->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('team_integrations', [
            'id' => $integration->id,
        ]);
    }

}
