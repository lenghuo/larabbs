<?php

namespace App\Http\Controllers\Api;

use App\Models\Image;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\Api\UserRequest;
use App\Transformers\UserTransformer;

class UsersController extends Controller
{
    //
    public function store(UserRequest $request)
    {
        $verifyData = \Cache::get($request->verification_key);

        if(!$verifyData) {
            return $this->response->error('验证码已经失效',422);
        }

        if (!hash_equals($verifyData['code'],$request->verification_code)) {
            return $this->response->errorUnauthorized('验证码错误');
        }

        $user = User::create([
            'name' => $request->name,
            'phone' => $verifyData['phone'],
            'password' => bcrypt($request->password),
        ]);

        \Cache::forget($request->verification_key);

        //return $this->response->item($user,new UserTransformer())->setStatusCode(201);
        return $this->responseWithItem($user)->setStatusCode(201);
    }

    public function weappStore(UserRequest $request)
    {
        // 缓存中是否存在对应的key
        $verifyData = \Cache::get($request->verification_key);

        if(!$verifyData) {
            return $this->response->error('验证码已经失效',422);
        }

        // 判断验证码是否相等，不想当返回401错误
        if (!hash_equals((string)$verifyData['code'], $request->verification_code)) {
            return $this->response->errorUnauthorized('验证码错误');
        }

        // 获取微信的 open_id 和 session_key
        $miniProgram = \EasyWeChat::miniProgram();
        $data = $miniProgram->auth->session($request->code);

        if (isset($data['errCode'])) {
            return $this->response->errorUnauthorized('code 不正确');
        }

        // 如果 open_id 的用户已存在，报错403
        $user = User::where('weapp_openid', $data['openid'])->first();
        if ($user) {
            return $this->response->errorForbidden('微信已绑定其他用户');
        }

        // 创建用户
        $user = User::create([
            'name' => $request->name,
            'phone' => $verifyData['phone'],
            'password' => bcrypt($request->password),
            'weapp_openid' => $data['openid'],
            'weixin_session_key' => $data['session_key'],
        ]);

        //清除验证码缓存
        \Cache::forget($request->verification_key);

        // meta中返回token信息
        return $this->responseWithItem($user)->setStatusCode(201);
    }

    public function me()
    {
        //return $this->response->item($this->user(),new UserTransformer());
        return $this->responseWithItem($this->user())->setStatusCode(201);
    }

    public function update(UserRequest $request)
    {
        $user = $this->user();

        $attributes = $request->only(['name','email','introduction','registration_id']);

        if ($request->avatar_image_id) {
            $image = Image::find($request->avatar_image_id);

            $attributes['avatar'] = $image->path;
        }

        $user->update($attributes);

        return $this->response->item($user,new UserTransformer());
    }

    public function responseWithItem($user)
    {
        return $this->response->item($user,new UserTransformer())
                            ->setMeta([
                                'access_token' => \Auth::guard('api')->fromUser($user),
                                'token_type' => 'Bearer',
                                'expired_in' => \Auth::guard('api')->factory()->getTTL() * 60
                            ]);
    }

    public function activedIndex(User $user)
    {
        $activedUser = $user->getActiveUsers();

        return $this->response->collection($activedUser,new UserTransformer());
    }
}
