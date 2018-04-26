<?php

namespace App\Http\Controllers\Api;

use Auth;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\Api\SocialAuthorizationRequest;
use App\Http\Requests\Api\AuthorizationRequest;
use Zend\Diactoros\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\AuthorizationServer;
use App\Models\Traits\PassportToken;
use App\Http\Requests\Api\WeappAuthorizationRequest;

class AuthorizationsController extends Controller
{
    use PassportToken;
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

        /*$result = $this->getBearerTokenByUser($user, '1', false);

        //return $this->responseWithToken($token)->setStatusCode(201);
        return $this->response->array($result)->setStatusCode(201);*/
        $result = $this->getBearerTokenByUser($user, '1', false);
        return $this->response->array($result)->setStatusCode(201);
    }

    /*public function store(AuthorizationRequest $request)
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
    }*/

    public function store(AuthorizationRequest $originRequest, AuthorizationServer $server, ServerRequestInterface $serverRequest)
    {
        try {
            return $server->respondToAccessTokenRequest($serverRequest,new Psr7Response)->withStatus(201);
        } catch (OAuthServerException $e) {
            return $this->response->errorUnauthorized($e->getMessage());
        }
    }

    public function responseWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => \Auth::guard('api')->factory()->getTTL() * 60
        ]);
    }

    /*public function update()
    {
        $token = \Auth::guard('api')->refresh();
        return $this->responseWithToken($token);
    }*/

    public function update(AuthorizationServer $server, ServerRequestInterface $serverRequest)
    {
        try {
            return $server->respondToAccessTokenRequest($serverRequest, new Psr7Response);
        } catch (OAuthServerException $e) {
            return $this->response->errorUnauthorized($e->getMessage());
        }
    }

    public function destory()
    {
        // \Auth::logout();
        $this->user()->token()->revoke();
        return $this->response->noContent();
    }

    public function weappStore(WeappAuthorizationRequest $request)
    {
        $code = $request->code;

        $miniProgram = \EasyWeChat::miniProgram();

        $data = $miniProgram->auth->session($code);

        if (isset($data['errcode'])) {
            return $this->response->errorUnauthorized('code 不正确');
        }

        $user = User::where('weapp_openid',$data['openid'])->first();
        $attributes['weixin_session_key'] = $data['session_key'];

        if (!$user) {
            if (!$request->username) {
                return $this->response->errorForbidden('用户不存在');
            }

            $username = $request->username;

            filter_var($username, FILTER_VALIDATE_EMAIL) ?
                $credentials['email'] = $username :
                $credentials['phone'] = $username;

            $credentials['password'] = $request->password;

            if(! Auth::guard('api')->once($credentials)) {
                return $this->response->errorUnauthorized('用户名或密码错误');
            }

            $user = Auth::guard('api')->getUser();
            $attributes['weapp_openid'] = $data['open_id'];
        }

        $user->update($attributes);

        $token = Auth::guard('api')->fromUser($user);

        return $this->responseWithToken($token)->setStatusCode(201);
    }
}
