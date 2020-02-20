<?php

namespace App\Http\Controllers;

use App\Model\User;
use App\Helpers\Responder;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
}
