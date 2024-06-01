<?php

namespace Tests\Feature;

use App\Enums\Roles;
use App\Events\ItemShared as GenericItemShared;
use App\Models\Bits\Bit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Models\User;
use App\Models\Teams\Team;
use App\Models\Teams\TeamShareable;
use App\Models\Folder;
use App\Models\Share;

use App\Models\Lock;

use Illuminate\Support\Facades\Mail;
use App\Mail\ItemShared;

class SharesTest extends TestCase {
    use DatabaseTransactions;

    protected $user1;
    protected $user2;
    protected $team;
    protected $folder;

    public function setUp() {
        parent::setUp();

        $this->initialize();
    }

    public function initialize() {
        $this->user1 = factory(User::class)->create();
        $this->user2 = factory(User::class)->create();

        $this->team = factory(Team::class)->create(['user_id' => $this->user1->id]);
        $this->team->users()->attach([$this->user1->id, $this->user2->id]);
    }

    public function testCreate() {
        $folder = factory(Folder::class)->create(['user_id' => $this->user1->id, 'team_id' => $this->team->id, 'owner_id' => $this->user1->id]);

        $data = [
            'team_id'        => $this->team->id,
            'shareable_type' => 'folder',
            'shareable_id'   => $folder->id,
            'share'          => 1,
            'edit'           => 1
        ];

        Mail::fake();

        $this->asUser($this->user1)->asTeam($this->team)
            ->post('shares', array_merge($data, ['users' => [$this->user2->id]]))
            ->assertStatus(200)
            ->assertJson(['shares_count' => 1]);

        $data['user_id'] = $this->user2->id;
        $this->assertDatabaseHas('shares', $data);

        $this->assertDatabaseHas('folders', [
            'id'        => $folder->id,
            'is_shared' => true
        ]);

        Mail::assertSent(ItemShared::class, function ($mail) {
            return $mail->hasTo($this->user2->email);
        });
    }

    public function testCreateAlreadySharedDirectly() {
        /** @var Folder $folder */
        $folder = factory(Folder::class)->create(['user_id' => $this->user1->id, 'team_id' => $this->team->id, 'owner_id' => $this->user1->id]);

        $data = [
            'team_id'        => $this->team->id,
            'shareable_type' => 'folder',
            'shareable_id'   => $folder->id,
            'share'          => 1,
            'edit'           => 1
        ];

        Mail::fake();

        $folder->shareWith($this->user2, 'share');

        $this->asUser($this->user1)->asTeam($this->team)
            ->post('shares', array_merge($data, ['users' => [$this->user2->id]]))
            ->assertStatus(400);

        Mail::assertNotSent(ItemShared::class);
    }

    public function testCreateSharedRecursivelyValid() {
        /** @var Folder $containing_folder */
        $containing_folder = factory(Folder::class)->create([
            'user_id' => $this->user1->id,
            'team_id' => $this->team->id,
            'owner_id' => $this->user1->id,
        ]);

        $folder = factory(Folder::class)->create([
            'user_id'           => $this->user1->id,
            'team_id'           => $this->team->id,
            'folder_id'         => $containing_folder->id,
            'owner_id'          => $this->user1->id,
            'has_shared_parent' => true
        ]);

        $containing_folder->shareWith($this->user2, 'edit');

        $data = [
            'team_id'        => $this->team->id,
            'shareable_type' => 'folder',
            'shareable_id'   => $folder->id,
            'share'          => 1,
            'edit'           => 1,
            'users'          => [$this->user2->id]
        ];

        Mail::fake();

        $this->asUser($this->user1)->asTeam($this->team)
            ->post('shares', $data)
            ->assertStatus(200);

        Mail::assertSent(ItemShared::class);
    }

    public function testCreateSharedRecursivelyWithMorePermissions() {
        /** @var Folder $containing_folder */
        $containing_folder = factory(Folder::class)->create([
            'user_id' => $this->user1->id,
            'team_id' => $this->team->id,
            'owner_id' => $this->user1->id,
        ]);

        $folder = factory(Folder::class)->create([
            'user_id'   => $this->user1->id,
            'team_id'   => $this->team->id,
            'folder_id' => $containing_folder->id,
            'owner_id' => $this->user1->id,
        ]);

        $containing_folder->shareWith($this->user2, 'edit');

        $data = [
            'team_id'        => $this->team->id,
            'shareable_type' => 'folder',
            'shareable_id'   => $folder->id,
            'share'          => 0,
            'edit'           => 0,
            'users'          => [$this->user2->id]
        ];

        Mail::fake();

        $this->asUser($this->user1)->asTeam($this->team)
            ->post('shares', $data)
            ->assertStatus(400);

        Mail::assertNotSent(ItemShared::class);
    }

