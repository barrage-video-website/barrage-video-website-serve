<?php

namespace App\Http\Controllers;

use App\Model\User;
use App\Model\Video;
use App\Model\Live;
use App\Helpers\Responder;


use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redis;

class UserController extends BaseController
{

    // 声明没有参数的构造函数
    public function __construct()
    {

    }

    public function login(Request $request){
        // step 1. 验证数据
        $account = $request->input('account');
        $password = $request->input('password');

        //  验证数据
        $validator = Validator::make($request->all(), [
            'account' => ['required'],
            'password' => ['required'],
        ],[
            'account.required' => '账号不能为空',
            'password.required' => '验证码不能为空',
        ]);

        if($validator->fails()){
            return Responder::error('0001',$validator->errors()->first());
        }

        //step 2. 判断用户是否存在
        $result = User::where('account',$account)->exists();
        if(!$result){
            return Responder::error('0002','账号不正确');
        }

        // step 3. 拿出用户密码与前端发送密码是否一致
        $DBpassword = User::where('account',$account)->value('password');
        $result = password_verify($password,$DBpassword);
        if(!$result){
            return Responder::error('0003','密码不正确');
        }


        // stpe 4. 生成token
        $user = User::where('account',$account)->first();
        $token = app('auth')->login($user);

        // step 5. 返回
        return Responder::success('登录成功',[
            'token' => $token
        ]);
    }

    public function register(Request $request){
        // step 1. 验证数据
        $account = $request->input('account');
        $password = $request->input('password');
        $nickname = $request->input('nickname');

        //  验证数据
        $validator = Validator::make($request->all(), [
            'account' => ['required'],
            'password' => ['required'],
            'nickname' => ['required'],
        ],[
            'account.required' => '账号不能为空',
            'password.required' => '验证码不能为空',
            'nickname.required' => '昵称不能为空',
        ]);

        if($validator->fails()){
            return Responder::error('0001',$validator->errors()->first());
        }

        //step 2. 判断用户是否存在
        $result = User::where('account',$account)->exists();
        if($result){
            return Responder::error('0002','账号不正确');
        }

        // step 3. 插入用户
        $user = new User();
        $user->account = $account;
        $user->nickname = $nickname;
        $user->password = password_hash($password,PASSWORD_BCRYPT);
        $user->save();


        //step 4. 返回
        return Responder::success('注册成功');
    }

    public function upload(Request $request){
        // step 1. 验证数据
        $video = $request->file('video');
        $img = $request->file('img');

//         //  验证数据
//         $validator = Validator::make($request->all(), [
//             'video' => ['required'],
//         ],[
//             'video.file' => '上传文件不能为空',
//         ]);
//
//         if($validator->fails()){
//             return Responder::error('0001',$validator->errors()->first());
//         }

        // step 2. 随机生成一个字符串
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for($i=0; $i<30; $i++){
            $randomString = $randomString.$characters[rand(0, $charactersLength - 1)];
        }


        // step 3. 生成直到生成的文件名并不存在于文件夹当中
        //        请求过来的扩展名
        $videoExtension = $video->extension();
        do{
            $fileName = $randomString.'.'.$videoExtension;
        }while(Storage::disk('video')->exists($fileName));


        // step 4. 获取视频原名
        $filetOriginalName =  $video->getClientOriginalName();
        $imgOriginalName =  $img->getClientOriginalName();
        //获取登录中用户
        $userId = Auth::guard('user')->payload()['userId'];
        // 将文件存储
        $filePath = Storage::disk('video')->putFileAs('',$video,$fileName);
        $immPath = Storage::disk('image')->putFileAs('',$img,$imgOriginalName);
        // step 5.插入video记录

        Video::insert([
            'user_id'=> $userId,
            'video_path' => $filePath,
            'video_name' => $filetOriginalName,
            'video_cover_name' => $imgOriginalName,
            'video_cover_path' => $immPath
        ]);


        //step 6. 返回
        return Responder::success('上传成功');
    }

    // 注销
    public function logout(){
        app('auth')->parseToken()->invalidate();
        return Responder::success('成功注销');
    }

    // 获取视频列表
    public  function  getCartoonList(Request $request){
        // step 1. 验证数据
        $page = $request->input('page');

        //  验证数据
        $validator = Validator::make($request->all(), [
            'page' => ['required'],
        ],[
            'page.required' => '页数',
        ]);

        if($validator->fails()){
            return Responder::error('0001',$validator->errors()->first());
        }

        // step 2. 随机拿出视频
        $videoSize =8;

        // 分页 获取问题答案
        $videoLists=Video::skip($videoSize * ($page - 1))->take($videoSize)
            ->select('video_id as id','video_cover_path as coverPath','video_title as coverTitle','user_id')
            ->get();

        // step 3. 通过user_id找到相应名称
        foreach ($videoLists as $videoList){
            $videoList->userName = User::where('user_id',$videoList->user_id)->value('nickname');
            $videoList->type = 'video';
        }

        return Responder::success('获取列表成功',[
            'videolists' => $videoLists
        ]);
    }

    public function getLiveList(Request $request){
        // step 1. 验证数据
        $page = $request->input('page');

        //  验证数据
        $validator = Validator::make($request->all(), [
            'page' => ['required'],
        ],[
            'page.required' => '页数',
        ]);

        if($validator->fails()){
            return Responder::error('0001',$validator->errors()->first());
        }

        // step 2. 随机拿出直播
        $videoSize =8;

        // 分页 获取问题答案
        $LiveLists=Live::skip($videoSize * ($page - 1))->take($videoSize)
            ->select('live_id as id','live_cover_path as coverPath','Live_title as coverTitle','user_id')
            ->get();

        // step 3. 通过user_id找到相应名称
        foreach ($LiveLists as $LiveList){
            $LiveList->userName = User::where('user_id',$LiveList->user_id)->value('nickname');
            $LiveList->type = 'live';
        }

        return Responder::success('获取列表成功',[
            'LiveLists' => $LiveLists
        ]);

    }

    public  function  getVideo(Request $request){
        // step 1. 验证数据
        $videoId = $request->input('videoId');

        //  验证数据
        $validator = Validator::make($request->all(), [
            'videoId' => ['required'],
        ],[
            'videoId.required' => '视频id不能为空',
        ]);

        if($validator->fails()){
            return Responder::error('0001',$validator->errors()->first());
        }

        // step 2. 通过videoId找到相应标题和路径
        $video = Video::where('video_id',$videoId)
            ->select('user_id as userId','video_title as videoTitle','video_cover_path as coverPath','video_path as videoPath')
            ->first();

        // step 3.返回
        return Responder::success('成功获取视频',[
            'video' => $video
        ]);
    }


    public function sentBarrage(Request $request){
        // step 1. 验证数据
        $barrage = $request->input('barrage');
        $videoId = $request->input('videoId');
        $currentTime = $request->input('currentTime');

        //  验证数据
        $validator = Validator::make($request->all(), [
            'barrage' => ['required'],
            'videoId' => ['required'],
            'currentTime' => ['required'],
        ],[
            'barrage.required' => '弹幕不能为空',
            'videoId.required' => '视频号不能为空噢',
            'currentTime.required' => '当前时间不能为空',
        ]);

        if($validator->fails()){
            return Responder::error('0001',$validator->errors()->first());
        }


        // step 2. 将内容缓存到redis服务器

        Redis::rpush($videoId,"$currentTime:$barrage");
	

        return Responder::success('成功发送弹幕');
    }


    public function deleteBarrage(){
        Redis::flushAll();
    }
}
