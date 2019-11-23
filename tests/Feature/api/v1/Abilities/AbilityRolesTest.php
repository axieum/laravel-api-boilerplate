<?php

namespace Tests\Feature\api\v1\Abilities;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Role;
use Tests\TestCase;

class AbilityRolesTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function can_retrieve_an_abilities_roles()
    {
        /** @var Ability $ability */
        $ability = factory(Ability::class)->create();

        /** @var Collection|Role $roles list of roles that inherit the ability */
        $roles = factory(Role::class, 5)->create();
        foreach ($roles as $role)
            $role->allow($ability);

        /** @var User $user user whom can view and index the abilities' roles */
        $user = factory('App\User')->create();
        $user->allow('view', $ability);
        $user->allow('index-roles', $ability);

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
            ->assertJsonCount($roles->count(), 'data');
    }

    /** @test */
    public function cannot_retrieve_an_abilities_roles_without_permission()
    {
        /** @var User $user new user whom cannot index abilities' roles */
        $user = factory('App\User')->create();

        /** @var Ability $ability new ability */
        $ability = factory(Ability::class)->create();

        self::actingAs($user)
            ->get("/api/v1/abilities/{$ability->id}/roles")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
