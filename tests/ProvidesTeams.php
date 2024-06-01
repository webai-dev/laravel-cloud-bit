<?php
namespace Tests;

use App\Models\User;
use App\Models\Teams\Team;

trait ProvidesTeams
{
    /**
     * Provides a team with a user for testing
     * 
     * @param User $user User to attach to the team, or auto-generate
     * @return array
     */ 
    protected function getTeam(User $user = null){
        $user = $user ? $user : factory(User::class)->create();
        $team = factory(Team::class)->create([
            'user_id' => $user->id
        ]);
        $team->users()->attach($user->id);

        return [$team,$user];
    }
    
    /**
     * Provides a team with the number of specified users, the first of which is the owner
     * 
     * @param int $count=1 Number of users to attach
     * @param string $role The role to use for the users of the team (except the owner)
     * @return array
     */ 
    protected function getTeamWithUsers($count = 1,$role = null){
        if ($count < 1) {
            throw new \InvalidArgumentException("User count must be at least 1");
        }
        
        $users = factory(User::class,$count)->create();
        $team = factory(Team::class)->create([
            'user_id' => $users->first()->id
        ]);
        
        $team->users()->attach($users->pluck('id')->toArray());

        if ($role != null){
            /** @var User $user */
            foreach ($users as $user){
                if($user->id == $team->user_id) continue;
                $user->setRoleInTeam($role,$team->id);
            }
        }

        return [$team,$users];        
    }

}