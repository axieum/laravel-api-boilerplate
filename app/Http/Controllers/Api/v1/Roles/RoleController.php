<?php

namespace App\Http\Controllers\Api\v1\Roles;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Role as RoleResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Role;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('can:index,\Silber\Bouncer\Database\Role')->only('index');
        $this->middleware('can:create,\Silber\Bouncer\Database\Role')->only('store');
        $this->middleware('can:read,role')->only('show');
        $this->middleware('can:update,role')->only('update');
        $this->middleware('can:delete,role')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $roles = QueryBuilder::for(Role::class)
            ->allowedSorts(['id', 'name', 'title', 'level', 'scope', 'updated_at', 'created_at'])
            ->allowedFilters(['level', 'scope', 'updated_at', 'created_at'])
            ->jsonPaginate();

        return RoleResource::collection($roles)->response();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function store()
    {
        $data = request()->validate([
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'level' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        $role = Bouncer::role()->create([
            'name' => strtolower($data['name']),
            'title' => $data['title'],
            'level' => $data['level'] ?? null,
        ]);

        Bouncer::refresh();

        return response()->json([
            'message' => __('roles.created'),
            'role' => new RoleResource($role)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param Role $role
     * @return JsonResponse
     */
    public function show(Role $role)
    {
        return response()->json(new RoleResource($role));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Role $role
     * @return JsonResponse
     */
    public function update(Role $role)
    {
        $data = request()->validate([
            'name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'level' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        // NAME
        if (array_key_exists('name', $data))
            $role->name = strtolower($data['name']);

        // TITLE
        if (array_key_exists('title', $data))
            $role->title = $data['title'];

        // LEVEL
        if (array_key_exists('level', $data))
            $role->level = $data['level'];

        $role->save();

        Bouncer::refresh();

        return response()->json([
            'message' => __('roles.updated'),
            'role' => new RoleResource($role)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Role $role
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Role $role)
    {
        $role->delete();

        Bouncer::refresh();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
