<?php

namespace Tests\Unit\app\Http\Resources\v1;

use App\Http\Resources\v1\UserNotification as UserNotificationResource;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

class UserNotificationTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /** @test */
    public function can_see_relevant_fields()
    {
        /** @var User $user user receiving notification */
        $user = factory('App\User')->create();

        $notification = new TestNotification(
            $this->faker->sentence(3),
            $this->faker->realText()
        );

        // Notify the user of the test notification
        $user->notify($notification);

        // Get user notification
        $user_notification = $user->notifications()->latest()->first();

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
                    'user_id' => $user->id,
                    'content' => $notification->toArray($user)
                ]
            ]);
    }

    /** @test */
    public function can_see_user_instance_when_eager_loaded()
    {
        /** @var User $user user receiving notification */
        $user = factory('App\User')->create();

        $notification = new TestNotification(
            $this->faker->sentence(3),
            $this->faker->realText()
        );

        // Notify the user of the test notification
        $user->notify($notification);

        // Get user notification with user
        $user_notification = DatabaseNotification::with('notifiable')->latest()->first();

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
                        'id' => $user->id,
                        'name' => $user->name,
                    ],
                    'content' => $notification->toArray($user)
                ]
            ]);
    }
}

class TestNotification extends Notification
{
    /**
     * @var string title of the notification
     */
    private $title;

    /**
     * @var string body text of the notification
     */
    private $body;

    /**
     * Construct a new Test Notification.
     *
     * @param string $title
     * @param string $body
     */
    public function __construct(string $title, string $body)
    {
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
