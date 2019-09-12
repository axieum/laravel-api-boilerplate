<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * User JSON Resource.
 *
 * @property \App\User   $resource
 * @property-read int    $id
 * @property-read string $name
 * @property-read string $email
 * @property-read Carbon $email_verified_at
 * @property-read Carbon $updated_at
 * @property-read Carbon $created_at
 */
class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        $canSeeEmail = $this->resource->wasRecentlyCreated ||
            $this->resource->getKey() == Auth::id();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($canSeeEmail, $this->email),
            'email_verified_at' => $this->email_verified_at,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
