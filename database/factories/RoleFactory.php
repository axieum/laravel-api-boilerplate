<?php

/** @var Factory $factory */

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Str;
use Silber\Bouncer\Database\Role;

$factory->define(Role::class, function (Faker $faker) {
    return [
        'name' => Str::slug($title = $faker->unique()->jobTitle),
        'title' => $title,
    ];
});
