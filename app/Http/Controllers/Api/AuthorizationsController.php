<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\Api\SocialAuthorizationRequest;
use App\Http\Requests\Api\AuthorizationRequest;

class AuthorizationsController extends Controller
{
    //
    public function socialStore($type,SocialAuthorizationRequest $request)
    {
        if (!in_array($type,['weixin'])) {
            return $this->response->errorBadRequest();
        }

        $driver = \Socialite::driver($type);

        try {
            if ($code = $request->code) {
                $response = $driver->getAccessTokenResponse($code);
                $token = array_get($response,'access_token');
            } else {
                $token = $request->accesstoken;

                if ($type == 'weixin') {
                    $driver->setOpenId($request->openid);
                }
            }

            $oauthUser = $driver->userFromToken($token);
        } catch (\Exception $e) {
            return $this->response->errorUnauthorized('参数错误，未获取用户信息');
        }

        switch ($type) {
            case 'weixin':
                $uniodid = $oauthUser->offsetExists('unionid') ? $oauthUser->offsetGet('unionid') : null;

                if ($uniodid) {
                    $user = User::where('weixin_unionid',$uniodid)->first();
                } else {
                    $user = User::where('weixin_unionid',$oauthUser->getId())->first();
                }

                if (!$user) {
                    $user = User::create([
                        'name' => $oauthUser->getNickName(),
                        'avatar' => $oauthUser->getAvatar(),
                        'weixin_openid' => $oauthUser->getId(),
                        'weixin_unionid' => $uniodid,
                    ]);
                }

                break;
        }

        return $this->responseWithToken($token)->setStatusCode(201);
    }

    public function store(AuthorizationRequest $request)
    {
        $username = $request->username;

        filter_var($username,FILTER_VALIDATE_EMAIL) ?
            $credentials['email'] = $username :
            $credentials['phone'] = $username;
        $credentials['password'] = $request->password;

        if(!$token = \Auth::guard('api')->attempt($credentials)) {

            return $this->response->errorUnauthorized(trans('auth.failed'));

        }

        return $this->responseWithToken($token)->setStatusCode(201);
    }

    public function responseWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => \Auth::guard('api')->factory()->getTTL() * 60
        ]);
    }

    public function update()
    {
        $token = \Auth::guard('api')->refresh();
        return $this->responseWithToken($token);
    }

    public function destory()
    {
        \Auth::logout();
        return $this->response->noContent();
    }
}
