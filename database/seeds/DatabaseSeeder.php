<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PinTypesSeeder::class);
        $this->call(BitTypesSeeder::class);
        $this->call(RolesSeeder::class);
    }
}
