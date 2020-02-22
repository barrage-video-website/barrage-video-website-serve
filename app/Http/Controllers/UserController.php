<?php

namespace App\Http\Controllers;

use App\Model\User;
use App\Helpers\Responder;

use App\Model\Video;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Laravel\Lumen\Routing\Controller as BaseController;

class UserController extends BaseController
{
    //
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
        //获取登录中用户
        $userId = Auth::guard('user')->payload()['userId'];
        // 将文件存储
        $filePath = Storage::disk('video')->putFileAs('',$video,$fileName);

        // step 5.插入video记录

        Video::insert([
            'user_id'=> $userId,
            'video_path' => $filePath,
            'video_name' => $filetOriginalName
        ]);


        //step 6. 返回
        return Responder::success('上传成功');
    }

}
