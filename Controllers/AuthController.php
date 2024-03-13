<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiSocialLogin;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\Web\ChangeUserPasswordRequest;
use App\Http\Requests\Web\ResetUserPasswordRequest;
use App\Http\Resources\Admin\UserResource;
use App\Http\Resources\loginResource;
use App\Http\Traits\SocialLogin;
use App\Model\User\User;
use Carbon\Carbon;
use Firebase\Auth\Token\Exception\ExpiredToken;
use Firebase\Auth\Token\Verifier;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AuthController extends Controller
{
    use SocialLogin;

    public function login(LoginRequest $request)
    {
        $searchableKey = \request()->input('searchableKey');
        $conditions = $searchableKey == "mobile" ?
            ['country_id' => $request->input('country_id'), $searchableKey => $request->input('identifier')] :
            [$searchableKey => $request->input('identifier')];
        $user = User::query()->where($conditions)->first();
        if ($user && Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'user' => new UserResource($user),
                'token' => $user->createToken(Carbon::now()->timestamp)->plainTextToken
            ], 200);
        }
        return response()->json([
            'message' => trans('global.invalid_credentials')
        ], 401);
    }

    public function verifyByToken(Request $request)
    {
        $project_id = env('FirebaseProjectId', 'aqar-max');
        $verifier = new Verifier($project_id);

        try {
            $verifiedIdToken = $verifier->verifyIdToken($request->input('token'));
            $phone = $verifiedIdToken->claims()->get('phone_number');
            if ($phone) {
                $user = User::query()->where('mobile_with_code', $phone)->where('id', auth()->id())->first();
                if ($user) {
                    $user->update(['mobile_verified_at' => Carbon::now()]);
                    $user->contacts()->create(['contact_method_id' => 2, 'value' => $phone]);
                    return response()->json([
                        'status' => true,
                        'message' => trans('global.confirmed_success'),
                        'user' => new UserResource($user)
                    ], 200);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => trans('global.invalid_phone_number')
                    ], 422);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => trans('global.invalid_phone_number')
                ], 422);
            }

        } catch (ExpiredToken $e) {
            return response()->json([
                'status' => false,
                'message' => trans('global.invalid_token')
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => trans('global.invalid_phone_number'),
                'fireBaseMessage' => $e->getMessage(),
            ], 422);
        }
    }

    public function socialLogin(ApiSocialLogin $request)
    {
        return $this->checkSocialLogin($request);
    }

    public function logout()
    {
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
            return response()->json([], 204);
        }
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    public function resetPassword(ResetUserPasswordRequest $request)
    {
        $project_id = env('FirebaseProjectId', 'aqar-max');
        $verifier = new Verifier($project_id);

        try {
            $verifiedIdToken = $verifier->verifyIdToken($request->input('token'));
            $phone = $verifiedIdToken->claims()->get('phone_number');
            if ($phone) {
                $user = User::query()->where('mobile_with_code', $phone)->first();
                if ($user) {
                    $user->update(['mobile_verified_at' => Carbon::now()]);
                    $user->update(['password' => bcrypt($request->input('password'))]);
                }
            }
            if (!$phone || !$user) {
                return response()->json([
                    'status' => false,
                    'message' => trans('global.invalid_phone_number')
                ], 422);
            }

        } catch (ExpiredToken $e) {
            return response()->json([
                'status' => false,
                'message' => trans('global.invalid_token')
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => trans('global.invalid_phone_number'),
                'fireBaseMessage' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['message' => trans('auth.password_reset')], 200);
    }

    public function changePassword(ChangeUserPasswordRequest $request){
        $user = auth('sanctum')->user();
        if(!Hash::check($request->input('old_password'), $user->password)) return response()->json(['message' => trans('auth.invalid_password')],401);

        $user->update(['password' => bcrypt($request->input('password'))]);
        return response()->json(['message' => trans('auth.password_updated')],200);
    }
}
