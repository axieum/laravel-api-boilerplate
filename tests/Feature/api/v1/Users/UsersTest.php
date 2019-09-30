<?php

namespace Tests\Feature\api\v1\Users;

use App\User;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * @var int number of users to generate
     */
    private $count = 50;

    protected function setUp(): void
    {
        parent::setUp();

        // Generate randomly verified users
        factory('App\User', $this->count)->create([
            'email_verified_at' => function () {
                return $this->faker->optional(0.5)->dateTime;
            }
        ]);
    }

    /** @test */
    public function can_index_users()
    {
        /** @var User $user user whom can index users */
        $user = factory('App\User')->create();
        $user->allow('index', User::class);

        /** @var int $perPage how many users per page to request */
        $perPage = 7;

        /** @var int $count total user count */
        $count = User::count();

        self::actingAs($user)
            ->get("/api/v1/users?page[size]=${perPage}")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'email_verified_at',
                        'updated_at',
                        'created_at'
                    ]
                ],
                'links' => [],
                'meta' => []
            ])
            ->assertJsonCount(
                $perPage > $count ? $count : $perPage,
                'data'
            ) // per page requested
            ->assertJson([
                'meta' => [
                    'per_page' => $perPage, // per page requested
                    'total' => $count // users created
                ]
            ]);
    }

    /** @test */
    public function can_index_verified_users()
    {
        /** @var User $user user whom can index users */
        $user = factory('App\User')->create();
        $user->allow('index', User::class);

        // Request verified users
        self::actingAs($user)
            ->get("/api/v1/users?filter[verified]=true")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'email_verified_at',
                        'updated_at',
                        'created_at'
                    ]
                ],
                'links' => [],
                'meta' => []
            ])
            ->assertJsonMissing([
                'data' => [
                    [
                        'email_verified_at' => null
                    ]
                ]
            ])
            ->assertJson([
                'meta' => [
                    'total' => User::verified(true)->count() // total users verified
                ]
            ]);

        // Request unverified users
        self::actingAs($user)
            ->get("/api/v1/users?filter[verified]=false")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'email_verified_at',
                        'updated_at',
                        'created_at'
                    ]
                ],
                'links' => [],
                'meta' => []
            ])
            ->assertJson([
                'data' => [
                    [
                        'email_verified_at' => null
                    ]
                ],
                'meta' => [
                    'total' => User::verified(false)->count() // total users unverified
                ]
            ]);
    }

    /** @test */
    public function cannot_index_users_without_ability()
    {
        self::get('/api/v1/users')->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function can_retrieve_a_user()
    {
        /** @var User $passive user to be retrieved */
        /** @var User $active user whom can retrieve passive user */
        [$passive, $active] = factory('App\User', 2)->create();

        self::actingAs($active)
            ->get("/api/v1/users/{$passive->id}")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'id',
                'name',
                'email_verified_at',
                'updated_at',
                'created_at'
            ])
            ->assertJson([
                'id' => $passive->id,
                'name' => $passive->name
            ]);
    }

    /** @test */
    public function can_retrieve_own_user()
    {
        /** @var User $user new acting user */
        $user = factory('App\User')->create();

        self::actingAs($user)
            ->get('/api/v1/users/me')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'email_verified_at',
                'updated_at',
                'created_at'
            ])
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);
    }

    /** @test */
    public function can_delete_user()
    {
        /** @var User $passive user to be deleted */
        /** @var User $active user whom can delete any user */
        [$passive, $active] = factory('App\User', 2)->create();

        $active->allow('delete', User::class);

        // Passive user exists in the database
        self::assertDatabaseHas('users', $passive->only(['id', 'name', 'email']));

        // Delete the user
        self::actingAs($active)
            ->delete("/api/v1/users/{$passive->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        // Passive user does not exist in the database
        self::assertDatabaseMissing('users', $passive->only(['id', 'name', 'email']));
    }

    /** @test */
    public function can_delete_own_user()
    {
        /** @var User $user new user to be authenticated and deleted */
        $user = factory('App\User')->create();

        // Passive user exists in the database
        self::assertDatabaseHas('users', $user->only(['id', 'name', 'email']));

        // Delete the user
        self::actingAs($user)
            ->delete("/api/v1/users/{$user->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        // Passive user does not exist in the database
        self::assertDatabaseMissing('users', $user->only(['id', 'name', 'email']));
    }

    /** @test */
    public function cannot_delete_user_without_ability()
    {
        /** @var User $passive user to be deleted */
        /** @var User $active user whom cannot delete any user */
        [$passive, $active] = factory('App\User', 2)->create();

        // Passive user exists in the database
        self::assertDatabaseHas('users', $passive->only(['id', 'name', 'email']));

        // Delete the user
        self::actingAs($active)
            ->delete("/api/v1/users/{$passive->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Passive user still exists in the database
        self::assertDatabaseHas('users', $passive->only(['id', 'name', 'email']));
    }

    /** @test */
    public function can_create_new_user()
    {
        /** @var User $user user whom can manually create users */
        $user = factory('App\User')->create();
        $user->allow('create', User::class);

        /** @var array $data new user details */
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->email,
            'password' => $password = $this->faker->password(8),
            'password_confirmation' => $password
        ];

        self::actingAs($user)
            ->post('/api/v1/users', $data)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'updated_at',
                    'created_at'
                ]
            ])
            ->assertJson([
                'message' => __('users.created'),
                'user' => [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'email_verified_at' => null
                ]
            ]);

        // Assert that the database has the new user
        self::assertDatabaseHas('users', [
            'name' => $data['name'],
            'email' => $data['email']
        ]);
    }

    /** @test */
    public function can_create_new_verified_user()
    {
        /** @var User $user user whom can manually create verified users */
        $user = factory('App\User')->create();
        $user->allow(['create', 'verify'], User::class);

        /** @var array $data new user details */
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->email,
            'password' => $password = $this->faker->password(8),
            'password_confirmation' => $password,
            'verified' => true
        ];

        self::actingAs($user)
            ->post('/api/v1/users', $data)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'updated_at',
                    'created_at'
                ]
            ])
            ->assertJson([
                'message' => __('users.created'),
                'user' => [
                    'name' => $data['name'],
                    'email' => $data['email']
                ]
            ])
            ->assertJsonMissing([
                'user' => [
                    'email_verified_at' => null
                ]
            ]);

        // Assert that the database has the new user
        self::assertDatabaseHas('users', [
            'name' => $data['name'],
            'email' => $data['email']
        ]);

        // Assert the verified status of the new user
        self::assertTrue(
            User::verified(true)
                ->where('name', $data['name'])
                ->where('email', $data['email'])
                ->exists()
        );
    }

    /** @test */
    public function cannot_create_new_user_without_ability()
    {
        /** @var User $user user whom cannot manually create users */
        $user = factory('App\User')->create();

        /** @var array $data new user details */
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->email,
            'password' => $password = $this->faker->password(8),
            'password_confirmation' => $password
        ];

        self::actingAs($user)
            ->post('/api/v1/users', $data)
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Assert that the database does not have the new user
        self::assertDatabaseMissing('users', [
            'name' => $data['name'],
            'email' => $data['email']
        ]);
    }

    /** @test */
    public function cannot_create_new_verified_user_without_ability()
    {
        /** @var User $user user whom can manually create users but not verify */
        $user = factory('App\User')->create();
        $user->allow('create', User::class);

        /** @var array $data new user details */
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->email,
            'password' => $password = $this->faker->password(8),
            'password_confirmation' => $password,
            'verified' => true
        ];

        self::actingAs($user)
            ->post('/api/v1/users', $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure(['message', 'errors' => []])
            ->assertJsonValidationErrors([
                'verified' => __('validation.custom.bouncer.user.verify', [
                    'attribute' => 'verified'
                ])
            ]);

        // Assert that the database does not have the new user
        self::assertDatabaseMissing('users', [
            'name' => $data['name'],
            'email' => $data['email']
        ]);
    }

    /** @test */
    public function can_update_user()
    {
        /** @var User $passive user whom is being updated */
        /** @var User $active user whom can update any user */
        [$passive, $active] = factory('App\User', 2)->create();

        $active->allow('update', User::class);

        /** @var array $data new passive user details to update to */
        $data = [
            'name' => $this->faker->name,
            'password' => $password = $this->faker->password(8),
            'password_confirmation' => $password,
        ];

        self::actingAs($active)
            ->patch("/api/v1/users/{$passive->id}", $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email_verified_at',
                    'updated_at',
                    'created_at'
                ]
            ])
            ->assertJson([
                'message' => __('users.updated'),
                'user' => [
                    'id' => $passive->id,
                    'name' => $data['name'], // new name
                ]
            ]);

        // Assert that the database has the updated details
        $passive->refresh();
        self::assertEquals($data['name'], $passive->name);
        self::assertTrue(Hash::check($data['password'], $passive->getAuthPassword()));
    }

    /** @test */
    public function can_update_own_user()
    {
        /** @var User $user new user whom is updating themselves */
        $user = factory('App\User')->create();

        /** @var array $data new user details to update to */
        $data = [
            'name' => $this->faker->name,
            'password' => $password = $this->faker->password(8),
            'password_confirmation' => $password,
        ];

        self::actingAs($user)
            ->patch("/api/v1/users/{$user->id}", $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email_verified_at',
                    'updated_at',
                    'created_at'
                ]
            ])
            ->assertJson([
                'message' => __('users.updated'),
                'user' => [
                    'id' => $user->id,
                    'name' => $data['name'], // new name
                ]
            ]);

        // Ensure the new details persisted
        $user->refresh();
        self::assertEquals($data['name'], $user->name);
        self::assertTrue(Hash::check($data['password'], $user->getAuthPassword()));
    }

    /** @test */
    public function can_update_user_verified_status()
    {
        /** @var User $passive unverified user whom is being updated */
        /** @var User $active user whom can update and verify any user */
        [$passive, $active] = factory('App\User', 2)->create();

        $passive->update(['email_verified_at' => null]);
        $active->allow(['update', 'verify'], User::class);

        /** @var array $data new passive user details to update to */
        $data = [
            'name' => $this->faker->name,
            'verified' => true
        ];

        self::actingAs($active)
            ->patch("/api/v1/users/{$passive->id}", $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email_verified_at',
                    'updated_at',
                    'created_at'
                ]
            ])
            ->assertJson([
                'message' => __('users.updated'),
                'user' => [
                    'id' => $passive->id,
                    'name' => $data['name'], // new name
                ]
            ])
            ->assertJsonMissing([
                'user' => [
                    'email_verified_at' => $passive->email_verified_at
                ]
            ]);

        // Ensure the new details persisted
        $passive->refresh();
        self::assertEquals($data['name'], $passive->name);
        self::assertTrue($passive->hasVerifiedEmail());
    }

    /** @test */
    public function can_update_user_email()
    {
        /** @var User $passive verified user whom is being updated */
        /** @var User $active user whom can update any user and their emails */
        [$passive, $active] = factory('App\User', 2)->create();

        $active->allow(['update', 'read-email'], User::class);

        /** @var array $data new passive user details to update to */
        $data = [
            'email' => $this->faker->unique()->email
        ];

        // Begin intercepting notifications for re-verification email sent
        Notification::fake();

        self::actingAs($active)
            ->patch("/api/v1/users/{$passive->id}", $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'updated_at',
                    'created_at'
                ]
            ])
            ->assertJson([
                'message' => __('users.updated'),
                'user' => [
                    'id' => $passive->id,
                    'email' => $data['email'], // new email
                ]
            ])
            ->assertJsonMissing([
                'user' => [
                    'email_verified_at' => $passive->email_verified_at // is unverified
                ]
            ]);

        // Ensure the new details persisted and user is now unverified
        $passive->refresh();
        self::assertEquals($data['email'], $passive->email);
        self::assertFalse($passive->hasVerifiedEmail());

        // Assert new verification email sent
        Notification::assertSentTo($passive, VerifyEmailNotification::class);
    }

    /** @test */
    public function cannot_update_user_email_without_ability()
    {
        /** @var User $passive verified user whom is being updated */
        /** @var User $active user whom can update any user but not their emails */
        [$passive, $active] = factory('App\User', 2)->create();

        // NB: They require 'read-email' as well to touch emails
        $active->allow('update', User::class);

        /** @var array $data new passive user details to update to */
        $data = [
            'email' => $this->faker->unique()->email
        ];

        // Begin intercepting notifications for re-verification email sent
        Notification::fake();

        self::actingAs($active)
            ->patch("/api/v1/users/{$passive->id}", $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure(['message', 'errors' => []])
            ->assertJsonValidationErrors([
                'email' => __('validation.custom.bouncer.user.email', [
                    'attribute' => 'email'
                ])
            ]);

        // Ensure new email did not persist
        self::assertNotEquals($data['email'], $passive->refresh()->email);

        // Assert new verification email not sent
        Notification::assertNothingSent();
    }

    /** @test */
    public function cannot_update_user_without_ability()
    {
        /** @var User $passive user whom is being updated */
        /** @var User $active user whom cannot update any user */
        [$passive, $active] = factory('App\User', 2)->create();

        /** @var array $data new passive user details to update to */
        $data = [
            'name' => $this->faker->name,
            'password' => $password = $this->faker->password(8),
            'password_confirmation' => $password,
        ];

        self::actingAs($active)
            ->patch("/api/v1/users/{$passive->id}", $data)
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Ensure the new details did not persist
        $passive->refresh();
        self::assertNotEquals($data['name'], $passive->name);
        self::assertFalse(Hash::check($data['password'], $passive->getAuthPassword()));
    }

    /** @test */
    public function cannot_update_user_verified_status_without_ability()
    {
        /** @var User $passive unverified user whom is being updated */
        /** @var User $active user whom can update but not verify any user */
        [$passive, $active] = factory('App\User', 2)->create();

        $passive->update(['email_verified_at' => null]);
        $active->allow('update', User::class);

        /** @var array $data new passive user details to update to */
        $data = [
            'name' => $this->faker->name,
            'verified' => true
        ];

        self::actingAs($active)
            ->patch("/api/v1/users/{$passive->id}", $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure(['message', 'errors' => []])
            ->assertJsonValidationErrors([
                'verified' => __('validation.custom.bouncer.user.verify', [
                    'attribute' => 'verified'
                ])
            ]);

        // Ensure the new details did not persist
        $passive->refresh();
        self::assertNotEquals($data['name'], $passive->name);
        self::assertFalse($passive->hasVerifiedEmail());
    }
}
