<?php

namespace Tests\Feature\api\v1;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Role;
use Tests\TestCase;

class AbilitiesTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * @var int how many abilities to pre-generate
     */
    private $count = 16;

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
        /** @var User $user new user whom can index all abilities */
        $user = factory('App\User')->create();
        $user->allow('index', Ability::class);

        self::actingAs($user)
            ->get('/api/v1/abilities')
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
            ->assertJson(['meta' => ['total' => Ability::query()->count()]]);
    }

    /** @test */
    public function cannot_index_abilities_without_permission()
    {
        /** @var User $user new user whom cannot index abilities */
        $user = factory('App\User')->create();

        self::actingAs($user)
            ->get('/api/v1/abilities')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function can_retrieve_specific_ability()
    {
        /** @var Ability $ability */
        $ability = $this->abilities->random();

        /** @var User $user new user whom can view the ability */
        $user = factory('App\User')->create();
        $user->allow('view', $ability);

        self::actingAs($user)
            ->get("/api/v1/abilities/{$ability->id}")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'id',
                'name',
                'title',
                'only_owned',
                'forbidden',
                'options',
                'scope',
                'updated_at',
                'created_at'
            ])
            ->assertJson([
                'name' => $ability->name,
                'title' => $ability->title,
                'forbidden' => $ability->forbidden
            ]);
    }

    /** @test */
    public function can_retrieve_an_abilities_roles()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var Ability $ability */
        $ability = $this->abilities->random();

        // Give the new role access to the ability
        $role->allow($ability);

        /** @var User $user new user whom can view and index abilities' roles */
        $user = factory('App\User')->create();
        $user->allow('view', $ability);
        $user->allow('index.role', $ability);

        self::actingAs($user)
            ->get("/api/v1/abilities/{$ability->id}/roles")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'title',
                    'level',
                    'scope',
                    'updated_at',
                    'created_at'
                ]],
                'links' => [],
                'meta' => []
            ])
            ->assertJson([
                'data' => [
                    [
                        'name' => $role->name,
                        'title' => $role->title
                    ]
                ]
            ]);
    }

    /** @test */
    public function can_retrieve_an_abilities_users()
    {
        /** @var Ability $ability */
        $ability = $this->abilities->random();

        /** @var User $user new user whom can view and index an abilities' users */
        $user = factory('App\User')->create();
        $user->allow('view', $ability);
        $user->allow('index.user', $ability);

        // Give the new user access to the ability
        $user->allow($ability);

        self::actingAs($user)
            ->get("/api/v1/abilities/{$ability->id}/users")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name'
                ]],
                'links' => [],
                'meta' => []
            ])
            ->assertJson([
                'data' => [
                    [
                        'name' => $user->name
                    ]
                ]
            ]);
    }
}