    public function testCreateShareItemToOwner() {
        $folder = factory(Folder::class)->create(['user_id' => $this->user1->id, 'team_id' => $this->team->id, 'owner_id' => $this->user1->id]);

        $data = [
            'team_id'        => $this->team->id,
            'shareable_type' => 'folder',
            'shareable_id'   => $folder->id,
            'share'          => 1,
            'edit'           => 1
        ];

        $this->asUser($this->user1)->asTeam($this->team)
            ->post('shares', array_merge($data, ['users' => [$this->user2->id]]));

        $this->asUser($this->user2)->asTeam($this->team)
            ->post('shares', array_merge($data, ['users' => [$this->user1->id]]))
            ->assertstatus(400);
    }

    public function testCreateForTeam() {
        /** @var $users Collection */
        list($team, $users) = $this->getTeamWithUsers(3);
        /** @var User $guest */
        $guest = $users->last();
        $guest->setRoleInTeam(Roles::GUEST, $team->id);

        $item = factory(Folder::class)->create([
            'user_id' => $users->first()->id,
            'team_id' => $team->id,
            'owner_id' => $users->first()->id,
        ]);

        $data = [
            'team_id'        => $team->id,
            'shareable_type' => 'folder',
            'shareable_id'   => $item->id,
            'share'          => true,
            'edit'           => true
        ];

        Mail::fake();

        $this->asUser($users->first())->asTeam($team)
            ->post('shares', $data)
            ->assertStatus(200);

        $this->assertEquals($item->shares()->count(), 1);
        $this->assertDatabaseHas('team_shareables', $data);

        foreach ($users as $user) {
            if ($user->id == $users->first()->id) {
                continue;
            }
            // Shares should not be created for the guests
            if ($user->id == $guest->id) {
                $this->assertDatabaseMissing('shares', array_merge($data, [
                    'user_id' => $user->id
                ]));

                Mail::assertNotSent(ItemShared::class, function ($mail) use ($user) {
                    return $mail->hasTo($user->email);
                });
                continue;
            }

            $this->assertDatabaseHas('shares', array_merge($data, [
                'user_id' => $user->id
            ]));

            Mail::assertSent(ItemShared::class, function ($mail) use ($user) {
                return $mail->hasTo($user->email);
            });
        }
    }

    public function testPermissions() {
        $folder = factory(Folder::class)->create(['user_id' => $this->user1->id, 'team_id' => $this->team->id, 'owner_id' => $this->user1->id]);
        $folder->shareWith($this->user2, 'edit');

        $this->asUser($this->user1)->asTeam($this->team)
            ->get('shares/permissions?' . http_build_query([
                    'shareable_id'   => $folder->id,
                    'shareable_type' => 'folder'
                ]))
            ->assertStatus(200)
            ->assertJson([
                'shares' => [['user_id' => $this->user2->id, 'share' => 0, 'edit' => 1]],
                'owner'  => ['id' => $this->user1->id]
            ]);
    }

    public function testUpdate() {
        $folder = factory(Folder::class)->create(['user_id' => $this->user1->id, 'team_id' => $this->team->id, 'owner_id' => $this->user1->id]);

        $share = Share::create([
            'team_id'        => $this->team->id,
            'user_id'        => $this->user2->id,
            'edit'           => 1,
            'share'          => 0,
            'shareable_type' => 'folder',
            'shareable_id'   => $folder->id,
            'created_by_id'  => $this->user1->id
        ]);

        $this->asUser($this->user1)->asTeam($this->team)
            ->put('shares/' . $share->id, ['share' => 1])
            ->assertStatus(200);

        $this->assertDatabaseHas('shares', ['id' => $share->id, 'share' => 1, 'edit' => 1]);
    }

