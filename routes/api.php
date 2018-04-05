<?php

use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', [
    'namespace' => 'App\Http\Controllers\Api',
    'middleware' => ['serializer:array','bindings']
], function($api) {
    $api->group([
        'middleware' => 'api.throttle',
        'limit' => config('api.rate_limits.sign.limit'),
        'expires' => config('api.rate_limits.sign.expires'),
    ], function ($api){
        /**
        *游客可访问的接口；
        */
        // 短信验证码
        $api->post('verificationCodes', 'VerificationCodesController@store')
            ->name('api.verificationCodes.store');
        //用户注册
        $api->post('users','UsersController@store')
            ->name('api.users.store');
        //图片验证码
        $api->post('captchas','CaptchasController@store')
            ->name('api.captchas.store');
        //第三方登录
        $api->post('socials/{social_type}/authorizations','AuthorizationsController@socialStore')
            ->name('api.socials.authorizations.store');
        //登录
        $api->post('authorizations','AuthorizationsController@store')
            ->name('api.authorizations.store');
        //刷新token
        $api->put('authorizations/current','AuthorizationsController@update')
            ->name('api.authorizations.update');
        //删除token
        $api->delete('authorizations/current','AuthorizationsController@destory')
            ->name('api.authorizations.destory');
        $api->get('categories','CategoriesController@index')
            ->name('api.categories.index');
        $api->get('topics','TopicsController@index')
            ->name('api.topics.index');
        $api->get('users/{user}/topics','TopicsController@userIndex')
            ->name('api.users.topics.index');
        $api->get('topics/{topic}','TopicsController@show')
            ->name('api.topics.show');
            //话题回复列表
        $api->get('topics/{topic}/replies','RepliesController@index')
            ->name('api.topics.replies.index');
            //某个用户的回复列表
        $api->get('users/{user}/replies','RepliesController@userIndex')
            ->name('api.users.replies.index');
            //获取资源推荐列表
        $api->get('links','LinksController@index')
            ->name('api.links.index');
            //获取活跃用户列表
        $api->get('actived/users','UsersController@activedIndex')
            ->name('api.user.actived.index');
        /**
        *需要token验证的接口
        */
        $api->group(['middleware' => 'api.auth'], function($api) {
            //用户登录
            $api->get('user','UsersController@me')
                ->name('api.user.show');
            //编辑登录用户信息
            $api->patch('user','UsersController@update')
                ->name('api.user.update');
            //更新图片
            $api->post('images','ImagesController@store')
                ->name('api.images.store');
            $api->post('topics','TopicsController@store')
                ->name('api.topics.store');
            $api->patch('topics/{topic}','TopicsController@update')
                ->name('api.topics.update');
            $api->delete('topics/{topic}','TopicsController@destory')
                ->name('api.topics.delete');
            /*$api->post('topics/{topic}/replies','RepliesController@store')
                ->name('api.topics.replies.store');*/
            $api->post('topics/{topic}/replies', 'RepliesController@store')
                ->name('api.topics.replies.store');
                //删除回复
            $api->delete('topics/{topic}/replies/{reply}', 'RepliesController@destroy')
                ->name('api.topics.replies.destroy');
                //通知列表
            $api->get('user/notifications','NotificationsController@index')
                ->name('api.user.notifications.index');
                //未读消息数量
            $api->get('user/notifications/stats','NotificationsController@stats')
                ->name('api.user.notifications.stats');
                //标记消息为已读
            $api->patch('user/read/notifications','NotificationsController@read')
                ->name('api.user.notifications.read');
                //当前登录用户权限
            $api->get('user/permissions','PermissionsController@index')
                ->name('api.user.permissions.index');
        });
    });
});
