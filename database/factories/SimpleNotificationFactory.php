<?php

/** @var Factory $factory */

use App\Notifications\v1\SimpleNotification;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

$factory->define(SimpleNotification::class, function (Faker $faker) {
    return [
        new SimpleNotification(
            $faker->sentence(3), // title
            $faker->realText() // body
        )
    ];
});
