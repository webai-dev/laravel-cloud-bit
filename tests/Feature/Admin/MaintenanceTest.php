<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\DatabaseTransactions;


class MaintenanceTest extends AdminTest
{
    use DatabaseTransactions;

    public function testCreate() {
        $response = $this->asUser($this->user)
             ->post('maintenances', [
                 'type'   => "Sample Type",
                 'description' => 'Simple Message',
                 'is_active' => false,
               ], self::HEADER);

        $response->assertStatus(200);
        $this->assertDatabaseHas('maintenances', ['id' => $response->baseResponse->original->id]);
    }

    public function testUpdate() {
        $response = $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type",
                'description' => 'Simple Message',
                'is_active' => false,
            ], self::HEADER);

        $response = $this->asUser($this->user)
            ->put('maintenances/' . $response->baseResponse->original->id, [
                'description' => 'Updated Message',
            ], self::HEADER);

        $response->assertStatus(200);
        $this->assertDatabaseHas('maintenances', ['description' => $response->baseResponse->original->description]);
    }

    public function testView() {
        $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type",
                'description' => 'Message 1',
                'is_active' => false,
            ], self::HEADER);

        $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type",
                'description' => 'Message 2',
                'is_active' => false,
            ], self::HEADER);

        $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type",
                'description' => 'Message 3',
                'is_active' => false,
            ], self::HEADER);

        $response = $this->asUser($this->user)
            ->fetch('maintenances', [], self::HEADER);

        $response->assertStatus(200);
        $this->assertEquals(count($response->baseResponse->original), 3);
    }

    public function testGetActiveRecord() {
        $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type",
                'description' => 'Message 1',
                'is_active' => false,
            ], self::HEADER);

        $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type",
                'description' => 'Message 2',
                'is_active' => true,
            ], self::HEADER);

        $response = $this->asUser($this->user)
            ->fetch('maintenances/active', [], self::HEADER);

        $response->assertStatus(200);
        $this->assertEquals($response->baseResponse->original->is_active, true);
    }

    public function testActiveRecordUpdate() {
        $response = $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type",
                'description' => 'Message 1',
                'is_active' => true,
            ], self::HEADER);

        $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type",
                'description' => 'Message 2',
                'is_active' => true,
            ], self::HEADER);

        $this->assertDatabaseHas('maintenances', ['description' => 'Message 2', 'is_active' => true]);
        $this->assertDatabaseHas('maintenances', ['description' => 'Message 1', 'is_active' => false]);

        $this->asUser($this->user)
            ->put('maintenances/' . $response->baseResponse->original->id, [
                'is_active' => true,
            ], self::HEADER);

        $this->assertDatabaseHas('maintenances', ['description' => 'Message 2', 'is_active' => false]);
        $this->assertDatabaseHas('maintenances', ['description' => 'Message 1', 'is_active' => true]);
    }

    public function testPaginationFilter() {
        $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type1",
                'description' => 'Message 1',
                'is_active' => false,
            ], self::HEADER);

        $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type2",
                'description' => 'Message 2',
                'is_active' => false,
            ], self::HEADER);

        $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type3",
                'description' => 'Message 3',
                'is_active' => true,
            ], self::HEADER);

        $response = $this->asUser($this->user)
            ->fetch('maintenances', ['page_size' => 2], self::HEADER);
        $this->assertEquals(count($response->baseResponse->original), 2);

        $response = $this->asUser($this->user)
            ->fetch('maintenances', ['page_size' => 3, 'page' => 2], self::HEADER);
        $this->assertEquals(count($response->baseResponse->original), 0);

        $response = $this->asUser($this->user)
            ->fetch('maintenances', ['is_active' => false], self::HEADER);
        $this->assertEquals(count($response->baseResponse->original), 2);

        $response = $this->asUser($this->user)
            ->fetch('maintenances', ['search' => 'Message 3'], self::HEADER);
        $this->assertEquals(count($response->baseResponse->original), 1);
    }

    public function testSorting() {
        $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type1",
                'description' => 'Message 1',
                'is_active' => false,
            ], self::HEADER);

        $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type2",
                'description' => 'Message 2',
                'is_active' => false,
            ], self::HEADER);

        $this->asUser($this->user)
            ->post('maintenances', [
                'type'   => "Sample Type3",
                'description' => 'Message 3',
                'is_active' => true,
            ], self::HEADER);

        $response = $this->asUser($this->user)
            ->fetch('maintenances', [], self::HEADER);
        $this->assertEquals($response->baseResponse->original[0]->description, 'Message 1');

        $response = $this->asUser($this->user)
            ->fetch('maintenances', ['order' => 'desc'], self::HEADER);
        $this->assertEquals($response->baseResponse->original[0]->description, 'Message 3');

        $response = $this->asUser($this->user)
            ->fetch('maintenances', ['order_by' => 'type'], self::HEADER);
        $this->assertEquals($response->baseResponse->original[0]->type, 'Sample Type1');
    }
}