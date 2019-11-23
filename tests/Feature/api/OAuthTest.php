<?php

namespace Tests\Feature\api;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client as PassportClient;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * @var PassportClient Passport OAuth password client
     */
    private $password_client;

    /**
     * @var User created user whom is being authenticated
     */
    private $user;

    /**
     * @var string plaintext password for the created user
     */
    private $password;

    protected function setUp(): void
    {
        parent::setUp();

        // Fetch the installed Password Client
        $this->password_client = PassportClient::query()
            ->where('password_client', true)
            ->firstOrFail();

        // Create a new user
        $this->password = $this->faker->password(8);
        $this->user = factory('App\User')->create([
            'password' => Hash::make($this->password)
        ]);
    }

    /** @test */
    public function can_retrieve_access_token_via_password_grant()
    {
        $data = [
            'grant_type' => 'password',
            'client_id' => $this->password_client->id,
            'client_secret' => $this->password_client->secret,
            'username' => $this->user->email,
            'password' => $this->password
        ];

        // Attempt to retrieve an access token via the password grant
        $res = self::post('/oauth/token', $data)
            ->assertOk()
            ->assertJsonStructure([
                'token_type',
                'expires_in',
                'access_token',
                'refresh_token'
            ]);

        // Use the token to authenticate the user and retrieve their user details
        self::get('/api/v1/users/me', [
            'Authorization' => "{$res->json('token_type')} {$res->json('access_token')}"
        ])
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'email_verified_at',
                'updated_at',
                'created_at'
            ])
            ->assertJson([
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email
            ]);
    }

    /** @test */
    public function can_refresh_access_token()
    {
        $data = [
            'grant_type' => 'password',
            'client_id' => $this->password_client->id,
            'client_secret' => $this->password_client->secret,
            'username' => $this->user->email,
            'password' => $this->password
        ];

        // Attempt to retrieve an access token via the password grant
        $res = self::post('/oauth/token', $data)
            ->assertOk()
            ->assertJsonStructure([
                'token_type',
                'expires_in',
                'access_token',
                'refresh_token'
            ]);

        $data = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->password_client->id,
            'client_secret' => $this->password_client->secret,
            'refresh_token' => $res->json('refresh_token')
        ];

        // Refresh the token
        $ref = self::post('/oauth/token', $data)
            ->assertOk()
            ->assertJsonStructure([
                'token_type',
                'expires_in',
                'access_token',
                'refresh_token'
            ]);

        // Ensure refresh token is not equal to the old token
        self::assertNotEquals($ref->json('access_token'), $res->json('access_token'));

        // Ensure the refreshed token operates
        self::get('/api/v1/users/me', [
            'Authorization' => "{$ref->json('token_type')} {$ref->json('access_token')}"
        ])
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'email_verified_at',
                'updated_at',
                'created_at'
            ])
            ->assertJson([
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email
            ]);
    }
}
