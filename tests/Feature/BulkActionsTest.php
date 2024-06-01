<?php

namespace Tests\Feature;

use App\Models\Teams\Team;
use App\Sharing\Shareable;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Models\User;
use App\Models\File;
use App\Models\Folder;
use App\Models\Bits\Bit;

class BulkActionsTest extends TestCase
{
    use DatabaseTransactions;
    
    public function itemProvider(){
        return [
            ['files',File::class],
            ['folders',Folder::class],
            ['bits',Bit::class]
        ];
    }
    
    /**
     * @dataProvider itemProvider
     * @param string $url
     * @param string $model
     */ 
    public function testBulkDeleteSuccess($url,$model){
        list($team,$user) = $this->getTeam();
        
        $items = factory($model,3)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);
        
        $this->asUser($user)->asTeam($team)
             ->delete("bulk/trash",[
                $url => $items->pluck('id')->toArray()    
            ])
             ->assertStatus(200);

        /** @var Shareable $item */
        foreach ($items as $item) {
            $this->assertSoftDeleted($url,array_except($item->getAttributes(),['updated_at']));
        }
    }
    
    /**
     * @dataProvider itemProvider
     * @param string $url
     * @param string $model
     */
    public function testBulkDeleteFailing($url,$model){
        /** @var Team $team */
        list($team,$user) = $this->getTeam();
        
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);
        
        $items = factory($model,3)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);
        
        $this->asUser($user2)->asTeam($team)
             ->delete("bulk/trash",[
                $url => $items->pluck('id')->toArray()    
            ])
             ->assertStatus(403);
    }
    
    /**
     * @dataProvider itemProvider
     * @param string $url
     * @param string $model
     */ 
    public function testBulkMove($url,$model){
        list($team,$user) = $this->getTeam();
        
        $items = factory($model,3)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);
        
        $folder = factory(Folder::class)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);
        
        $this->asUser($user)->asTeam($team)
             ->put("bulk/move",[
                $url        => $items->pluck('id')->toArray(),
                'folder_id' => $folder->id,
                'team_id'   => $team->id
            ])
             ->assertStatus(200);
             
        foreach ($items as $item) {
            $this->assertDatabaseHas($url,['folder_id' => $folder->id,'id' => $item->id]);
        }
    }
    
    /**
     * @dataProvider itemProvider
     * @param string $url
     * @param string $model
     */
    public function testBulkShare($url,$model){
        /** @var Team $team */
        list($team,$user) = $this->getTeam();
        $users = factory(User::class,3)->create();
        $user_ids = $users->pluck('id')->toArray();
        $team->users()->attach($user_ids);

        /** @var Collection $items */
        $items = factory($model,3)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ])->map(function(Shareable $item){
            return [
                'shareable_type' => $item->getType(),
                'shareable_id'   => $item->id
            ];  
        });
        
        $this->asUser($user)->asTeam($team)
             ->post("shares/bulk",[
                'items'   => $items->toArray(),
                'users'   => $user_ids,
                'team_id' => $team->id,
                'edit'    => 1,
                'share'   => 0
            ])
             ->assertStatus(200);
        
        foreach($items as $item){
            foreach ($user_ids as $user_id) {
                $item_data = array_merge($item,
                        ['team_id' => $team->id,
                        'user_id'  => $user_id,
                        'edit'     => 1,
                        'share'    => 0]
                );
                $this->assertDatabaseHas('shares',$item_data);
            }
        }
    }
}
