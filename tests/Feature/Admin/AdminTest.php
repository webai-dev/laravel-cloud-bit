<?php
namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use DatabaseTransactions;

    const HEADER = ['x-ybit-admin' => true];

    protected $user;

    public function setUp() {
        parent::setUp();

        $this->initialize();
    }

    public function initialize() {
        $this->user = factory(User::class)->states('superuser')->create();
    }
}