<?php

namespace Tests\Unit\app;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /** @test */
    public function can_scope_verified_users()
    {
        /** @var Collection|User $users collection of randomly verified users */
        $users = factory('App\User', 50)->create([
            'email_verified_at' => function () {
                return $this->faker->optional(0.5)->dateTime;
            }
        ]);

        $count_unverified = $users->where('email_verified_at', null)->count();
        $count_verified = $users->count() - $count_unverified;

        self::assertEquals($count_verified, User::verified(true)->count());
        self::assertEquals($count_unverified, User::verified(false)->count());
    }
}
