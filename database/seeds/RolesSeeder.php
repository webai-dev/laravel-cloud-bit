<?php

use App\Models\Role;
use App\Models\Teams\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            ['name' => 'Administrator', 'label'=>'admin'],
            ['name' => 'Member', 'label'=>'member'],
            ['name' => 'Guest', 'label'=>'guest'],
        ];

        Role::insert($roles);

        $member_role = Role::where('label','member')->first();

        $teams = Team::query()->with('users')->get();

        $user_roles = [];
        foreach($teams as $team){
            foreach($team->users as $user){
                if($user->id == $team->user_id) continue;

                $user_roles[] = [
                    'team_id' => $team->id,
                    'role_id' => $member_role->id,
                    'user_id' => $user->id
                ];
            }
        }

        DB::table('user_team_roles')->insert($user_roles);
        DB::table('team_invitations')->update(['role_id' => $member_role->id]);
    }
}
