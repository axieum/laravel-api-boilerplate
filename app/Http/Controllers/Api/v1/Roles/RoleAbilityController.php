<?php

namespace App\Http\Controllers\Api\v1\Roles;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Ability as AbilityResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Role;
use Spatie\QueryBuilder\QueryBuilder;

class RoleAbilityController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('can:index.ability,role')->only('index');
        $this->middleware(['can:allow,role', 'can:allow,ability'])->only('attach');
        $this->middleware(['can:disallow,role', 'can:disallow,ability'])->only('detach');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Role $role
     * @return JsonResponse
     */
    public function index(Role $role)
    {
        $abilities = QueryBuilder::for($role->abilities()->getQuery())
            ->allowedSorts(['id', 'name', 'title', 'only_owned', 'scope', 'updated_at', 'created_at'])
            ->allowedFilters(['only_owned', 'scope', 'updated_at', 'created_at'])
            ->jsonPaginate();

        return AbilityResource::collection($abilities)->response();
    }

    /**
     * Allow/forbid an ability on the role.
     *
     * @param Role    $role
     * @param Ability $ability
     * @return JsonResponse
     */
    public function attach(Role $role, Ability $ability)
    {
        $data = request()->validate(['forbid' => ['sometimes', 'boolean']]);
        $forbid = Arr::get($data, 'forbid', false);

        Bouncer::{$forbid ? 'forbid' : 'allow'}($role)->to($ability);
        Bouncer::refresh();

        return response()->json(['message' => __($forbid ? 'roles.forbid' : 'roles.allowed')]);
    }

    /**
     * Disallow/unforbid an ability from the role.
     *
     * @param Role    $role
     * @param Ability $ability
     * @return JsonResponse
     */
    public function detach(Role $role, Ability $ability)
    {
        Bouncer::disallow($role)->to($ability);
        Bouncer::refresh();

        return response()->json(['message' => __('roles.disallowed')]);
    }
}
