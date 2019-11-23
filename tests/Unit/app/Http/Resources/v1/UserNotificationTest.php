<?php

namespace Tests\Unit\app\Http\Resources\v1;

use App\Http\Resources\v1\UserNotification as UserNotificationResource;
use App\Notifications\v1\SimpleNotification;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserNotificationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var User user receiving notification
     */
    private $user;

    /**
     * @var SimpleNotification sending notification
     */
    private $notification;

    protected function setUp(): void
    {
        parent::setUp();

        // Prepare user with a notification
        $this->user = factory('App\User')->create();
        $this->notification = factory(SimpleNotification::class)->raw()[0];
        $this->user->notify($this->notification);
    }


    /** @test */
    public function can_see_relevant_fields()
    {
        // Get user notification
        $user_notification = $this->user->notifications()->latest()->first();

        /** @noinspection PhpParamsInspection */
        self::createTestResponse((new UserNotificationResource($user_notification))->response())
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'user_id',
                    'content',
                    'read_at',
                    'updated_at',
                    'created_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'user_id' => $this->user->id,
                    'content' => $this->notification->toArray($this->user)
                ]
            ]);
    }

    /** @test */
    public function can_see_user_instance_when_eager_loaded()
    {
        // Get user notification with user
        $user_notification = $this->user->notifications()->with('notifiable')->latest()->first();

        /** @noinspection PhpParamsInspection */
        self::createTestResponse((new UserNotificationResource($user_notification))->response())
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'user' => [],
                    'content',
                    'read_at',
                    'updated_at',
                    'created_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                    ],
                    'content' => $this->notification->toArray($this->user)
                ]
            ]);
    }
}
