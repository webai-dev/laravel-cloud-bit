<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use App\Models\User;
use App\Models\Teams\Team;
use App\Models\File;
use App\Models\Folder;

class FilesTest extends TestCase{
    
    use DatabaseTransactions;
    
    public function testCreate(){
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create([
            'user_id' => $user->id
        ]);
        $team->users()->attach($user->id);
        $folder = factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id
        ]);

        Storage::fake('uploads');

        $this->asUser($user)->asTeam($team);

        $response = $this->post("files",[
            'team_id' => $team->id,
            'folder_id' => $folder->id,
            'data'    => UploadedFile::fake()->create('sales.pdf',40)
        ]);

        $this->assertResponse($response);

        $this->assertDatabaseHas('files',[
            'title'     => 'sales.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'team_id'   => $team->id,
            'folder_id' => $folder->id
        ]);

        $this->assertDatabaseHas('file_versions',[
           'name' => 'Version 1',
           'filename' => 'sales.pdf',
           'user_id'  => $user->id
        ]);

        $file = File::find($response->json()['id']);

        Storage::disk('uploads')->assertExists($file->path);
    }
    
    public function testExceedLimit(){
        $limit = 50;
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create([
            'user_id'       => $user->id,
            'storage_limit' => $limit * 1024
        ]);
        $team->users()->attach($user->id);
        $folder = factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id
        ]);
        
        Storage::fake('uploads');
        
        $this->asUser($user)->asTeam($team);
        
        $response = $this->post("files",[
            'team_id'   => $team->id,
            'folder_id' => $folder->id,
            'data'      => UploadedFile::fake()->create('sales.pdf',$limit + 1)
        ]);

        $this->assertResponse($response,400);
    }
    
    public function testRename(){
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create([
            'user_id' => $user->id
        ]);
        $team->users()->attach($user->id);
        
        $file = factory(File::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id
        ]);
        
        $title = "New title";
        
        $response = $this->asUser($user)
             ->asTeam($team)
             ->put('files/'.$file->id,[
                'title'   => $title,
                'team_id' => $team->id
            ]);

        $this->assertResponse($response);
        
        $this->assertDatabaseHas('files',compact('title'));
    }
    
    public function testCopy(){
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create([
            'user_id' => $user->id
        ]);
        $team->users()->attach($user->id);
        
        $path = 'files/user_'.$user->id;
        $name = "1234.pdf";
        
        $file = factory(File::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'path'    => $path.'/'.$name
        ]);
        
        $folder = factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id
        ]);
        
        Storage::fake('uploads');
        Storage::disk('uploads')->putFileAs($path,UploadedFile::fake()->create($name,40),$name);
        
        $this->asUser($user)->asTeam($team);
        
        $response = $this->put('files/'.$file->id.'/copy',[
                    'team_id' => $team->id,
                    'folder_id' => $folder->id
                ]);
        $this->assertResponse($response);
        
        //Check both files exist 
        $this->assertDatabaseHas('files',[
                'title'     => $file->title,
                'folder_id' => $folder->id
            ]);
        $this->assertDatabaseHas('files',[
                'title'     => $file->title,
                'folder_id' => null
            ]);
        
        $new_file = File::find($response->json()['id']);
        
        Storage::disk('uploads')->assertExists($file->path);
        Storage::disk('uploads')->assertExists($new_file->path);
    }
    
    public function testSoftDelete(){
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create([
            'user_id' => $user->id
        ]);
        $team->users()->attach($user->id);
        
        $path = 'files/user_'.$user->id;
        $name = "1234.pdf";
        
        $file = factory(File::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'path'    => $path.'/'.$name
        ]);
        
        factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id
        ]);
        
        Storage::fake('uploads');
        Storage::disk('uploads')->putFileAs($path,UploadedFile::fake()->create($name,40),$name);
        
        $this->asUser($user)->asTeam($team)
             ->delete('files/'.$file->id.'/trash')
             ->assertStatus(200);
        
        $this->assertSoftDeleted('files',['id'=>$file->id]);
        Storage::disk('uploads')->assertExists($file->path);
    }
    
    public function testDelete(){
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create([
            'user_id' => $user->id
        ]);
        $team->users()->attach($user->id);
        
        $path = 'files/user_'.$user->id;
        $name = "1234.pdf";
        
        $file = factory(File::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'path'    => $path.'/'.$name
        ]);
        
        factory(Folder::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id
        ]);
        
        Storage::fake('uploads');
        Storage::disk('uploads')->putFileAs($path,UploadedFile::fake()->create($name,40),$name);
        
        $this->asUser($user)
             ->asTeam($team)
             ->delete('files/'.$file->id)
             ->assertStatus(200);
        
        $this->assertDatabaseMissing('files',['id'=>$file->id]);
        Storage::disk('uploads')->assertMissing($file->path);
    }
    
    public function testPublish(){
        list($team,$user) = $this->getTeam();
        
        $path = 'files/user_'.$user->id;
        $name = "1234.pdf";
        
        $file = factory(File::class)->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'path'    => $path.'/'.$name
        ]); 
        
        $this->asUser($user)
             ->asTeam($team)
             ->get('files/'.$file->id.'/link')
             ->assertStatus(200);
             
        $file->refresh();
        
        $this->assertNotEquals($file->public_token,null);
    }
}
