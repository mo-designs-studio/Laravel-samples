<?php


namespace App\Http\Traits;


use App\Http\Requests\ApiSocialLogin;
use App\Http\Resources\Admin\UserResource;
use App\Model\User\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

trait SocialLogin
{
    protected $request;

    public function checkSocialLogin(ApiSocialLogin $request)
    {

        $this->request = $request;

        switch ($request->input('type')) {
            case 1:
                return $this->loginWithFacebook();
            case 2 :
                return $this->loginWithGoogle();
            default:
                return response()->json(['message' => 'معرف الخدمة (type) غير مدعوم'], 422);
        }
    }

    private function loginWithFacebook()
    {
        try {
            $url = "https://graph.facebook.com/{$this->request->input('id')}?fields=birthday,email,hometown,first_name,last_name,middle_name,picture";
            $response = Http::withHeaders([
                "Authorization" => "Bearer {$this->request->input('token')}"
            ])->get($url)->json();

            $user = $this->checkIfFacebookUserExists($response);

            return response()->json([
                'status' => true,
                'user' => new UserResource($user),
                'token' => $user->createToken($user->id)->plainTextToken
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => trans('global.cannot_connect_to_service_provider')], 422);
        }

    }

    private function checkIfFacebookUserExists($socialResponse)
    {
        $socialResponse = json_decode(json_encode($socialResponse));
        $social = User::query()->where(function ($idQuery) use ($socialResponse) {
            $idQuery->where('social_id', optional($socialResponse)->id)->whereNotNull('social_id');
        })->orWhere(function ($q) use ($socialResponse) {
            $q->when(optional($socialResponse)->email, function ($q) use ($socialResponse) {
                $q->where('email', optional($socialResponse)->email);
            });
        })->first();
        if (!$social && !isset($socialResponse->error)) {
            $user = User::query()->create([
                'mobile' => optional($socialResponse)->phone ? $socialResponse->phone : "0000" . Carbon::now()->timestamp,
                'password' => bcrypt(Carbon::now()->timestamp . 'mazadat'),
                'user_type' => User::User,
                'city_id' => 1,
                'is_active' => 1,
                'is_social' => 1,
                'role_id' => User::User,
                'email' => isset($socialResponse->email) ? $socialResponse->email : Str::random(5) . '@' . Str::random(5) . Str::random(2),
                'email_verified_at' => isset($socialResponse->email) ? Carbon::now() : null,
                'social_type' => 1,
                'social_id' => optional($socialResponse)->id,
                'social_token' => $this->request->input('token'),
                'need_current_password' => 0
            ]);
            $user->userAttributes()->create([
                'first_name' => isset($socialResponse->first_name) ? $socialResponse->first_name : null,
                'last_name' => isset($socialResponse->last_name) ? $socialResponse->last_name : null,
                'profile_image' => "http://graph.facebook.com/{$socialResponse->id}/picture?type=large",
            ]);
            if(isset($socialResponse->email)) $user->contacts()->create([
                'contact_method_id' =>  '3',
                'value'             =>  $socialResponse->email,
            ]);
            return $user;
        }
        return $social;
    }

    private function loginWithGoogle()
    {
        $clientId = env('Google_ClientId');
        if ($this->request->input('platform'))
            $clientId = $this->request->input('platform') === 'android' ??  env('Android_Id');
        $client = new \Google_Client(['client_id' => $clientId]);
        $payload = $client->verifyIdToken($this->request->input('token'));

        if ($payload) {
            $user = $this->checkIfGoogleUserExists(json_decode(json_encode($payload)));
            return response()->json([
                'status' => true,
                'user' => new UserResource($user),
                'token' => $user->createToken($user->id)->plainTextToken
            ], 200);
        } else {
            return response()->json(['message' => trans('global.cannot_connect_to_service_provider')], 422);
        }

    }

    private function checkIfGoogleUserExists($socialResponse)
    {
        $social = User::query()->where(function ($idQuery) use ($socialResponse) {
            $idQuery->where('social_id', optional($socialResponse)->sub)->whereNotNull('social_id');
        })->orWhere(function ($q) use ($socialResponse) {
            $q->when(optional($socialResponse)->email, function ($q) use ($socialResponse) {
                $q->where('email', optional($socialResponse)->email);
            });
        })->first();
        if (!$social) {
            $user = User::query()->create([
                'mobile' => isset($socialResponse->phone) ? $socialResponse->phone : "0000" . Carbon::now()->timestamp,
                'password' => isset($socialResponse->password) ? $socialResponse->password : bcrypt(Carbon::now()->timestamp . 'mazadat'),
                'user_type' => User::User,
                'city_id' => 1,
                'is_active' => 1,
                'is_social' => 1,
                'role_id' => User::User,
                'email' => isset($socialResponse->email) ? $socialResponse->email : Str::random(5) . '@' . Str::random(5) . Str::random(2),
                'email_verified_at' => isset($socialResponse->email) ? Carbon::now() : null,
                'social_type' => 2,
                'social_id' => optional($socialResponse)->sub,
                'social_token' => $this->request->input('token'),
                'need_current_password' => 0
            ]);

            $name = explode(' ', optional($socialResponse)->given_name);

            $user->userAttributes()->create([
                'first_name' => isset($name[0]) ? $name[0] : null,
                'last_name' => isset($name[1]) ? $name[1] : (isset($socialResponse->family_name) && $socialResponse->family_name ? $socialResponse->family_name : null),
                'profile_image' => isset($socialResponse->picture) ? $socialResponse->picture : null,
            ]);

            if(isset($socialResponse->email)) $user->contacts()->create([
                'contact_method_id' =>  '3',
                'value'             =>  $socialResponse->email,
            ]);

            return $user;
        }
        return $social;
    }

}
