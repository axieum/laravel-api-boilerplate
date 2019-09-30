<?php

namespace App\Http\Controllers\Api\v1\Abilities;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User as UserResource;
use Illuminate\Http\JsonResponse;
use Silber\Bouncer\Database\Ability;
use Spatie\QueryBuilder\AllowedFilter as QueryFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AbilityUserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('can:index-users,ability')->only('index');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Ability $ability
     * @return JsonResponse
     */
    public function index(Ability $ability)
    {
        $users = QueryBuilder::for($ability->users()->getQuery())
            ->allowedSorts(['id', 'username', 'verified_at', 'updated_at', 'created_at'])
            ->allowedFilters(['updated_at', 'created_at', QueryFilter::scope('verified')])
            ->jsonPaginate();

        return UserResource::collection($users)->response();
    }
}
