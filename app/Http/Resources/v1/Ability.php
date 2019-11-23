<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Ability JSON Resource.
 *
 * @property \Silber\Bouncer\Database\Ability $resource
 * @property-read int                         $id
 * @property-read string                      $name
 * @property-read string                      $title
 * @property-read bool                        $only_owned
 * @property-read bool                        $forbidden
 * @property-read array                       $options
 * @property-read int                         $scope
 * @property-read Carbon                      $updated_at
 * @property-read Carbon                      $created_at
 */
class Ability extends JsonResource
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
            'only_owned' => !!$this->only_owned,
            'forbidden' => !!$this->forbidden,
            'options' => $this->options,
            'scope' => $this->scope,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
            'roles' => Role::collection($this->whenLoaded('roles')),
            'users' => User::collection($this->whenLoaded('users')),
        ];
    }
}
