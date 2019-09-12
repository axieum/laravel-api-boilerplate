<?php

/** @var Factory $factory */

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Str;
use Silber\Bouncer\Database\Ability;

$factory->define(Ability::class, function (Faker $faker) {
    return [
        'name' => Str::slug($abilityTitle = $faker->unique()->sentence(3)),
        'title' => $abilityTitle,
    ];
});
