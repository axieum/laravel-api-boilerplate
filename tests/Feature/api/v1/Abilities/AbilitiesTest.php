<?php

namespace Tests\Feature\api\v1\Abilities;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Silber\Bouncer\Database\Ability;
use Tests\TestCase;

class AbilitiesTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var array|Collection|Ability new generated abilities
     */
    private $abilities;

    /**
     * @var int how many abilities to pre-generate
     */
    private $count = 16;

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

        /** @var User $user new user whom can read the ability */
        $user = factory('App\User')->create();
        $user->allow('read', $ability);

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
    public function cannot_retrieve_ability_without_permission()
    {
        /** @var User $user new user whom cannot read abilities */
        $user = factory('App\User')->create();

        self::actingAs($user)
            ->get("/api/v1/abilities/{$this->abilities->random()->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
