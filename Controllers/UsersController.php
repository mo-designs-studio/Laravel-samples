<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CreateWebUserRequest;
use App\Http\Requests\Web\UpdateWebUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Http\Resources\Web\Notifications\NotificationResource;
use App\Http\Traits\UploadHandler;
use App\Model\Properties\PropertyAd;
use App\Model\User\User;
use Carbon\Carbon;
use Firebase\Auth\Token\Exception\ExpiredToken;
use Firebase\Auth\Token\Verifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    use UploadHandler;

    public function index()
    {
        return !\request()->user('sanctum')->isUser()
            ? response()->json(['message' => trans('global.invalid_credentials')], 401)
            : response()->json(['user' => new UserResource(\request()->user('sanctum'))]);
    }

    public function store(CreateWebUserRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::query()->create([
                'role_id' => $request->user_Type == 'agent' ? 4 : 7,
                'email' => $request->input('email'),
                'country_id' => $request->input('country_id'),
                'mobile' => $request->input('mobile'),
                'username' => $request->input('username'),
                'password' => bcrypt($request->input('password'))
            ]);

            $user->userAttributes()->create([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
            ]);

            $this->uploadUserProfile($user);

            DB::commit();

            return response()->json([
                'status' => true,
                'user' => new UserResource($user),
                'token' => $user->createToken(Carbon::now()->timestamp)->plainTextToken
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function update(UpdateWebUserRequest $request)
    {
        $user = \auth()->user();
        $request->user('sanctum')->userAttributes()->update([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
        ]);

        if (!is_null($request->input('email')) && !$request->user('sanctum')->email_verified_at) {
            $request->user('sanctum')->update(['email' => $request->input('email')]);
            if (!$user->contacts()->where(['contact_method_id' => '3', 'value' => $request->input('email')])->exists()) $user->contacts()->create([
                'contact_method_id' => '3',
                'value' => $request->input('email')
            ]);
        }

        if (!is_null($request->input('mobile')) && !$request->user('sanctum')->mobile_verified_at) {
            $request->user('sanctum')->update([
                'mobile' => $request->input('mobile'),
                'country_id' => $request->input('country_id'),
            ]);
        }

        if ($request->file('profile_image'))
            $this->uploadUserProfile($request->user('sanctum'));

        return response()->json([
            'status' => true,
            'user' => new UserResource($request->user('sanctum'))
        ]);
    }

    public function markUserNotificationsAsRead()
    {
        $user = User::find(Auth::guard('sanctum')->id());
        if ($user) $user->unreadNotifications->markAsRead();
    }

    public function getUserNotifications()
    {
        $user = User::find(Auth::guard('sanctum')->id());
        if (!$user) return response()->json(['message' => 'unauthorized'], 401);
        $notifications = $user->notifications;
        return response()->json(['notifications' => NotificationResource::collection($notifications)], 200);
    }

    public function getAgents()
    {
        $agents = User::query()->where(['role_id' => User::Agent])->activeAgent()->latest()->limit(10)->get();
        return response()->json(['lastAgents' => UserResource::collection($agents)], 200);
    }

    public function getAllAgents()
    {
        $agents = User::query()->agent()->activeAgent()->paginate(2);

        return response()->json(['agents' => UserResource::collection($agents),
            'current_page' => $agents->currentPage(),
            'first_item' => $agents->firstItem(),
            'last_item' => $agents->lastItem(),
            'total' => $agents->total(),
            'next_page_url' => $agents->nextPageUrl(),
            'prev_page_url' => $agents->previousPageUrl(),
            'last_page' => $agents->lastPage(),
            'more_page' => $agents->hasMorePages(),
            'url_range' => $agents->getUrlRange(1, $agents->lastPage()),], 200);
    }

    public function showAgent($uuid)
    {
        $user = User::getUserByUuid($uuid);
        $agent = User::query()->agent()->activeAgent()->where(['id' => $user->id])->first();
        $ads = PropertyAd::query()->where(['user_id' => $user->id])->status()->expiry()->latest()->paginate(10);
        $adsResponse = PropertyAd::prepareResponse($ads);
        return (response()->json(['agent' => new UserResource($agent), 'propertiesAds' => $adsResponse]));
    }
}
