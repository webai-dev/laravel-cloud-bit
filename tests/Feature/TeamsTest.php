<?php

namespace Tests\Feature;

use App\Models\Bits\Type;
use App\Models\Folder;
use App\Models\Teams\Subscription;
use App\Policies\BasePolicy;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Models\User;
use App\Models\Teams\Team;

class TeamsTest extends TestCase
{
    use DatabaseTransactions;

    public function testCreateTeam()
    {
        $type = factory(Type::class)->create(['public' => true]);
        $private_type = factory(Type::class)->create(['public' => false]);

        $user = factory(User::class)->create();
        $team = [
            'name' => 'Test Team',
            'photo'=> 'test photo url',
            'subdomain' => 'test-subdomain'
        ];

        $response = $this->asUser($user)
             ->post('/teams',$team)
             ->assertStatus(200)->content();

        $team_id = json_decode($response)->id;

        $this->assertDatabaseHas('teams',$team);
        $this->assertDatabaseHas('team_users',[
            'user_id' => $user->id,
            'team_id' => $team_id
        ]);
        $this->assertDatabaseHas('bit_type_teams',[
            'team_id' => $team_id,
            'type_id' => $type->id,
            'enabled' => true
        ]);
        $this->assertDatabaseMissing('bit_type_teams',[
            'team_id' => $team_id,
            'type_id' => $private_type->id,
        ]);
    }

    public function testUpdateTeamFailing(){
        list($team,$admin) = $this->getTeam();
        $user = factory(User::class)->create();
        $team->users()->attach($user->id);

        $response = $this->asUser($user)->asTeam($team)
             ->put('/teams/'.$team->id,[
                'name' => 'not name',
                'subdomain' => 'wrong'
            ]);

        $this->assertResponse($response,403);
    }

    public function testUpdateTeamSuccess(){
        list($team,$user) = $this->getTeam();

        $team_data = ['name'=>'Update Name','photo'=>'Update Photo','subdomain'=>'test-subdomain'];

        $this->asUser($user)->asTeam($team)
             ->put('/teams/'.$team->id,$team_data)
            ->assertStatus(200);

        $this->assertDatabaseHas('teams',$team_data);
    }

    public function testTeamDelete(){
        list($team,$user) = $this->getTeam();

        $this->asUser($user)->asTeam($team)
             ->delete('/teams/'.$team->id)
             ->assertStatus(200);

        $this->assertDatabaseMissing('teams',['id'=>$team->id]);
    }

    public function testRemoveUser(){
        $user = factory(User::class)->create();
        $team_user = factory(User::class)->create();

        $team = factory(Team::class)->create([
            'user_id' => $user->id
        ]);
        $folder = factory(Folder::class)->create([
            'user_id' => $team_user->id,
            'team_id' => $team->id
        ]);

        $team->users()->attach([$team_user->id,$user->id]);

        $response = $this->asUser($user)->asTeam($team)
             ->delete('/teams/'.$team->id.'/users/'.$team_user->id);

        $this->assertResponse($response);

        $this->assertDatabaseMissing('team_users',[
            'team_id'=>$team->id,
            'user_id'=>$team_user->id
        ]);

        $user_folder = Folder::query()
                             ->where('title',$team_user->name)
                             ->where('team_id',$team->id)
                             ->where('user_id',$user->id)
                             ->first();
        $this->assertNotNull($user_folder,"The user folder for the owner wasn't created");

        $this->assertDatabaseHas('folders',[
            'id'    => $folder->id,
            'title' => $folder->title,
            'user_id' => $team->user_id,
            'team_id' => $team->id,
            'folder_id' => $user_folder->id
        ]);
    }

    public function testLeave(){
        list($team,$admin) = $this->getTeam();
        $user = factory(User::class)->create();
        $team->users()->attach($user->id);

        $this->asUser($user)->asTeam($team)
             ->delete('/teams/'.$team->id.'/users/'.$user->id)
             ->assertStatus(200);
    }

    public function testRemoveUnauthorized(){
        list($team,$user) = $this->getTeam();

        $other_user = factory(User::class)->create();
        $team->users()->attach($other_user->id);

        $this->asUser($other_user)->asTeam($team)
             ->delete('/teams/'.$team->id.'/users/'.$user->id)
             ->assertStatus(403);
    }

    public function testTransfer(){
        list($team,$users) = $this->getTeamWithUsers(2);
        $old_owner = $users->first();
        $new_owner = $users->get(1);

        $new_owner->setRoleInTeam('admin',$team->id);

        $this->asUser($old_owner)->asTeam($team)
            ->put('/teams/'.$team->id.'/transfer',[
                'user_id' => $new_owner->id
            ])
            ->assertStatus(200);

        $this->assertTrue($old_owner->hasRoleInTeam('admin',$team->id),"The old owner doesn't have admin role");
        $team->refresh();
        $this->assertEquals($new_owner->id,$team->user_id,"The team ownership didn't change");
    }

    public function testSuspend(){
        /** @var User $user */
        list($team,$user) = $this->getTeam();
        $user->superuser= true;
        $user->save();

        $response = $this->asUser($user)
             ->put('teams/'.$team->id.'/suspend',[
                 'suspended' => true
             ],[BasePolicy::ADMIN_API_HEADER => true]);
        $this->assertResponse($response);

        $this->assertDatabaseHas('teams',[
            'id' => $team->id,
            'suspended' => true
        ]);

        $this->withServerVariables([]);

        $response = $this->asUser($user)->asTeam($team)->get('bits/types');

        $this->assertResponse($response,403);
    }

    public function testTeamLocked(){
        list($team, $user) = $this->getTeam();

        $sub = new Subscription();
        $sub->code ='sub_old';
        $sub->plan_code ='plan_old';
        $sub->type ='main';
        $sub->team_id = $team->id;
        $sub->status = 'unpaid';
        $sub->storage = 1;
        $sub->save();

        $response = $this->asUser($user)->asTeam($team)->get('/teams');
        $this->assertResponse($response,200);
        foreach($response->json() as $t){
            if($t['id'] == $team->id){
                $this->assertEquals($t['locked'],true);
                break;
            }
        }

    }
}