    public function testDelete() {
        $folder = factory(Folder::class)->create(['user_id' => $this->user1->id, 'team_id' => $this->team->id, 'owner_id' => $this->user1->id]);

        $share = Share::create([
            'team_id'        => $this->team->id,
            'user_id'        => $this->user2->id,
            'edit'           => 1,
            'share'          => 0,
            'shareable_type' => 'folder',
            'shareable_id'   => $folder->id,
            'created_by_id'  => $this->user1->id
        ]);

        $subfolder = factory(Folder::class)->create([
            'user_id'   => $this->user2->id,
            'team_id'   => $this->team->id,
            'folder_id' => $folder->id
        ]);

        $bit = factory(Bit::class)->create([
            'user_id'   => $this->user2->id,
            'team_id'   => $this->team->id,
            'folder_id' => $folder->id
        ]);

        $lock_data = [
            'lockable_id'   => $folder->id,
            'lockable_type' => 'folder',
            'user_id'       => $this->user2->id,
            'team_id'       => $this->team->id
        ];
        Lock::create($lock_data);

        $bit_lock_data = [
            'lockable_id'   => $bit->id,
            'lockable_type' => 'bit',
            'user_id'       => $this->user2->id,
            'team_id'       => $this->team->id
        ];
        Lock::create($bit_lock_data);

        $response = $this->asUser($this->user1)->asTeam($this->team)
            ->delete('shares/' . $share->id);

        $this->assertResponse($response);
        //Check share deleted
        $this->assertDatabaseMissing('shares', ['id' => $share->id]);

        //Check locks removed
        $this->assertDatabaseMissing('locks', $lock_data);
        $this->assertDatabaseMissing('locks', $bit_lock_data);

        //Check descendants transferred to owner
        $this->assertDatabaseHas('folders', ['id' => $subfolder->id, 'user_id' => $this->user1->id]);
    }

    public function testDeleteForTeam() {
        list($team, $users) = $this->getTeamWithUsers(3);

        $item = factory(Folder::class)->create([
            'user_id' => $users->first()->id,
            'team_id' => $team->id,
            'owner_id' => $users->first()->id,
        ]);

        foreach ($users as $user) {
            if ($user->id == $users->first()->id) {
                continue;
            }
            $item->shareWith($user);
        }

        $data = [
            'team_id'        => $team->id,
            'shareable_type' => $item->getType(),
            'shareable_id'   => $item->id,
            'created_by_id'  => $users->first()->id
        ];

        TeamShareable::create($data);

        $this->asUser($users->first())->asTeam($team)
            ->delete('shares/team', $data)
            ->assertStatus(200);

        $this->assertEquals(0, $item->shares()->count());
        $this->assertDatabaseMissing('team_shareables', $data);
    }

    public function testRename() {
        list($team, $user1) = $this->getTeam();
        $user2 = factory(User::class)->create();
        $team->users()->attach($user2->id);

        $folder = factory(Folder::class)->create(['user_id' => $user1->id, 'team_id' => $team->id, 'owner_id' => $user1->id]);
        $folder->shareWith($user2);

        $this->asUser($user2)->asTeam($team)
            ->put('folders/' . $folder->id, [
                'title' => $folder->title . "_renamed"
            ])
            ->assertStatus(200)
            ->assertJson([
                'title' => $folder->title . '_renamed'
            ]);

        $this->assertDatabaseHas('shares', [
            'shareable_id'   => $folder->id,
            'shareable_type' => 'folder',
            'rename'         => $folder->title . '_renamed'
        ]);

        $this->assertDatabaseHas('folders', [
            'id'    => $folder->id,
            'title' => $folder->title
        ]);

        $this->get('shares?' . http_build_query(['team_id' => $team->id]))
            ->assertStatus(200)
            ->assertJson([
                [
                    'title' => $folder->title . '_renamed'
                ]
            ]);
    }

    public function testEventDispatched() {
        $folder = factory(Folder::class)->create(['user_id' => $this->user1->id, 'team_id' => $this->team->id, 'owner_id' => $this->user1->id]);

        $data = [
            'team_id'        => $this->team->id,
            'shareable_type' => 'folder',
            'shareable_id'   => $folder->id,
            'share'          => 1,
            'edit'           => 1
        ];

        Event::fake();

        $this->asUser($this->user1)
            ->asTeam($this->team)
            ->post('shares', array_merge($data, ['users' => [$this->user2->id]]))
            ->assertStatus(200)
            ->assertJson(['shares_count' => 1]);

        $share = new Share();
        $share->user_id = $this->user2->id;
        $share->created_by_id = $this->user1->id;
        $share->team_id = $this->team->id;
        $share->shareable_type = "folder";
        $share->shareable_id = $folder->id;

        Event::assertDispatched(GenericItemShared::class, function ($e) use ($share) {
            return equalTo($e->share, $share);
        });
    }
}
