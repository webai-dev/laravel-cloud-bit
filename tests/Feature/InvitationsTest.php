<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Share;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Models\User;
use App\Models\Folder;
use App\Models\Teams\Team;
use App\Models\Teams\Invitation;
use App\Models\Teams\TeamShareable;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use App\Notifications\InvitationCreated;

class InvitationsTest extends TestCase
{
    use DatabaseTransactions;

    public function testCreateInvitation()
    {
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create([
            'user_id'=> $user->id
        ]);

        $member_role = Role::where('label','member')->firstOrFail();
        $guest_role = Role::where('label','guest')->firstOrFail();

        $team->users()->attach($user->id);
        
        $payload = [
            'team_id' => $team->id,
            'invitations' => [
                [
                    'contact' => '+0000000000',
                    'role_id' => $member_role->id
                ],
                [
                    'contact' => 'test@email.com',
                    'role_id' => $guest_role->id
                ]
            ]
        ];
        
        Notification::fake();
        Mail::fake();
        
        $this->asUser($user)->asTeam($team)
             ->post('/invitations',$payload)
             ->assertStatus(201);

        foreach($payload['invitations'] as $invitation){
            $this->assertDatabaseHas('team_invitations',[
                'team_id' => $team->id,
                'role_id' => $invitation['role_id'],
                'contact' => $invitation['contact']
            ]);

            $model = Invitation::where('contact',$invitation['contact'])->first();
            Notification::assertSentTo($model,InvitationCreated::class);
        }
    }
    
    public function testCreateExisting(){
        $user = factory(User::class)->create();
        $guest_role = Role::where('label','guest')->firstOrFail();
        $team = factory(Team::class)->create([
            'user_id'=> $user->id
        ]);
        
        $team->users()->save($user);
        $existing = 'test@email.com';
        $new = 'test2@email.com';

        $team->invitations()->create([
            'contact' => $existing,
            'user_id' => $user->id,
            'role_id' => $guest_role->id
        ]);


        $payload = [
            'team_id' => $team->id,
            'invitations' => [
                [
                    'contact' => $new,
                    'role_id' => $guest_role->id
                ],
                [
                    'contact' => $existing,
                    'role_id' => $guest_role->id
                ]
            ]
        ];
        
        Notification::fake();
        Mail::fake();
        
        $this->asUser($user)->asTeam($team)
             ->post('/invitations',$payload)
             ->assertStatus(400);
             
        $this->assertDatabaseMissing('team_invitations',
            ['team_id' => $team->id, 'contact' => $new]
        );
    }
    
    public function testAccept(){
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();

        $team_user = factory(User::class)->create();
        
        $team = factory(Team::class)->create([
            'user_id'=> $team_user->id
        ]);
        
        $invitation = $team->invitations()->create([
            'contact'=> strtoupper($user->email),
            'user_id'=> $team_user->id,
            'role_id'=> $role->id
        ]);
        
        $this->asUser($user)
             ->put('/invitations/'.$invitation->id,[
                'accepted' => true
            ])
             ->assertStatus(200);

        $this->assertDatabaseHas('team_invitations',[
            'team_id'=>$team->id, 'contact'=> strtoupper($user->email),'status'=>'accepted'
        ]);
        $this->assertDatabaseHas('team_users',[
            'team_id'=>$team->id,'user_id'=>$user->id
        ]);
        $this->assertDatabaseHas('user_team_roles',[
            'team_id'=>$team->id,'user_id'=>$user->id,'role_id'=>$role->id
        ]);
    }
    
    public function testAcceptWithShared(){
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();

        list($team,$owner) = $this->getTeam();
        
        $invitation = $team->invitations()->create([
            'contact'=> strtoupper($user->email),
            'user_id'=> $owner->id,
            'role_id'=> $role->id
        ]);
        
        $item = factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $owner->id
        ]);
        
        $share_data = [
            'team_id' => $team->id,
            'share'   => 0,
            'edit'    => 1,
            'shareable_type' => $item->getType(),
            'shareable_id'   => $item->id,
            'created_by_id'  => $owner->id
        ];
        
        TeamShareable::create($share_data);
        
        Mail::fake();
        
        $this->asUser($user)
             ->put('/invitations/'.$invitation->id,[
                'accepted' => true
            ])
             ->assertStatus(200);
        
        $this->assertDatabaseHas('shares',array_merge($share_data,[
            'user_id' => $user->id
        ]));

        $shares_created = Share::where($share_data)->count();
        $this->assertEquals(1,$shares_created,"More shares created than expected");
    }
    
    public function testReject(){
        $user = factory(User::class)->create();
        $team_user = factory(User::class)->create();
        $role = factory(Role::class)->create();

        $team = factory(Team::class)->create([
            'user_id'=> $team_user->id
        ]);
        
        $invitation = $team->invitations()->create([
            'contact'=> strtoupper($user->email),
            'user_id'=> $team_user->id,
            'role_id'=> $role->id
        ]);
        
        $this->asUser($user)
             ->put('/invitations/'.$invitation->id,[
                'accepted' => false
            ])
             ->assertStatus(200);
             
        $this->assertDatabaseHas('team_invitations',[
            'team_id'=>$team->id, 'contact'=> strtoupper($user->email),'status'=>'rejected'
        ]);
        $this->assertDatabaseMissing('team_users',[
            'team_id'=>$team->id,'user_id'=>$user->id
        ]);        
    }
}
