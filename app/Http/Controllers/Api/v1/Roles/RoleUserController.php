<?php

namespace App\Http\Controllers\Api\v1\Roles;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\user as userResource;
use App\User;
use Illuminate\Http\JsonResponse;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Role;
use Spatie\QueryBuilder\AllowedFilter as QueryFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoleUserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('can:index-users,role')->only('index');
        $this->middleware(['can:assign,role', 'can:assign,user'])->only('assign');
        $this->middleware(['can:retract,role', 'can:retract,user'])->only('retract');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Role $role
     * @return JsonResponse
     */
    public function index(Role $role)
    {
        $users = QueryBuilder::for($role->users()->getQuery())
            ->allowedSorts(['id', 'username', 'verified_at', 'updated_at', 'created_at'])
            ->allowedFilters(['updated_at', 'created_at', QueryFilter::scope('verified')])
            ->jsonPaginate();

        return UserResource::collection($users)->response();
    }

    /**
     * Assign user(s) the role.
     *
     * @param Role $role
     * @param User $user
     * @return JsonResponse
     */
    public function assign(Role $role, User $user)
    {
        Bouncer::assign($role)->to($user);
        Bouncer::refresh();

        return response()->json(['message' => __('roles.assigned')]);
    }

    /**
     * Retract user(s) from the role.
     *
     * @param Role $role
     * @param User $user
     * @return JsonResponse
     */
    public function retract(Role $role, User $user)
    {
        Bouncer::retract($role)->from($user);
        Bouncer::refresh();

        return response()->json(['message' => __('roles.retracted')]);
    }
}
