<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    return [
        'name'         => $faker->name,
        'email'        => $faker->unique()->safeEmail,
        'phone'        => $faker->unique()->phoneNumber,
        'photo'        => $faker->imageUrl(50, 50),
        'apparatus_id' => $faker->shuffle("test_apparatus_id")
    ];
});

$factory->state(App\Models\User::class, 'superuser', function() {
    return [
        'superuser' => true,
    ];
});

$factory->define(\App\Models\Role::class, function (\Faker\Generator $faker) {
    return [
        'name'  => $faker->word,
        'label' => $faker->word
    ];
});

$factory->define(App\Models\Teams\Team::class, function (Faker\Generator $faker) {
    return [
        'name'      => $faker->name,
        'photo'     => $faker->imageUrl(50, 50),
        'subdomain' => $faker->unique()->word,
    ];
});

$factory->define(App\Models\Teams\Integration::class, function (Faker\Generator $faker) {
    return [
        'name'    => $faker->word,
        'key'     => $faker->word,
        'secret'  => encrypt($faker->word),
        'team_id' => function () {
            return factory(\App\Models\Teams\Team::class)->create()->id;
        }
    ];
});

$factory->define(App\Models\File::class, function (Faker\Generator $faker) {
    return [
        'title'         => $faker->word,
        'path'          => $faker->word,
        'extension'     => $faker->fileExtension,
        'size'          => $faker->randomNumber(),
        'mime_type'     => $faker->mimeType,
        's3_version_id' => time() . "_test"
    ];
});

$factory->define(App\Models\Folder::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->word
    ];
});

$factory->define(App\Models\Bits\Type::class, function (Faker\Generator $faker) {
    return [
        'name'     => $faker->word,
        'base_url' => $faker->url,
        'jwt_key'  => $faker->word,
        'height'   => 1,
        'width'    => 10
    ];
});

$factory->define(App\Models\Bits\Bit::class, function (Faker\Generator $faker) {
    return [
        'title'   => $faker->word,
        'type_id' => function () {
            return factory(App\Models\Bits\Type::class)->create()->id;
        }
    ];
});

$factory->define(App\Models\Share::class, function (Faker\Generator $faker) {
    return [];
});