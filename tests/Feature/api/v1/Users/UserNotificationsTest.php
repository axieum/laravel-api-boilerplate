<?php


namespace Tests\Feature\api\v1\Users;


use App\Notifications\v1\SimpleNotification;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class UserNotificationsTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * @var User user whom is initialised with notifications
     */
    private $passive;

    /**
     * @var int how many notifications to generate
     */
    private $count = 50;

    protected function setUp(): void
    {
        parent::setUp();

        // Prepare a user with some notifications
        $this->passive = factory('App\User')->create();

        /** @var array|SimpleNotification $notifications generated notifications */
        $notifications = factory(SimpleNotification::class, $this->count)->raw();
        foreach ($notifications as $notification)
            $this->passive->notify($notification[0]);

        // Randomly read/unread notifications
        $this->passive->notifications()->each(function ($notification) {
            $notification->{$this->faker->boolean ? 'markAsRead' : 'markAsUnread'}();
        });
    }

    /** @test */
    public function can_index_notifications()
    {
        /** @var User $active user whom can index other users notifications */
        $active = factory('App\User')->create();
        $active->allow('index.notification', User::class);

        /** @var int $perPage how many notifications per page to request */
        $perPage = 10;

        // Request notifications
        self::actingAs($active)
            ->get("/api/v1/users/{$this->passive->id}/notifications?page[size]=${perPage}")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'type',
                        'user_id',
                        'content' => [],
                        'read_at',
                        'updated_at',
                        'created_at'
                    ]
                ],
                'links' => [],
                'meta' => []
            ])
            ->assertJsonCount(
                $perPage > $this->count ? $this->count : $perPage,
                'data'
            ) // per page requested
            ->assertJson([
                'meta' => [
                    'per_page' => $perPage, // per page requested
                    'total' => $this->count // notifications sent
                ]
            ]);
    }

    /** @test */
    public function can_index_read_notifications()
    {
        /** @var User $active user whom can index other users notifications */
        $active = factory('App\User')->create();
        $active->allow('index.notification', User::class);

        $count_read = $this->passive->readNotifications()->count();
        $count_unread = $this->passive->unreadNotifications()->count();

        // Request read notifications
        self::actingAs($active)
            ->get("/api/v1/users/{$this->passive->id}/notifications?filter[read]=true")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [[]],
                'links' => [],
                'meta' => []
            ])
            ->assertJsonMissing(['data' => [['read_at' => null]]])
            ->assertJson(['meta' => ['total' => $count_read]]);

        // Request unread notifications
        self::actingAs($active)
            ->get("/api/v1/users/{$this->passive->id}/notifications?filter[read]=false")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [[]],
                'links' => [],
                'meta' => []
            ])
            ->assertJson([
                'data' => [['read_at' => null]],
                'meta' => ['total' => $count_unread]
            ]);
    }

    /** @test */
    public function can_index_own_notifications()
    {
        // Request notifications
        self::actingAs($this->passive)
            ->get("/api/v1/users/{$this->passive->id}/notifications")
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['meta' => ['total' => $this->count]]);
    }

    /** @test */
    public function cannot_index_notifications_without_ability()
    {
        /** @var User $active user whom can index other users notifications */
        $active = factory('App\User')->create();

        self::actingAs($active)
            ->get("/api/v1/users/{$this->passive->id}/notifications")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function can_retrieve_a_notification()
    {
        /** @var User $active user whom can read any notification */
        $active = factory('App\User')->create();
        $active->allow('read', DatabaseNotification::class);

        /** @var DatabaseNotification $notification random unread notification */
        $notification = $this->passive->unreadNotifications()->inRandomOrder()->first();

        self::actingAs($active)
            ->get("/api/v1/users/{$this->passive->id}/notifications/{$notification->getKey()}")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'id',
                'type',
                'user_id',
                'content' => [],
                'read_at',
                'updated_at',
                'created_at'
            ])
            ->assertJson([
                'id' => $notification->id,
                'user_id' => $this->passive->id,
                'content' => $notification->data
            ])
            ->assertJsonMissing(['read_at' => null]); // is read upon retrieval
    }

    /** @test */
    public function can_retrieve_own_notification()
    {
        /** @var DatabaseNotification $notification random unread notification */
        $notification = $this->passive->unreadNotifications()->inRandomOrder()->first();

        self::actingAs($this->passive)
            ->get("/api/v1/users/{$this->passive->id}/notifications/{$notification->getKey()}")
            ->assertStatus(Response::HTTP_OK);
    }

    /** @test */
    public function cannot_retrieve_notification_without_ability()
    {
        /** @var User $active user whom cannot read others' notifications */
        $active = factory('App\User')->create();

        /** @var DatabaseNotification $notification random unread notification */
        $notification = $this->passive->unreadNotifications()->inRandomOrder()->first();

        self::actingAs($active)
            ->get("/api/v1/users/{$this->passive->id}/notifications/{$notification->getKey()}")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function can_delete_a_notification()
    {
        /** @var User $active user whom can delete any notification */
        $active = factory('App\User')->create();
        $active->allow('delete', DatabaseNotification::class);

        /** @var DatabaseNotification $notification random notification */
        $notification = $this->passive->notifications()->inRandomOrder()->first();

        self::actingAs($active)
            ->delete("/api/v1/users/{$this->passive->id}/notifications/{$notification->getKey()}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        // Ensure the notification does not exist
        self::assertDatabaseMissing('notifications', $notification->only(['id', 'notifiable_id']));
    }

    /** @test */
    public function can_delete_own_notification()
    {
        /** @var DatabaseNotification $notification random notification */
        $notification = $this->passive->notifications()->inRandomOrder()->first();

        self::actingAs($this->passive)
            ->delete("/api/v1/users/{$this->passive->id}/notifications/{$notification->getKey()}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        // Ensure the notification does not exist
        self::assertDatabaseMissing('notifications', $notification->only(['id', 'notifiable_id']));
    }

    /** @test */
    public function cannot_delete_notification_without_ability()
    {
        /** @var User $active user whom cannot delete others' notifications */
        $active = factory('App\User')->create();

        /** @var DatabaseNotification $notification random notification */
        $notification = $this->passive->notifications()->inRandomOrder()->first();

        self::actingAs($active)
            ->delete("/api/v1/users/{$this->passive->id}/notifications/{$notification->getKey()}")
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Ensure the notification still exists
        self::assertDatabaseHas('notifications', $notification->only(['id', 'notifiable_id']));
    }

    /** @test */
    public function can_mark_notification()
    {
        /** @var User $active user whom can mark any notification */
        $active = factory('App\User')->create();
        $active->allow('mark', DatabaseNotification::class);

        /** @var DatabaseNotification $notification random unread notification */
        $notification = $this->passive->notifications()->inRandomOrder()->first();

        $data = [
            'read' => !$notification->read() // switch read status
        ];

        self::actingAs($active)
            ->patch("/api/v1/users/{$this->passive->id}/notifications/{$notification->getKey()}", $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['message', 'read'])
            ->assertJson([
                'message' => __('notifications.' . $data['read'] ? 'read' : 'unread'),
                'read' => $data['read']
            ]);

        // Ensure read status changed
        self::assertEquals($data['read'], $notification->refresh()->read());
    }

    /** @test */
    public function can_mark_own_notification()
    {
        /** @var DatabaseNotification $notification random unread notification */
        $notification = $this->passive->notifications()->inRandomOrder()->first();

        $data = [
            'read' => !$notification->read() // switch read status
        ];

        self::actingAs($this->passive)
            ->patch("/api/v1/users/{$this->passive->id}/notifications/{$notification->getKey()}", $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['message', 'read'])
            ->assertJson([
                'message' => __('notifications.' . $data['read'] ? 'read' : 'unread'),
                'read' => $data['read']
            ]);

        // Ensure read status changed
        self::assertEquals($data['read'], $notification->refresh()->read());
    }

    /** @test */
    public function cannot_mark_notification_without_ability()
    {
        /** @var User $active user whom cannot mark others' notifications */
        $active = factory('App\User')->create();

        /** @var DatabaseNotification $notification random unread notification */
        $notification = $this->passive->notifications()->inRandomOrder()->first();

        $data = [
            'read' => !$notification->read() // switch read status
        ];

        self::actingAs($active)
            ->patch("/api/v1/users/{$this->passive->id}/notifications/{$notification->getKey()}", $data)
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Ensure read status did not change
        self::assertNotEquals($data['read'], $notification->refresh()->read());
    }
}
