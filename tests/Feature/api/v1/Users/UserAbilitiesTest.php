<?php

namespace Tests\Feature\api\v1\Users;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Ability;
use Tests\TestCase;

class UserAbilitiesTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * @var int how many abilities to pre-generate
     */
    private $count = 5;

    /**
     * @var array|Collection|Ability new generated abilities
     */
    private $abilities;

    protected function setUp(): void
    {
        parent::setUp();

        // Pre-generate some abilities
        $this->abilities = factory(Ability::class, $this->count)->create();
    }

    /** @test */
    public function can_index_abilities()
    {
        /** @var User $passive user whom has abilities */
        /** @var User $active user whom can index any users' abilities */
        [$passive, $active] = factory('App\User', 2)->create();

        $active->allow('index-abilities', User::class);
        $passive->allow($this->abilities);

        // Request passive user's abilities
        self::actingAs($active)
            ->get("/api/v1/users/{$passive->id}/abilities")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'title',
                    'only_owned',
                    'forbidden',
                    'updated_at',
                    'created_at'
                ]],
                'links' => [],
                'meta' => []
            ])
            ->assertJsonCount($this->abilities->count(), 'data');
    }

    /** @test */
    public function can_index_forbidden_abilities()
    {
        /** @var User $passive user whom has abilities */
        /** @var User $active user whom can index any users' abilities */
        [$passive, $active] = factory('App\User', 2)->create();
        $active->allow('index-abilities', User::class);

        /** @var Collection|Ability $forbid random abilities to be forbid */
        /** @var Collection|Ability $allow random abilities to be allowed */
        [$forbid, $allow] = $this->abilities->shuffle()->split(2);
        $passive->forbid($forbid);
        $passive->allow($allow);

        // Request passive user's forbidden abilities
        self::actingAs($active)
            ->get("/api/v1/users/{$passive->id}/abilities?filter[forbidden]=true")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'title',
                    'only_owned',
                    'forbidden',
                    'updated_at',
                    'created_at'
                ]],
                'links' => [],
                'meta' => []
            ])
            ->assertJson(['data' => [['forbidden' => true]]])
            ->assertJsonCount($forbid->count(), 'data');
    }

    /** @test */
    public function can_index_own_abilities()
    {
        /** @var User $user user whom has abilities */
        $user = factory('App\User')->create();
        $user->allow($this->abilities->random(3));

        // Request passive user's abilities
        self::actingAs($user)
            ->get("/api/v1/users/{$user->id}/abilities")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'title',
                    'only_owned',
                    'forbidden',
                    'updated_at',
                    'created_at'
                ]],
                'links' => [],
                'meta' => []
            ])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function cannot_index_abilities_without_permission()
    {
        /** @var User $passive user whom has abilities */
        /** @var User $active user whom cannot index any users' abilities */
        [$passive, $active] = factory('App\User', 2)->create();

        self::actingAs($active)
            ->get("/api/v1/users/{$passive->id}/abilities")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function can_allow_an_ability()
    {
        /** @var Ability $ability ability to be allowed */
        $ability = $this->abilities->random();

        /** @var User $user user whom can allow a specific ability for themselves */
        $user = factory('App\User')->create();
        $user->allow('allow', $ability); // can allow the specific ability
        $user->allow('allow', $user); // can allow for them

        // Ensure the user does not inherit the ability before test
        self::assertTrue($user->cannot($ability->name));

        self::actingAs($user)
            ->put("/api/v1/users/{$user->id}/abilities/{$ability->id}")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('users.allowed')]);

        // Ensure the user inherits the ability post test
        self::assertTrue($user->can($ability->name));
    }

    /** @test */
    public function can_forbid_an_ability()
    {
        /** @var Ability $ability ability to be forbid */
        $ability = $this->abilities->random();

        /** @var User $passive user whom is being forbid common ability */
        /** @var User $active user whom can forbid a specific ability from any user */
        [$passive, $active] = factory('App\User', 2)->create();

        Bouncer::allowEveryone()->to($ability); // allow everyone this ability
        $active->allow('allow', $ability); // can allow/forbid the specific ability
        $active->allow('allow', User::class); // can allow/forbid any user

        // Ensure the user inherits the ability before test (everyone has this ability)
        self::assertTrue($passive->can($ability->name));

        self::actingAs($active)
            ->put("/api/v1/users/{$passive->id}/abilities/{$ability->id}", ['forbid' => true])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('users.forbid')]);

        // Ensure the user cannot use the ability post test (despite everyone has it)
        self::assertTrue($passive->cannot($ability->name));
    }

    /** @test */
    public function cannot_allow_an_ability_without_permission()
    {
        /** @var Ability $ability ability to be allowed */
        $ability = $this->abilities->random();

        /** @var User $user user whom cannot allow a specific ability for themselves */
        $user = factory('App\User')->create();

        // Ensure the user does not inherit the ability before test
        self::assertTrue($user->cannot($ability->name));

        self::actingAs($user)
            ->put("/api/v1/users/{$user->id}/abilities/{$ability->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Ensure the user does not inherit the ability still
        self::assertTrue($user->cannot($ability->name));
    }

    /** @test */
    public function can_disallow_an_ability()
    {
        /** @var Ability $ability ability to be disallowed */
        $ability = $this->abilities->random();

        /** @var User $user user whom can disallow a specific ability from themselves */
        $user = factory('App\User')->create();
        $user->allow('disallow', $ability); // can disallow the specific ability
        $user->allow('disallow', $user); // can disallow from them
        $user->allow($ability);

        // Ensure the user inherits the ability beforehand
        self::assertTrue($user->can($ability->name));

        self::actingAs($user)
            ->delete("/api/v1/users/{$user->id}/abilities/{$ability->id}")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('users.disallowed')]);

        // Ensure the user does not inherit the ability
        self::assertTrue($user->cannot($ability->name));
    }

    /** @test */
    public function cannot_disallow_an_ability_without_permission()
    {
        /** @var Ability $ability ability to be allowed */
        $ability = $this->abilities->random();

        /** @var User $user user whom cannot allow a specific ability for themselves */
        $user = factory('App\User')->create();
        $user->allow($ability);

        // Ensure the user inherits the ability before test
        self::assertTrue($user->can($ability->name));

        self::actingAs($user)
            ->delete("/api/v1/users/{$user->id}/abilities/{$ability->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Ensure the user still inherits the ability
        self::assertTrue($user->can($ability->name));
    }
}
