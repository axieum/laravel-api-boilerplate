<?php

namespace Tests\Feature\api\v1;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Role;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /** @test */
    public function can_index_roles()
    {
        // Create new roles to index
        factory(Role::class, 5)->create();

        /** @var User $user new user whom can index all roles */
        $user = factory('App\User')->create();
        $user->allow('index', Role::class);

        self::actingAs($user)
            ->get('/api/v1/roles')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'title',
                    'level',
                    'updated_at',
                    'created_at'
                ]],
                'links' => [],
                'meta' => []
            ]);
    }

    /** @test */
    public function cannot_index_roles_without_permission()
    {
        self::get('/api/v1/roles')->assertStatus(403);
    }

    /** @test */
    public function can_create_a_new_role()
    {
        /** @var array $data role attribute array */
        $data = factory(Role::class)->raw();

        /** @var User $user new user whom can create roles */
        $user = factory('App\User')->create();
        $user->allow('create', Role::class);

        self::actingAs($user)
            ->put('/api/v1/roles', $data)
            ->assertJsonStructure([
                'message',
                'role' => [
                    'id',
                    'name',
                    'title',
                    'level',
                    'scope',
                    'updated_at',
                    'created_at'
                ]
            ])
            ->assertJson([
                'role' => [
                    'name' => $data['name'],
                    'title' => $data['title']
                ]
            ]);

        self::assertDatabaseHas('roles', $data);
    }

    /** @test */
    public function cannot_create_a_new_role_without_permission()
    {
        self::put('/api/v1/roles')->assertStatus(403);
    }

    /** @test */
    public function can_retrieve_specific_role()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var User $user new user whom can view the role */
        $user = factory('App\User')->create();
        $user->allow('view', $role);

        self::actingAs($user)
            ->get("/api/v1/roles/{$role->id}")
            ->assertJsonStructure([
                'id',
                'name',
                'title',
                'level',
                'scope',
                'updated_at',
                'created_at'
            ])
            ->assertJson([
                'name' => $role->name,
                'title' => $role->title
            ]);
    }

    /** @test */
    public function can_update_a_role()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var User $user new user whom can update the role */
        $user = factory('App\User')->create();
        $user->allow('update', $role);

        /** @var array $data desired role attribute array for update */
        $data = factory(Role::class)->raw([
            'level' => $this->faker->numberBetween(0, 10)
        ]);

        self::actingAs($user)
            ->patch("/api/v1/roles/{$role->id}", $data)
            ->assertJsonStructure([
                'message',
                'role' => [
                    'id',
                    'name',
                    'title',
                    'level',
                    'updated_at',
                    'created_at'
                ]
            ])
            ->assertJson([
                'role' => [
                    'name' => $data['name'],
                    'title' => $data['title'],
                    'level' => $data['level']
                ]
            ]);

        self::assertDatabaseMissing('roles', $role->only(['name', 'title']));
        self::assertDatabaseHas('roles', $data);
    }

    /** @test */
    public function can_delete_a_role()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var User $user new user whom can delete the role */
        $user = factory('App\User')->create();
        $user->allow('delete', $role);

        // Ensure the role exists beforehand
        self::assertDatabaseHas('roles', $role->toArray());

        self::actingAs($user)
            ->delete("/api/v1/roles/{$role->id}")
            ->assertStatus(204);

        // Ensure the role no longer exists after
        self::assertDatabaseMissing('roles', $role->toArray());
    }

    /** @test */
    public function cannot_delete_a_role_without_permission()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        self::delete("/api/v1/roles/{$role->id}")
            ->assertStatus(403);

        // Ensure the role still exists
        self::assertDatabaseHas('roles', $role->toArray());
    }

    /** @test */
    public function can_retrieve_a_roles_abilities()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var Ability $ability */
        $ability = factory(Ability::class)->create();

        // Give the new role access to the ability
        $role->allow($ability);

        /** @var User $user new user whom can view and index a roles' abilities */
        $user = factory('App\User')->create();
        $user->allow('view', $role);
        $user->allow('index.ability', $role);

        self::actingAs($user)
            ->get("/api/v1/roles/{$role->id}/abilities")
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
            ->assertJson([
                'data' => [
                    [
                        'name' => $ability->name,
                        'title' => $ability->title
                    ]
                ]
            ]);
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
            ->assertStatus(200)
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('roles.allowed')]);

        // Ensure the role has access to the ability post test
        self::assertTrue($role->abilities()->where('ability_id', $ability->id)->exists());
    }

    /** @test */
    public function can_detach_an_ability_from_a_role()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var Ability $ability */
        $ability = factory(Ability::class)->create();

        // Allow the role access to the ability
        $role->allow($ability);

        /** @var User $user new user whom can disallow both the role and ability */
        $user = factory('App\User')->create();
        $user->allow('disallow', $role);
        $user->allow('disallow', $ability);

        // Ensure the role has access to the ability before test
        self::assertTrue($role->abilities()->where('ability_id', $ability->id)->exists());

        self::actingAs($user)
            ->delete("/api/v1/roles/{$role->id}/abilities/{$ability->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('roles.disallowed')]);

        // Ensure the role does not have access to the ability post test
        self::assertFalse($role->abilities()->where('ability_id', $ability->id)->exists());
    }

    /** @test */
    public function can_retrieve_a_roles_users()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var User $user new user whom can view and index a roles' users */
        $user = factory('App\User')->create();
        $user->allow('view', $role);
        $user->allow('index.user', $role);

        // Assign the role to the new user
        $user->assign($role);

        self::actingAs($user)
            ->get("/api/v1/roles/{$role->id}/users")
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

    /** @test */
    public function can_assign_a_role_to_a_user()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var User $user new user whom can assign roles to themselves and the role */
        $user = factory('App\User')->create();
        $user->allow('assign', $role);
        $user->allow('assign', $user);

        // Ensure the user does not inherit the role before test
        self::assertTrue($user->isNotA($role));

        self::actingAs($user)
            ->put("/api/v1/roles/{$role->id}/users/{$user->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('roles.assigned')]);

        // Ensure the user inherits the role post test
        self::assertTrue($user->isA($role));
    }

    /** @test */
    public function can_retract_a_role_from_a_user()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        // Create a user and assign them the role
        /** @var User $user new user whom can retract roles from themselves and the role */
        $user = factory('App\User')->create();
        $user->allow('retract', $role);
        $user->allow('retract', $user);
        $user->assign($role);

        // Ensure the user inherits the role before test
        self::assertTrue($user->isA($role));

        self::actingAs($user)
            ->delete("/api/v1/roles/{$role->id}/users/{$user->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('roles.retracted')]);

        // Ensure the user does not inherit the role post test
        self::assertTrue($user->isNotA($role));
    }
}
