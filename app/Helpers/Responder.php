<?php


namespace App\Helpers;


class Responder
{
    public static function success($msg="获取成功",$data=null){
        return self::makeCode("0000",$msg,$data);
    }

    public static function error($code, $msg="成功",$data=null){
        return self::makeCode($code,$msg,$data);
    }

    public static function unAuth($msg="用户还未登录",$data=null){
        return self::makeCode("4001",$msg,$data);
    }

    private static function makeCode($code, $msg="获取成功", $data=null){
        $response = [
            'code' => $code,
            'msg' => $msg
        ];
        if($data !== null){
            $response['data'] = $data;
        }
        return $response;
    }
}
