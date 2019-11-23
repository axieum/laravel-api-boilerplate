<?php

namespace Tests\Feature\api\v1\Auth;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /** @test */
    public function can_register_a_new_user()
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->email,
            'password' => $password = $this->faker->password(8),
            'password_confirmation' => $password,
            'agreement' => true
        ];

        self::post('/api/v1/auth/register', $data)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure(['message', 'user' => ['name', 'email', 'email_verified_at']])
            ->assertJson([
                'user' => [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'email_verified_at' => null // not verified
                ]
            ]);

        self::assertDatabaseHas('users', [
            'name' => $data['name'],
            'email' => $data['email']
        ]);
    }

    /** @test */
    public function cannot_register_duplicate_email()
    {
        /** @var User $passive registered user with taken email */
        $passive = factory('App\User')->create();

        /** @var array $data registration form with duplicate email */
        $data = [
            'name' => $this->faker->name,
            'email' => $passive->email,
            'password' => $password = $this->faker->password(8),
            'password_confirmation' => $password,
            'agreement' => true
        ];

        self::post('/api/v1/auth/register', $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure(['message', 'errors' => ['email']])
            ->assertJsonValidationErrors([
                'email' => __('validation.custom.email.unique', ['attribute' => 'email'])
            ]);
    }

    /** @test */
    public function cannot_register_with_password_too_short()
    {
        self::post('/api/v1/auth/register', [
            'password' => 'secrets',
            'password_confirmation' => 'secrets'
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure(['message', 'errors' => ['password']])
            ->assertJsonValidationErrors([
                'password' => __('validation.min.string', ['attribute' => 'password', 'min' => 8])
            ]);
    }

    /** @test */
    public function cannot_register_with_mismatching_password_confirmation()
    {
        self::post('/api/v1/auth/register', [
            'password' => 'secret123',
            'password_confirmation' => 'secret321'
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure(['message', 'errors' => ['password']])
            ->assertJsonValidationErrors([
                'password' => __('validation.confirmed', ['attribute' => 'password'])
            ]);
    }

    /** @test */
    public function cannot_register_without_agreement()
    {
        self::post('/api/v1/auth/register', ['agreement' => false])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure(['message', 'errors' => ['agreement']])
            ->assertJsonValidationErrors([
                'agreement' => __('validation.accepted', ['attribute' => __('validation.attributes.agreement')])
            ]);
    }
}
