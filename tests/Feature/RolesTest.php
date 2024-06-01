<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Teams\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;


class RolesTest extends TestCase
{
    use DatabaseTransactions;

    public function testChange(){

        /** @var Team $team */
        /** @var Collection $users*/
        list($team,$users) = $this->getTeamWithUsers(3,'member');

        /** @var Role $admin_role */
        $admin_role = Role::query()->where('label','admin')->firstOrFail();
        /** @var Role $member_role */
        $member_role = Role::query()->where('label','member')->firstOrFail();
        /** @var Role $guest_role */
        $guest_role = Role::query()->where('label','guest')->firstOrFail();

        $owner = $users->first();
        /** @var User $member */
        $member = $users->get(1);
        $guest = $users->get(2);

        list($team2) = $this->getTeam($owner);
        $member->setRoleInTeam('member',$team2->id);
        $guest->setRoleInTeam('guest',$team2->id);

        $this->asUser($owner)->asTeam($team)
             ->put('teams/'.$team->id.'/users/'.$member->id,[
                 'role_id' => $admin_role->id
             ])
             ->assertStatus(200);

        $this->assertDatabaseHas('user_team_roles',[
            'user_id' => $member->id,
            'role_id' => $admin_role->id,
            'team_id' => $team->id
        ]);

        //Assert new roles didn't change
        $this->assertDatabaseHas('user_team_roles',[
            'user_id' => $guest->id,
            'role_id' => $guest_role->id,
            'team_id' => $team2->id
        ]);

        $this->assertDatabaseHas('user_team_roles',[
            'user_id' => $member->id,
            'role_id' => $member_role->id,
            'team_id' => $team2->id
        ]);

    }
}
