<?php

namespace Tests\Feature\Bits;

use App\Models\Bits\Bit;
use App\Models\Bits\BitFile;
use App\Notifications\BitNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommonCallsTest extends TestCase {
    use DatabaseTransactions;

    public function testTeammates() {
        list($team, $users) = $this->getTeamWithUsers(3);
        $owner = $users->first();

        $bit = factory(Bit::class)->create([
            'user_id' => $owner->id,
            'team_id' => $team->id
        ]);

        $users = $users->map(function ($user) {
            return [
                'id'    => $user->id,
                'name'  => $user->name,
                'photo' => $user->photo
            ];
        })->toArray();

        $this->asBit($bit, $owner)
            ->get('integration/' . $bit->type_id . '/teammates')
            ->assertStatus(200)
            ->assertExactJson($users);
    }

    public function testMetadata() {
        list($team, $user) = $this->getTeam();

        $bit = factory(Bit::class)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);
        $metadata = "Test String";


        $this->asBit($bit, $user)
            ->put('integration/' . $bit->type_id . '/metadata', [
                'metadata' => $metadata
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('bits', [
            'id'       => $bit->id,
            'metadata' => $metadata
        ]);
    }

    public function testCurrentUser() {
        list($team, $user) = $this->getTeam();

        $bit = factory(Bit::class)->create([
            'user_id' => $user->id,
            'team_id' => $team->id
        ]);


        $this->asBit($bit, $user)
            ->get('integration/' . $bit->type_id . '/user')
            ->assertStatus(200)
            ->assertExactJson([
                'id'    => $user->id,
                'name'  => $user->name,
                'photo' => $user->photo
            ]);
    }

    public function testNotify() {
        list($team, $users) = $this->getTeamWithUsers(2);
        $owner = $users->first();
        $user = $users->get(1);

        $bit = factory(Bit::class)->create([
            'user_id' => $owner->id,
            'team_id' => $team->id
        ]);

        Notification::fake();
        $bit->shareWith($user, 'view');

        $this->asBit($bit, $owner)
            ->post('integration/' . $bit->type_id . '/notify', [
                'content' => 'test'
            ])
            ->assertStatus(200);

        Notification::assertSentTo($user, BitNotification::class);
    }


    public function testLog() {
        list($team, $users) = $this->getTeamWithUsers(2);
        $owner = $users->first();

        $bit = factory(Bit::class)->create([
            'user_id' => $owner->id,
            'team_id' => $team->id
        ]);

        $response = $this->asBit($bit, $owner)
            ->post('integration/' . $bit->type_id . '/log', [
                'major' => 1,
                'action' => 'created',
            ]);

        $response->assertStatus(200);
    }
}
