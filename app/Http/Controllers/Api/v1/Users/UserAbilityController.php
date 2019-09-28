<?php

namespace App\Http\Controllers\Api\v1\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Ability as AbilityResource;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Ability;
use Spatie\QueryBuilder\QueryBuilder;

class UserAbilityController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('can:index.ability,user')->only('index');
        $this->middleware(['can:allow,user', 'can:allow,ability'])->only('attach');
        $this->middleware(['can:disallow,user', 'can:disallow,ability'])->only('detach');
    }

    /**
     * Display a listing of the resource.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function index(User $user)
    {
        $abilities = QueryBuilder::for($user->abilities()->getQuery())
            ->allowedSorts(['id', 'name', 'title', 'only_owned', 'scope', 'updated_at', 'created_at'])
            ->allowedFilters(['only_owned', 'scope', 'updated_at', 'created_at'])
            ->jsonPaginate();

        return AbilityResource::collection($abilities)->response();
    }

    /**
     * Allow/forbid an ability on the user.
     *
     * @param User    $user
     * @param Ability $ability
     * @return JsonResponse
     */
    public function attach(User $user, Ability $ability)
    {
        $data = request()->validate(['forbid' => ['sometimes', 'boolean']]);
        $forbid = Arr::get($data, 'forbid', false);

        Bouncer::{$forbid ? 'forbid' : 'allow'}($user)->to($ability);
        Bouncer::refresh();

        return response()->json(['message' => __($forbid ? 'users.forbid' : 'users.allowed')]);
    }

    /**
     * Disallow/unforbid an ability from the user.
     *
     * @param User    $user
     * @param Ability $ability
     * @return JsonResponse
     */
    public function detach(User $user, Ability $ability)
    {
        Bouncer::disallow($user)->to($ability);
        Bouncer::refresh();

        return response()->json(['message' => __('users.disallowed')]);
    }
}
