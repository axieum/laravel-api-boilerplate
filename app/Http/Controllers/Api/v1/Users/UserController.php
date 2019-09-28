<?php

namespace App\Http\Controllers\Api\v1\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User as UserResource;
use App\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Spatie\QueryBuilder\AllowedFilter as QueryFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->only('current');
        $this->middleware('can:index,App\User')->only('index');
        $this->middleware('can:create,App\User')->only('store');
        $this->middleware('can:view,user')->only('show');
        $this->middleware('can:update,user')->only('update');
        $this->middleware('can:delete,user')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $users = QueryBuilder::for(User::class)
            ->allowedSorts(['id', 'name', 'verified_at', 'updated_at', 'created_at'])
            ->allowedFilters(['updated_at', 'created_at', QueryFilter::scope('verified')])
            ->jsonPaginate();

        return UserResource::collection($users)->response();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function store()
    {
        $data = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'verified' => ['sometimes', 'boolean', function ($attribute, $value, $fail) {
                // Setting verified to 'false' is allowed, but 'true' needs authorization
                if ($value && !Bouncer::can('verify', User::class))
                    $fail(__('validation.custom.bouncer.verify_user', ['attribute' => $attribute]));
            }],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'email_verified_at' => Arr::get($data, 'verified', false) ? now() : null,
        ]);

        return response()->json([
            'message' => __('users.created'),
            'user' => new UserResource($user)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user)
    {
        return response()->json(new UserResource($user));
    }

    /**
     * Display the resource for the currently authenticated user.
     *
     * @return JsonResponse
     */
    public function current()
    {
        return response()->json(new UserResource(request()->user()));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function update(User $user)
    {
        $data = request()->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'verified' => ['sometimes', 'boolean', function ($attribute, $value, $fail) use ($user) {
                if (!Bouncer::can('verify', $user))
                    $fail(__('validation.custom.bouncer.verify_user', ['attribute' => $attribute]));
            }],
        ]);

        // NAME
        if (array_key_exists('name', $data))
            $user->name = $data['name'];

        // EMAIL
        if (array_key_exists('email', $data)) {
            $user->email = strtolower($data['email']);
            $user->email_verified_at = null; // Require re-verification

            $user->sendEmailVerificationNotification();
        }

        // PASSWORD
        if (array_key_exists('password', $data))
            $user->password = Hash::make($data['password']);

        // VERIFIED (NB: authorized through validation)
        if (array_key_exists('verified', $data))
            $user->email_verified_at = $data['verified'] ? now() : null;

        $user->save();

        return response()->json([
            'message' => __('users.updated'),
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
