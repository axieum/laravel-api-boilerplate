<?php

namespace Tests\Feature\api\v1\Roles;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Silber\Bouncer\Database\Role;
use Tests\TestCase;

class RoleUsersTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function can_retrieve_a_roles_users()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var User $active user whom can view and index the role's users */
        $active = factory('App\User')->create();
        $active->allow('view', $role);
        $active->allow('index-users', $role);

        /** @var Collection|User $users users whom inherit the role */
        $users = factory('App\User', 10)->create();
        foreach ($users as $user)
            $user->assign($role);

        self::actingAs($active)
            ->get("/api/v1/roles/{$role->id}/users")
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
    public function cannot_retrieve_a_roles_users_without_permission()
    {
        /** @var User $user new user whom cannot index roles' users */
        $user = factory('App\User')->create();

        /** @var Role $role new role */
        $role = factory(Role::class)->create();

        self::actingAs($user)
            ->get("/api/v1/roles/{$role->id}/users")
            ->assertForbidden();
    }

    /** @test */
    public function can_assign_a_role_to_a_user()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var User $passive user whom is being assigned the role */
        /** @var User $active user whom can assign a specific role to others */
        [$passive, $active] = factory('App\User', 2)->create();

        $active->allow('assign', $role); // can assign the specific role
        $active->allow('assign', User::class); // can assign to any user

        // Ensure the user does not inherit the role before test
        self::assertTrue($passive->isNotA($role));

        self::actingAs($active)
            ->put("/api/v1/roles/{$role->id}/users/{$passive->id}")
            ->assertOk()
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('roles.assigned')]);

        // Ensure the user inherits the role post test
        self::assertTrue($passive->isA($role));
    }

    /** @test */
    public function cannot_assign_a_role_to_a_user_without_permission()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var User $passive user whom is being assigned the role */
        /** @var User $active user whom cannot assign a specific role to others */
        [$passive, $active] = factory('App\User', 2)->create();

        // Ensure the user does not inherit the role before test
        self::assertTrue($passive->isNotA($role));

        self::actingAs($active)
            ->put("/api/v1/roles/{$role->id}/users/{$passive->id}")
            ->assertForbidden();

        // Ensure the user still does not inherit the role post test
        self::assertTrue($passive->isNotA($role));
    }

    /** @test */
    public function can_retract_a_role_from_a_user()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var User $passive user whom is having the role retracted */
        /** @var User $active user whom can retract a specific role from others */
        [$passive, $active] = factory('App\User', 2)->create();

        $active->allow('retract', $role); // can retract the specific role
        $active->allow('retract', User::class); // can retract from any user
        $passive->assign($role);

        // Ensure the user inherits the role before test
        self::assertTrue($passive->isA($role));

        self::actingAs($active)
            ->delete("/api/v1/roles/{$role->id}/users/{$passive->id}")
            ->assertOk()
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('roles.retracted')]);

        // Ensure the user does not inherit the role post test
        self::assertTrue($passive->isNotA($role));
    }

    /** @test */
    public function cannot_retract_a_role_from_a_user_without_permission()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var User $passive user whom is having the role retracted */
        /** @var User $active user whom cannot retract a specific role from others */
        [$passive, $active] = factory('App\User', 2)->create();

        $passive->assign($role);

        // Ensure the user inherits the role before test
        self::assertTrue($passive->isA($role));

        self::actingAs($active)
            ->delete("/api/v1/roles/{$role->id}/users/{$passive->id}")
            ->assertForbidden();

        // Ensure the user still inherits the role post test
        self::assertTrue($passive->isA($role));
    }
}
