<?php

namespace Tests\Feature\api\v1\Roles;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Role;
use Tests\TestCase;

class RoleAbilitiesTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function can_retrieve_a_roles_abilities()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var Collection|Ability $abilities list of abilities that belong to the role */
        $abilities = factory(Ability::class, 10)->create();
        foreach ($abilities as $ability)
            $role->allow($ability);

        /** @var User $user user whom can view and index the role's abilities */
        $user = factory('App\User')->create();
        $user->allow('view', $role);
        $user->allow('index-abilities', $role);

        self::actingAs($user)
            ->get("/api/v1/roles/{$role->id}/abilities")
            ->assertOk()
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
            ->assertJsonCount($abilities->count(), 'data');
    }

    /** @test */
    public function cannot_retrieve_a_roles_abilities_without_permission()
    {
        /** @var User $user new user whom cannot index roles' abilities */
        $user = factory('App\User')->create();

        /** @var Role $role new role */
        $role = factory(Role::class)->create();

        self::actingAs($user)
            ->get("/api/v1/roles/{$role->id}/abilities")
            ->assertForbidden();
    }

    /** @test */
    public function can_attach_an_ability_to_a_role()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var Ability $ability */
        $ability = factory(Ability::class)->create();

        /** @var User $user new user whom can allow both the role and ability */
        $user = factory('App\User')->create();
        $user->allow('allow', $role);
        $user->allow('allow', $ability);

        // Ensure the role does not have access to the ability before test
        self::assertFalse($role->abilities()->where('ability_id', $ability->id)->exists());

        self::actingAs($user)
            ->put("/api/v1/roles/{$role->id}/abilities/{$ability->id}")
            ->assertOk()
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('roles.allowed')]);

        // Ensure the role has access to the ability post test
        self::assertTrue($role->abilities()->where('ability_id', $ability->id)->exists());
    }

    /** @test */
    public function cannot_attach_an_ability_to_a_role_without_permission()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var Ability $ability */
        $ability = factory(Ability::class)->create();

        /** @var User $user new user whom cannot allow both the role or ability */
        $user = factory('App\User')->create();

        // Ensure the role does not have access to the ability before test
        self::assertFalse($role->abilities()->where('ability_id', $ability->id)->exists());

        self::actingAs($user)
            ->put("/api/v1/roles/{$role->id}/abilities/{$ability->id}")
            ->assertForbidden();

        // Ensure the role still does not have access to the ability post test
        self::assertFalse($role->abilities()->where('ability_id', $ability->id)->exists());
    }

    /** @test */
    public function can_detach_an_ability_from_a_role()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var Ability $ability ability that belongs to the role */
        $ability = factory(Ability::class)->create();
        $role->allow($ability);

        /** @var User $user user whom can disallow both the role and ability */
        $user = factory('App\User')->create();
        $user->allow('disallow', $role);
        $user->allow('disallow', $ability);

        // Ensure the role has access to the ability before test
        self::assertTrue($role->abilities()->where('ability_id', $ability->id)->exists());

        self::actingAs($user)
            ->delete("/api/v1/roles/{$role->id}/abilities/{$ability->id}")
            ->assertOk()
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('roles.disallowed')]);

        // Ensure the role does not have access to the ability post test
        self::assertFalse($role->abilities()->where('ability_id', $ability->id)->exists());
    }

    /** @test */
    public function cannot_detach_an_ability_from_a_role_without_permission()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var Ability $ability ability that belongs to the role */
        $ability = factory(Ability::class)->create();
        $role->allow($ability);

        /** @var User $user user whom cannot disallow both the role or ability */
        $user = factory('App\User')->create();

        // Ensure the role has access to the ability before test
        self::assertTrue($role->abilities()->where('ability_id', $ability->id)->exists());

        self::actingAs($user)
            ->delete("/api/v1/roles/{$role->id}/abilities/{$ability->id}")
            ->assertForbidden();

        // Ensure the role still has access to the ability post test
        self::assertTrue($role->abilities()->where('ability_id', $ability->id)->exists());
    }
}
