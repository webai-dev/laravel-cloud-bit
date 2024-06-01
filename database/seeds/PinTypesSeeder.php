<?php

use Illuminate\Database\Seeder;
use App\Models\Pins\Type;

class PinTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types  = [
            ['name' => 'Photo', 'label'=>'photo'],
            ['name' => 'Text Note','label'=>'text'],
            ['name' => 'Video','label'=>'video'],
            ['name' => 'Google Map','label'=>'map'],
            ['name' => 'Reminder','label'=>'reminder'],
            ['name' => 'Announcement','label'=>'announcement'],
        ];
        
        Type::insert($types);
    }
}
