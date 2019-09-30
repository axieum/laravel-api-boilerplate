<?php

namespace App\Http\Controllers\Api\v1\Abilities;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Ability as AbilityResource;
use Illuminate\Http\JsonResponse;
use Silber\Bouncer\Database\Ability;
use Spatie\QueryBuilder\QueryBuilder;

class AbilityController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('can:index,\Silber\Bouncer\Database\Ability')->only('index');
        $this->middleware('can:read,ability')->only('show');
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $abilities = QueryBuilder::for(Ability::class)
            ->allowedSorts(['id', 'name', 'title', 'only_owned', 'scope', 'updated_at', 'created_at'])
            ->allowedFilters(['only_owned', 'scope', 'updated_at', 'created_at'])
            ->jsonPaginate();

        return AbilityResource::collection($abilities)->response();
    }

    /**
     * Display the specified resource.
     *
     * @param Ability $ability
     * @return JsonResponse
     */
    public function show(Ability $ability)
    {
        return response()->json(new AbilityResource($ability));
    }
}
