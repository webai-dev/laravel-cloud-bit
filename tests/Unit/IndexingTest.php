<?php

namespace Tests\Unit;

use App\Models\Bits\Bit;
use App\Models\Bits\Type;
use App\Models\Folder;
use App\Models\File;
use App\Models\Share;
use App\Models\Teams\Team;
use App\Models\User;
use App\Sharing\Shareable;
use App\Services\IndexingService;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Mockery;

class IndexingTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $team;
    protected $parent_folder;
    protected $data;

    public function setUp() {
        parent::setUp();
    }

    public function itemProvider() {
        return [
            [
                'file',
                'sales.pdf',
                File::class,
            ],
            [
                'folder',
                'Test Folder',
                Folder::class,
            ],
            [
                'bit',
                'Test Bit',
                Bit::class,
            ],
        ];
    }

    public function initialize($type, $title) {
        $this->user = factory(User::class)->create();
        $this->team = factory(Team::class)->create([ 'user_id' => $this->user->id ]);
        $this->team->users()->attach($this->user->id);

        Shareable::$update_index = false;

        $this->parent_folder = factory(Folder::class)->create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'owner_id' => $this->user->id,
        ]);

        $this->data = [ 'team_id' => $this->team->id ];

        if ($type === 'file') {
            $this->data['folder_id'] = $this->parent_folder->id;
            $this->data['data'] = UploadedFile::fake()->create($title,40);
        } else if ($type === 'folder') {
            $this->data['title'] = $title;
        } else if ($type === 'bit') {
            $bit_type = factory(Type::class)->create(['public' => true]);
            $this->data['folder_id'] = $this->parent_folder->id;
            $this->data['title'] = $title;
            $this->data['type_id'] = $bit_type->id;
        }

        Shareable::$update_index = true;
    }

    /**
     * @dataProvider itemProvider
     * @param $type
     * @param $title
     */
    public function testCreateItem($type, $title) {
        $this->initialize($type, $title);
        Storage::fake('uploads');

        $this->asUser($this->user)->asTeam($this->team);

        $mock = Mockery::mock(IndexingService::class);
        $mock->shouldReceive('index')->with(Mockery::on(function($arg) use($type, $title) {
            return
                $arg->title === $title
                && $arg->shareable_type === $type
                && $arg->team_id === $this->team->id
                && $arg->user_id === $this->user->id
                && $arg->owner_id === $this->user->id
                && ($type === 'folder' ? $arg->folder_id === NULL : $arg->folder_id === $this->parent_folder->id);
        }))->once();
        $this->app->instance(IndexingService::class, $mock);

        $response = $this->post($type . 's', $this->data);

        $this->assertResponse($response);
    }

    /**
     * @dataProvider itemProvider
     * @param $type
     * @param $title
     * @param $model
     */
    public function testUpdateItem($type, $title, $model) {
        $this->initialize($type, $title);

        $this->data['user_id'] = $this->user->id;
        $this->data['owner_id'] = $this->user->id;
        if ($type === 'file') {
            unset($this->data['data']);
        }

        Shareable::$update_index = false;

        $shareable = factory($model)->create($this->data);

        Shareable::$update_index = true;

        $this->asUser($this->user)->asTeam($this->team);

        $mock = Mockery::mock(IndexingService::class);
        $mock->shouldReceive('index')->with(Mockery::on(function($arg) use($type) {
            return $arg->title === 'Updated Title';
        }))->once();
        $this->app->instance(IndexingService::class, $mock);

        $response = $this->put($type . 's/' . $shareable->id, [ 'title' => 'Updated Title', 'team_id' => $this->team->id ]);

        $this->assertResponse($response);
    }

    /**
     * @dataProvider itemProvider
     * @param $type
     * @param $title
     * @param $model
     */
    public function testShareItem($type, $title, $model) {
        $this->initialize($type, $title);

        $this->data['user_id'] = $this->user->id;
        $this->data['owner_id'] = $this->user->id;
        if ($type === 'file') {
            unset($this->data['data']);
        }

        Shareable::$update_index = false;

        $shareable = factory($model)->create($this->data);

        Shareable::$update_index = true;

        $data = [
            'team_id'        => $this->team->id,
            'shareable_type' => $type,
            'shareable_id'   => $shareable->id,
            'share'          => 1,
            'edit'           => 1
        ];

        Mail::fake();

        $user2 = factory(User::class)->create();
        $this->team->users()->attach([$user2->id]);

        $this->asUser($this->user)->asTeam($this->team);

        $mock = Mockery::mock(IndexingService::class);
        $mock->shouldReceive('index')->times(4);
        $this->app->instance(IndexingService::class, $mock);

        $response = $this->post('shares', array_merge($data, ['users' => [$user2->id]]));

        $this->assertResponse($response);
    }

    /**
     * @dataProvider itemProvider
     * @param $type
     * @param $title
     * @param $model
     */
    public function testUnshareItem($type, $title, $model) {
        $this->initialize($type, $title);

        $this->data['user_id'] = $this->user->id;
        $this->data['owner_id'] = $this->user->id;
        if ($type === 'file') {
            unset($this->data['data']);
        }

        Shareable::$update_index = false;

        $shareable = factory($model)->create($this->data);

        Shareable::$update_index = true;

        $user2 = factory(User::class)->create();
        $this->team->users()->attach([$user2->id]);

        $share = Share::create([
            'team_id'        => $this->team->id,
            'user_id'        => $user2->id,
            'edit'           => 1,
            'share'          => 0,
            'shareable_type' => $type,
            'shareable_id'   => $shareable->id,
            'created_by_id'  => $this->user->id
        ]);

        Mail::fake();

        $this->asUser($this->user)->asTeam($this->team);

        $mock = Mockery::mock(IndexingService::class);
        $mock->shouldReceive('index')->once();
        $mock->shouldReceive('remove')->once();
        $this->app->instance(IndexingService::class, $mock);

        $response = $this->delete('shares/' . $share->id);

        $this->assertResponse($response);
    }

    /**
     * @dataProvider itemProvider
     * @param $type
     * @param $title
     * @param $model
     */
    public function testDeleteItem($type, $title, $model) {
        $this->initialize($type, $title);

        $this->data['user_id'] = $this->user->id;
        $this->data['owner_id'] = $this->user->id;
        if ($type === 'file') {
            unset($this->data['data']);
        }

        Shareable::$update_index = false;

        $shareable = factory($model)->create($this->data);

        Shareable::$update_index = true;

        $this->asUser($this->user)->asTeam($this->team);

        $mock = Mockery::mock(IndexingService::class);
        $mock->shouldReceive('remove')->once();
        $this->app->instance(IndexingService::class, $mock);

        $response = $this->delete($type . 's/' . $shareable->id . '/trash');

        $this->assertResponse($response);
    }
}
