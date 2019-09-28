<?php

namespace App\Http\Controllers\Api\v1\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Role as RoleResource;
use App\User;
use Illuminate\Http\JsonResponse;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Role;
use Spatie\QueryBuilder\QueryBuilder;

class UserRoleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('can:index.role,user')->only('index');
        $this->middleware(['can:assign,user', 'can:assign,role'])->only('assign');
        $this->middleware(['can:retract,user', 'can:retract,role'])->only('retract');
    }

    /**
     * Display a listing of the resource.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function index(User $user)
    {
        $roles = QueryBuilder::for($user->roles()->getQuery())
            ->allowedSorts(['id', 'name', 'title', 'level', 'scope', 'updated_at', 'created_at'])
            ->allowedFilters(['level', 'scope', 'updated_at', 'created_at'])
            ->jsonPaginate();

        return RoleResource::collection($roles)->response();
    }

    /**
     * Assign a role to the user.
     *
     * @param User $user
     * @param Role $role
     * @return JsonResponse
     */
    public function assign(User $user, Role $role)
    {
        Bouncer::assign($role)->to($user);
        Bouncer::refresh();

        return response()->json(['message' => __('users.assigned')]);
    }

    /**
     * Retract a role from the user.
     *
     * @param User $user
     * @param Role $role
     * @return JsonResponse
     */
    public function retract(User $user, Role $role)
    {
        Bouncer::retract($role)->from($user);
        Bouncer::refresh();

        return response()->json(['message' => __('users.retracted')]);
    }
}
