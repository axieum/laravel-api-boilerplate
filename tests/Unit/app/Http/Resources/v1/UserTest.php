<?php

namespace Tests\Unit\app\Http\Resources\v1;

use App\Http\Resources\v1\User as UserResource;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function can_see_relevant_fields()
    {
        /** @var User $user */
        $user = factory('App\User')->create();

        /** @noinspection PhpParamsInspection */
        self::createTestResponse((new UserResource($user))->response())
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'updated_at',
                    'created_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);
    }

    /** @test */
    public function cannot_see_sensitive_fields()
    {
        /** @var User $user */
        $user = factory('App\User')->create();

        /** @noinspection PhpParamsInspection */
        self::createTestResponse((new UserResource($user))->response())
            ->assertDontSee(json_encode($user->password))
            ->assertDontSee(json_encode($user->remember_token));
    }

    /** @test */
    public function can_see_email_on_new_user()
    {
        /** @var User $user */
        $user = factory('App\User')->create();
//        $user->wasRecentlyCreated = true;

        /** @noinspection PhpParamsInspection */
        self::createTestResponse((new UserResource($user))->response())
            ->assertJson(['data' => ['email' => $user->email]]);
    }

    /** @test */
    public function cannot_see_email_on_non_new_user()
    {
        /** @var User $user */
        $user = factory('App\User')->create();
        $user->wasRecentlyCreated = false;

        /** @noinspection PhpParamsInspection */
        self::createTestResponse((new UserResource($user))->response())
            ->assertJsonMissing(['email' => $user->email]);
    }

    /** @test */
    public function can_see_own_email()
    {
        /** @var User $user */
        $user = factory('App\User')->create();
        $user->wasRecentlyCreated = false;

        self::actingAs($user);
        /** @noinspection PhpParamsInspection */
        self::createTestResponse((new UserResource($user))->response())
            ->assertJson(['data' => ['email' => $user->email]]);
    }

    /** @test */
    public function can_see_email_with_ability()
    {
        /** @var User $passive user whom is being viewed */
        $passive = factory('App\User')->create();
        $passive->wasRecentlyCreated = false;

        /** @var User $active user whom is viewing passive user */
        $active = factory('App\User')->create();
        $active->allow('view.email', $passive);

        self::actingAs($active);
        /** @noinspection PhpParamsInspection */
        self::createTestResponse((new UserResource($passive))->response())
            ->assertJson(['data' => ['email' => $passive->email]]);
    }
}
