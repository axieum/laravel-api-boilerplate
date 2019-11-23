<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Role JSON Resource.
 *
 * @property \Silber\Bouncer\Database\Role $resource
 * @property-read int                      $id
 * @property-read string                   $name
 * @property-read string                   $title
 * @property-read int                      $level
 * @property-read int                      $scope
 * @property-read Carbon                   $updated_at
 * @property-read Carbon                   $created_at
 */
class Role extends JsonResource
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
            'name' => $this->name,
            'title' => $this->title,
            'level' => $this->level,
            'scope' => $this->scope,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
            'abilities' => Ability::collection($this->whenLoaded('abilities')),
            'users' => User::collection($this->whenLoaded('users')),
        ];
    }
}
