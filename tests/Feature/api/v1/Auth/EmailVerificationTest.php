<?php

namespace Tests\Feature\api\v1\Auth;

use App\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use DatabaseTransactions;

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
            ->assertOk()
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
        self::actingAs($user)->post('/api/v1/auth/verify/resend')->assertOk();

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
                    ->assertOk()
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
        self::actingAs($user)->post('/api/v1/auth/verify/resend')->assertOk();

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
}
