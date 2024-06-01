<?php

use Illuminate\Database\Seeder;

use App\Models\Bits\Type;
use Illuminate\Support\Str;

class BitTypesSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {

        Type::create([
            'name'     => 'Open Sesame',
            'public'   => 1,
            'jwt_key'  => Str::random(32),
            'base_url' => '',
            'width'    => 2,
            'height'   => 2
        ]);

        Type::create([
            'name'     => 'Spreadit',
            'public'   => 1,
            'jwt_key'  => Str::random(32),
            'base_url' => '',
            'width'    => 6,
            'height'   => 5
        ]);

        Type::create([
            'name'     => 'Tefteri',
            'public'   => 1,
            'jwt_key'  => Str::random(32),
            'base_url' => '',
            'width'    => 2,
            'height'   => 5
        ]);

        Type::create([
            'name'     => 'Bookmark',
            'public'   => 1,
            'jwt_key'  => Str::random(32),
            'base_url' => '',
            'width'    => 1,
            'height'   => 2
        ]);
    }
}
