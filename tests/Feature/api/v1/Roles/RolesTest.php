<?php

namespace Tests\Feature\api\v1\Roles;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Silber\Bouncer\Database\Role;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * @var array|Collection|Role new generated roles
     */
    private $roles;

    /**
     * @var int how many roles to pre-generate
     */
    private $count = 16;

    protected function setUp(): void
    {
        parent::setUp();

        // Pre-generate some roles
        $this->roles = factory(Role::class, $this->count)->create();
    }

    /** @test */
    public function can_index_roles()
    {
        /** @var User $user new user whom can index all roles */
        $user = factory('App\User')->create();
        $user->allow('index', Role::class);

        self::actingAs($user)
            ->get('/api/v1/roles')
            ->assertOk()
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
            ])
            ->assertJson(['meta' => ['total' => Role::query()->count()]]);
    }

    /** @test */
    public function cannot_index_roles_without_permission()
    {
        /** @var User $user new user whom cannot index roles */
        $user = factory('App\User')->create();

        self::actingAs($user)
            ->get('/api/v1/roles')
            ->assertForbidden();
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
            ->post('/api/v1/roles', $data)
            ->assertCreated()
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
        /** @var User $user new user whom cannot create roles */
        $user = factory('App\User')->create();

        self::actingAs($user)
            ->post('/api/v1/roles')
            ->assertForbidden();
    }

    /** @test */
    public function can_retrieve_a_specific_role()
    {
        /** @var Role $role */
        $role = $this->roles->random();

        /** @var User $user new user whom can read the role */
        $user = factory('App\User')->create();
        $user->allow('read', $role);

        self::actingAs($user)
            ->get("/api/v1/roles/{$role->id}")
            ->assertOk()
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
    public function cannot_retrieve_a_specific_role_without_permission()
    {
        /** @var Role $role */
        $role = $this->roles->random();

        /** @var User $user new user whom can read the role */
        $user = factory('App\User')->create();

        self::actingAs($user)
            ->get("/api/v1/roles/{$role->id}")
            ->assertForbidden();
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
            'level' => $this->faker->numberBetween(0, 10) // update an extra attribute
        ]);

        self::actingAs($user)
            ->patch("/api/v1/roles/{$role->id}", $data)
            ->assertOk()
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

        // Ensure old role details do not exist, but new ones do
        self::assertDatabaseMissing('roles', $role->only(['name', 'title']));
        self::assertDatabaseHas('roles', $data);
    }

    /** @test */
    public function cannot_update_a_role_without_permission()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        /** @var User $user new user whom cannot update a role */
        $user = factory('App\User')->create();

        /** @var array $data desired role attribute array for update */
        $data = factory(Role::class)->raw([
            'level' => $this->faker->numberBetween(0, 10) // update an extra attribute
        ]);

        self::actingAs($user)
            ->patch("/api/v1/roles/{$role->id}", $data)
            ->assertForbidden();

        // Ensure old role details still exist, and new ones do not
        self::assertDatabaseHas('roles', $role->only(['name', 'title']));
        self::assertDatabaseMissing('roles', $data);
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
            ->assertNoContent();

        // Ensure the role no longer exists after
        self::assertDatabaseMissing('roles', $role->toArray());
    }

    /** @test */
    public function cannot_delete_a_role_without_permission()
    {
        /** @var Role $role */
        $role = $this->roles->random();

        self::delete("/api/v1/roles/{$role->id}")
            ->assertForbidden();

        // Ensure the role still exists
        self::assertDatabaseHas('roles', $role->toArray());
    }
}
