<?php

namespace Tests\Feature\api\v1;

use App\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /** @test */
    public function can_resend_verification_email()
    {
        /** @var User $user new unverified user */
        $user = factory('App\User')->create(['email_verified_at' => null]);
        self::assertFalse($user->hasVerifiedEmail());

        // Intercept notifications
        Notification::fake();

        // Resend verification email
        self::actingAs($user)
            ->post('/api/v1/auth/verify/resend')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['message', 'resent'])
            ->assertJson([
                'message' => __('auth.verification.resent'),
                'resent' => true
            ]);

        // Ensure that the user receives the notification
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function cannot_resend_verification_email_when_already_verified()
    {
        /** @var User $user new verified user */
        $user = factory('App\User')->create(['email_verified_at' => now()]);
        self::assertTrue($user->hasVerifiedEmail());

        // Intercept notifications
        Notification::fake();

        // Attempt to resend verification email
        self::actingAs($user)
            ->post('/api/v1/auth/verify/resend')
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure(['message', 'resent'])
            ->assertJson([
                'message' => __('auth.verification.conflict'),
                'resent' => false
            ]);

        // Ensure that the user did not receive the verification email
        Notification::assertNotSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function can_verify_email()
    {
        /** @var User $user new unverified user */
        $user = factory('App\User')->create(['email_verified_at' => null]);
        self::assertFalse($user->hasVerifiedEmail());

        // Intercept notifications
        Notification::fake();

        // Resend verification email
        self::actingAs($user)->post('/api/v1/auth/verify/resend')->assertStatus(Response::HTTP_OK);

        // Intercept the notification and extract the verification url, then
        // try to use the url to verify the user
        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            function (VerifyEmail $notification, array $channel, User $notifiable) use ($user) {
                // Extract the verification url
                $content = $notification->toMail($notifiable)->render();
                preg_match(
                    '/\/auth\/verify\/([0-9]+)\/([A-Za-z0-9]+?)(\?expires=[0-9]+&(?:amp;)?signature=[A-Za-z0-9]+)/',
                    $content,
                    $url
                );

                // Attempt to use the URL to verify the user
                self::actingAs($user)
                    ->get($url[0])
                    ->assertStatus(Response::HTTP_OK)
                    ->assertJsonStructure(['message', 'verified'])
                    ->assertJson([
                        'message' => __('auth.verification.verified'),
                        'verified' => true
                    ]);

                self::assertTrue($user->refresh()->hasVerifiedEmail());

                return true;
            }
        );
    }

    /** @test */
    public function cannot_verify_email_when_already_verified()
    {
        /** @var User $user new unverified user */
        $user = factory('App\User')->create(['email_verified_at' => null]);
        self::assertFalse($user->hasVerifiedEmail());

        // Intercept notifications
        Notification::fake();

        // Resend verification email
        self::actingAs($user)->post('/api/v1/auth/verify/resend')->assertStatus(Response::HTTP_OK);

        // Intercept the notification and extract the verification url, then
        // try to use the url to verify the user
        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            function (VerifyEmail $notification, array $channel, User $notifiable) use ($user) {
                // Extract the verification url
                $content = $notification->toMail($notifiable)->render();
                preg_match(
                    '/\/auth\/verify\/([0-9]+)\/([A-Za-z0-9]+?)(\?expires=[0-9]+&(?:amp;)?signature=[A-Za-z0-9]+)/',
                    $content,
                    $url
                );

                // Verify the user
                $user->markEmailAsVerified();

                // Attempt to use the URL to verify the already verified user
                self::actingAs($user)
                    ->get($url[0])
                    ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->assertJsonStructure(['message', 'verified'])
                    ->assertJson([
                        'message' => __('auth.verification.conflict'),
                        'verified' => false
                    ]);

                // Ensure the user remains verified
                self::assertTrue($user->refresh()->hasVerifiedEmail());

                return true;
            }
        );
    }

    /** @test */
    public function can_request_password_reset_email()
    {
        /** @var User $user */
        $user = factory('App\User')->create();

        // Intercept notifications
        Notification::fake();

        // Request password reset email
        self::post("/api/v1/auth/password/email?email={$user->email}")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['message', 'sent'])
            ->assertJson([
                'message' => __(PasswordBroker::RESET_LINK_SENT),
                'sent' => true
            ]);

        // Ensure that the user receives the notification
        Notification::assertSentTo($user, ResetPassword::class);
    }

    /** @test */
    public function cannot_receive_password_reset_email_for_unknown_email()
    {
        // Intercept notifications
        Notification::fake();

        // Request password reset email
        self::post("/api/v1/auth/password/email?email={$this->faker->unique()->safeEmail}")
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure(['message', 'sent', 'errors' => ['email']])
            ->assertJson([
                'message' => __(PasswordBroker::INVALID_USER),
                'sent' => false
            ])
            ->assertJsonValidationErrors(['email' => __(PasswordBroker::INVALID_USER)]);

        // Ensure that a notification was not sent
        Notification::assertNothingSent();
    }

    /** @test */
    public function can_reset_password_via_email()
    {
        /** @var User $user new user with known password */
        $user = factory('App\User')->create(['password' => Hash::make('secret123')]);

        // Intercept notifications
        Notification::fake();

        // Request password reset email
        self::post("/api/v1/auth/password/email?email={$user->email}")->assertStatus(Response::HTTP_OK);

        // Intercept the notification and extract the verification url, then
        // try to use the url to reset the password for the user
        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification, array $channel, User $notifiable) use ($user) {
                // Extract the password reset token from the email
                $content = $notification->toMail($notifiable)->render();
                preg_match('/\/password\/([A-Za-z0-9]+)/', $content, $url);

                // Attempt to use the token to reset the user's password
                $data = [
                    'token' => $url[1],
                    'email' => $user->email,
                    'password' => '123secret',
                    'password_confirmation' => '123secret'
                ];

                self::post('/api/v1/auth/password/reset', $data)
                    ->assertStatus(Response::HTTP_OK)
                    ->assertJsonStructure(['message', 'reset'])
                    ->assertJson([
                        'message' => __(PasswordBroker::PASSWORD_RESET),
                        'reset' => true
                    ]);

                // Ensure the user's new password persisted
                self::assertTrue(Hash::check('123secret', $user->refresh()->password));

                return true;
            }
        );
    }

    /** @test */
    public function cannot_reset_password_with_invalid_token()
    {
        /** @var User $user new user with known password */
        $user = factory('App\User')->create(['password' => Hash::make('secret123')]);

        // Attempt to use an invalid token to reset the user's password
        $data = [
            'token' => Str::random(60),
            'email' => $user->email,
            'password' => '123secret',
            'password_confirmation' => '123secret'
        ];

        self::post('/api/v1/auth/password/reset', $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure(['message', 'reset'])
            ->assertJson([
                'message' => __(PasswordBroker::INVALID_TOKEN),
                'reset' => false
            ]);

        // Ensure the user's old password remains
        self::assertTrue(Hash::check('secret123', $user->refresh()->password));
    }

    /** @test */
    public function cannot_reset_password_via_email_with_expired_link()
    {
        /** @var User $user new user with known password */
        $user = factory('App\User')->create(['password' => Hash::make('secret123')]);

        // Intercept notifications
        Notification::fake();

        // Temporarily change the password link expiry time to instant
        $expiry = config('auth.passwords.users.expire');
        Config::set('auth.passwords.users.expire', 0);

        // Request password reset email
        self::post("/api/v1/auth/password/email?email={$user->email}")->assertStatus(Response::HTTP_OK);

        // Intercept the notification and extract the verification url, then
        // try to use the url to reset the password for the user
        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification, array $channel, User $notifiable) use ($user) {
                // Extract the password reset token from the email
                $content = $notification->toMail($notifiable)->render();
                preg_match('/\/password\/([A-Za-z0-9]+)/', $content, $url);

                // Attempt to use the token to reset the user's password
                $data = [
                    'token' => $url[1],
                    'email' => $user->email,
                    'password' => '123secret',
                    'password_confirmation' => '123secret'
                ];

                self::post('/api/v1/auth/password/reset', $data)
                    ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->assertJsonStructure(['message', 'reset'])
                    ->assertJson([
                        'message' => __(PasswordBroker::INVALID_TOKEN),
                        'reset' => false
                    ])
                    ->assertJsonValidationErrors(['email' => __(PasswordBroker::INVALID_TOKEN)]);

                // Ensure the user's old password remains
                self::assertTrue(Hash::check('secret123', $user->refresh()->password));

                return true;
            }
        );

        // Revert changes to the password link expiry time
        Config::set('auth.passwords.users.expire', $expiry);
    }
}
