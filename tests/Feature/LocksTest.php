<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Models\User;
use App\Models\File;
use App\Models\Folder;
use App\Models\Bits\Bit;
use App\Models\Teams\Team;

class LocksTest extends TestCase
{
    use DatabaseTransactions;
    
    public function itemProvider(){
        return  [
            ['files',File::class],
            ['folders',Folder::class],
            ['bits',Bit::class]
        ];
    }
    
    /**
     * @dataProvider itemProvider
     */ 
    public function testLockFlow($url,$model){
        
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create([
            'user_id' => $user->id
        ]);
        $team->users()->attach($user->id);
        
        $item = factory($model)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);
        
        //Lock the item
        $this->asUser($user)->asTeam($team)
             ->put($url.'/'.$item->id.'/lock')
             ->assertStatus(200);
             
        $this->get($url.'/locked?'.http_build_query(['team_id' => $team->id ]))
             ->assertStatus(200)
             ->assertJson([
                ['title' => $item->title]
            ]);
            
        $fetched = $model::where('id',$item->id)->withLocked($user->id)->first();
        $this->assertSame(true,$fetched->is_locked);
        
        //Unlock the item
        $this->put(''.$url.'/'.$item->id.'/lock')
             ->assertStatus(200);
             
        $this->get($url.'/locked?'.http_build_query(['team_id' => $team->id ]))
             ->assertStatus(200)
             ->assertJson([]);
             
        $fetched = $model::where('id',$item->id)->withLocked($user->id)->first();
        $this->assertSame(null,$fetched->is_locked);
    }
}
