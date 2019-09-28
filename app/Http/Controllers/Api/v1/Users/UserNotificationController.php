<?php

namespace App\Http\Controllers\Api\v1\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\UserNotification as UserNotificationResource;
use App\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;
use Spatie\QueryBuilder\AllowedFilter as QueryFilter;
use Spatie\QueryBuilder\Filters\Filter;
use Spatie\QueryBuilder\QueryBuilder;

class UserNotificationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:index.notification,user')->only('index');
        $this->middleware('can:read,notification')->only('show');
        $this->middleware('can:mark,notification')->only('mark');
        $this->middleware('can:delete,notification')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function index(User $user)
    {
        $notifications = QueryBuilder::for($user->notifications()->getQuery())
            ->allowedSorts(['type', 'read_at', 'created_at'])
            ->allowedFilters([
                'type', 'notifiable_type', 'created_at',
                QueryFilter::custom('read', new DatabaseNotificationReadFilter()),
            ])
            ->jsonPaginate();

        return UserNotificationResource::collection($notifications)->response();
    }

    /**
     * Display the specified resource.
     *
     * @param mixed                $user
     * @param DatabaseNotification $notification
     * @return JsonResponse
     */
    public function show($user, DatabaseNotification $notification)
    {
        $notification->markAsRead(); // NB: Assuming read on retrieval

        return response()->json(new UserNotificationResource($notification));
    }

    /**
     * Mark the specified resource as read/unread.
     *
     * @param mixed                $user
     * @param DatabaseNotification $notification
     * @return JsonResponse
     */
    public function mark($user, DatabaseNotification $notification)
    {
        $data = request()->validate([
            'read' => ['sometimes', 'boolean']
        ]);

        $mark = !$notification->read();
        if (array_key_exists('read', $data))
            $mark = $data['read'];

        // Mark notification
        $notification->{$mark ? 'markAsRead' : 'markAsUnread'}();

        return response()->json([
            'message' => __('notifications.' . $mark ? 'read' : 'unread'),
            'read' => $mark
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param mixed                $user
     * @param DatabaseNotification $notification
     * @return JsonResponse
     * @throws Exception
     */
    public
    function destroy($user, DatabaseNotification $notification)
    {
        $notification->delete();

        return response()->json(null, 204);
    }
}

class DatabaseNotificationReadFilter implements Filter
{
    /**
     * Scope a query to filter the read status.
     *
     * @param Builder $query
     * @param mixed   $value
     * @param string  $property
     * @return Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        return $query->{$value ? 'whereNotNull' : 'whereNull'}('read_at');
    }
}
