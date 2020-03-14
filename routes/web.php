<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});



        // 登录
    $router->post('/login', ['uses' => 'UserController@login']);

        // 注册
    $router->post('/register', ['uses' => 'UserController@register']);

    // 获取动画区
    $router->get('/get-video-list', ['uses' => 'UserController@getCartoonList']);

    // 获取直播区
    $router->get('/get-live-list', ['uses' => 'UserController@getLiveList']);

    // 获取视频列表
    $router->get('/get-video', ['uses' => 'UserController@getVideo']);

        // 通过中间件验证接口
    $router->group(['middleware' => 'auth'],function() use ($router){

            // 注销
        $router->post('/logout', ['uses' => 'UserController@logout']);

            // 上传
        $router->post('/upload', ['uses' => 'UserController@upload']);

        // 发送弹幕
        $router->post('/sent-barrage', ['uses' => 'UserController@sentBarrage']);

        // 删除弹幕
        $router->post('/delete-barrage', ['uses' => 'UserController@deleteBarrage']);

        // 删除弹幕
        $router->post('/push', ['uses' => 'UserController@push']);
    });
