<?php

namespace App\Notifications\v1;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SimpleNotification extends Notification
{
    use Queueable;

    /**
     * @var string title of the notification
     */
    public $title;

    /**
     * @var string body text of the notification
     */
    public $body;

    /**
     * Create a new simple notification instance.
     *
     * @param string $title notification title
     * @param string $body  notification body
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
            'body' => $this->body
        ];
    }
}
