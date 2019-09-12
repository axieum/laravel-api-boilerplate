<?php

namespace Tests\Feature\api\v1;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use WithFaker, RefreshDatabase;

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
                    'email_verified_at' => null // Not verified
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
    public function cannot_register_with_invalid_email()
    {
        $emailCsv = fopen(__DIR__ . '/../../../Resources/emails.csv', 'r');

        while (!feof($emailCsv)) {
            [$email, $valid] = fgetcsv($emailCsv);
            if (is_null($valid) || (bool)$valid) continue; // Only want to check invalid emails

            self::post('/api/v1/auth/register', ['email' => trim($email)])
                ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonStructure(['message', 'errors' => ['email']])
                ->assertJsonValidationErrors([
                    'email' => __('validation.email', ['attribute' => 'email'])
                ]);
        }

        fclose($emailCsv);
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
