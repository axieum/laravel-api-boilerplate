<?php

namespace App\Http\Controllers\Api\v1\Abilities;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Role as RoleResource;
use Illuminate\Http\JsonResponse;
use Silber\Bouncer\Database\Ability;
use Spatie\QueryBuilder\QueryBuilder;

class AbilityRoleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('can:index.role,ability')->only('index');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Ability $ability
     * @return JsonResponse
     */
    public function index(Ability $ability)
    {
        $roles = QueryBuilder::for($ability->roles()->getQuery())
            ->allowedSorts(['id', 'name', 'title', 'level', 'scope', 'updated_at', 'created_at'])
            ->allowedFilters(['level', 'scope', 'updated_at', 'created_at'])
            ->jsonPaginate();

        return RoleResource::collection($roles)->response();
    }
}
