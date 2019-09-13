<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\v1\User as UserResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * User Notification JSON Resource.
 *
 * @property DatabaseNotification $resource
 * @property-read string          $id
 * @property-read string          $type
 * @property-read array           $data
 * @property-read int             $notifiable_id
 * @property-read Carbon          $read_at
 * @property-read Carbon          $updated_at
 * @property-read Carbon          $created_at
 */
class UserNotification extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'user_id' => $this->when(!$this->resource->relationLoaded('notifiable'),
                $this->notifiable_id),
            'user' => new UserResource($this->whenLoaded('notifiable')),
            'content' => $this->data,
            'read_at' => $this->read_at,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
