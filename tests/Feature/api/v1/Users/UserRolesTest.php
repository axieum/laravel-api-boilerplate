<?php

namespace Tests\Feature\api\v1\Users;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Silber\Bouncer\Database\Role;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * @var array|Collection|Role new generated roles
     */
    private $roles;

    /**
     * @var int how many roles to pre-generate
     */
    private $count = 5;

    protected function setUp(): void
    {
        parent::setUp();

        // Pre-generate some roles
        $this->roles = factory(Role::class, $this->count)->create();
    }

    /** @test */
    public function can_index_roles()
    {
        /** @var User $passive user whom has roles */
        /** @var User $active user whom can index any users' roles */
        [$passive, $active] = factory('App\User', 2)->create();

        $active->allow('index-roles', User::class);
        $passive->assign($this->roles);

        // Request passive user's roles
        self::actingAs($active)
            ->get("/api/v1/users/{$passive->id}/roles")
            ->assertStatus(Response::HTTP_OK)
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
            ->assertJsonCount($this->roles->count(), 'data');
    }

    /** @test */
    public function can_index_own_roles()
    {
        /** @var User $user user whom has roles */
        $user = factory('App\User')->create();
        $user->assign($this->roles->first());

        // Request passive user's roles
        self::actingAs($user)
            ->get("/api/v1/users/{$user->id}/roles")
            ->assertStatus(Response::HTTP_OK)
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
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function cannot_index_roles_without_permission()
    {
        /** @var User $passive user whom has roles */
        /** @var User $active user whom cannot index any users' roles */
        [$passive, $active] = factory('App\User', 2)->create();

        self::actingAs($active)
            ->get("/api/v1/users/{$passive->id}/roles")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function can_assign_a_role()
    {
        /** @var Role $role role to be assigned */
        $role = $this->roles->first();

        /** @var User $passive user whom is being assigned the role */
        /** @var User $active user whom can assign a specific role to others */
        [$passive, $active] = factory('App\User', 2)->create();

        $active->allow('assign', $role); // can assign the specific role
        $active->allow('assign', User::class); // can assign to any user

        // Ensure the user does not inherit the role before test
        self::assertTrue($passive->isNotA($role));

        self::actingAs($active)
            ->put("/api/v1/users/{$passive->id}/roles/{$role->id}")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('users.assigned')]);

        // Ensure the user inherits the role post test
        self::assertTrue($passive->isA($role));
    }

    /** @test */
    public function cannot_assign_a_role_without_ability()
    {
        /** @var Role $role role to be assigned */
        $role = $this->roles->first();

        /** @var User $user user whom cannot assign a specific role */
        $user = factory('App\User')->create();

        // Ensure the user does not inherit the role before test
        self::assertTrue($user->isNotA($role));

        self::actingAs($user)
            ->put("/api/v1/users/{$user->id}/roles/{$role->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Ensure the user does not inherit the role still
        self::assertTrue($user->isNotA($role));
    }

    /** @test */
    public function can_retract_a_role()
    {
        /** @var Role $role role to be retracted */
        $role = $this->roles->first();

        /** @var User $passive user whom is having the role retracted */
        /** @var User $active user whom can retract a specific role from others */
        [$passive, $active] = factory('App\User', 2)->create();

        $active->allow('retract', $role); // can retract the specific role
        $active->allow('retract', User::class); // can retract from any user
        $passive->assign($role);

        // Ensure the user inherits the role beforehand
        self::assertTrue($passive->isA($role));

        self::actingAs($active)
            ->delete("/api/v1/users/{$passive->id}/roles/{$role->id}")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => __('users.retracted')]);

        // Ensure the user does not inherit the role
        self::assertTrue($passive->isNotA($role));
    }

    /** @test */
    public function cannot_retract_a_role_without_ability()
    {
        /** @var Role $role role to be assigned */
        $role = $this->roles->first();

        /** @var User $user user whom cannot retract a specific role */
        $user = factory('App\User')->create();
        $user->assign($role);

        // Ensure the user inherits the role before test
        self::assertTrue($user->isA($role));

        self::actingAs($user)
            ->delete("/api/v1/users/{$user->id}/roles/{$role->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Ensure the user still inherits the role
        self::assertTrue($user->isA($role));
    }
}
