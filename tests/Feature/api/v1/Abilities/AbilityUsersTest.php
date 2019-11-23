<?php

namespace Tests\Feature\api\v1\Abilities;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Silber\Bouncer\Database\Ability;
use Tests\TestCase;

class AbilityUsersTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function can_retrieve_an_abilities_users()
    {
        /** @var Ability $ability */
        $ability = factory(Ability::class)->create();

        /** @var User $active user whom can view and index the abilities' users */
        $active = factory('App\User')->create();
        $active->allow('view', $ability);
        $active->allow('index-users', $ability);

        /** @var Collection|User $users users whom inherit the ability */
        $users = factory('App\User', 10)->create();
        foreach ($users as $user)
            $user->allow($ability);

        self::actingAs($active)
            ->get("/api/v1/abilities/{$ability->id}/users")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name'
                ]],
                'links' => [],
                'meta' => []
            ])
            ->assertJsonCount($users->count(), 'data');
    }

    /** @test */
    public function cannot_retrieve_an_abilities_users_without_permission()
    {
        /** @var User $user new user whom cannot index abilities' roles */
        $user = factory('App\User')->create();

        /** @var Ability $ability new ability */
        $ability = factory(Ability::class)->create();

        self::actingAs($user)
            ->get("/api/v1/abilities/{$ability->id}/users")
            ->assertForbidden();
    }
}
