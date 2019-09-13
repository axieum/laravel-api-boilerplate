<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
