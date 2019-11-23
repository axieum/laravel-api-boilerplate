<?php

namespace Tests\Unit\app\Http\Resources\v1;

use App\Http\Resources\v1\User as UserResource;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var User user to be queried
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory('App\User')->create();
    }

    /** @test */
    public function can_see_relevant_fields()
    {
        /** @var User $active user whom is viewing passive user */
        $active = factory('App\User')->create();

        /** @noinspection PhpParamsInspection */
        self::actingAs($active)
            ->createTestResponse((new UserResource($this->user))->response())
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email_verified_at',
                    'updated_at',
                    'created_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $this->user->id,
                    'name' => $this->user->name
                ]
            ]);
    }

    /** @test */
    public function cannot_see_sensitive_fields()
    {
        /** @var User $active user whom is viewing passive user */
        $active = factory('App\User')->create();

        /** @noinspection PhpParamsInspection */
        self::actingAs($active)
            ->createTestResponse((new UserResource($this->user))->response())
            ->assertDontSee(json_encode($this->user->password))
            ->assertDontSee(json_encode($this->user->remember_token));
    }

    /** @test */
    public function can_see_email_on_new_user()
    {
        $this->user->wasRecentlyCreated = true;

        /** @noinspection PhpParamsInspection */
        self::createTestResponse((new UserResource($this->user))->response())
            ->assertJson(['data' => ['email' => $this->user->email]]);
    }

    /** @test */
    public function cannot_see_email_on_non_new_user()
    {
        /** @var User $active user whom is viewing passive user */
        $active = factory('App\User')->create();

        $this->user->wasRecentlyCreated = false;

        /** @noinspection PhpParamsInspection */
        self::actingAs($active)
            ->createTestResponse((new UserResource($this->user))->response())
            ->assertJsonMissing(['email' => $this->user->email]);
    }

    /** @test */
    public function can_see_own_email()
    {
        /** @var User $user */
        $user = factory('App\User')->create();
        $user->wasRecentlyCreated = false; // do not conflict with new user case

        /** @noinspection PhpParamsInspection */
        self::actingAs($user)
            ->createTestResponse((new UserResource($user))->response())
            ->assertJson(['data' => ['email' => $user->email]]);
    }

    /** @test */
    public function can_see_email_with_ability()
    {
        /** @var User $active user whom can read passive user's email */
        $active = factory('App\User')->create();

        $this->user->wasRecentlyCreated = false;
        $active->allow('read-email', $this->user);

        /** @noinspection PhpParamsInspection */
        self::actingAs($active)
            ->createTestResponse((new UserResource($this->user))->response())
            ->assertJson(['data' => ['email' => $this->user->email]]);
    }
}
