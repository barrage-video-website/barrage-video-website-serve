<?php
namespace App\Services;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Redis;
/**
 * @see https://wiki.swoole.com/#/start/start_ws_server
 */
class WebSocketService implements WebSocketHandlerInterface
{
    private $wsTable;
    private $videoList;
    private $videoId;
    // 声明没有参数的构造函数
    public function __construct()
    {
        $this->wsTable = app('swoole')->wsTable;
    }
    public function onOpen(Server $server, Request $request)
    {
        // 在触发onOpen事件之前，建立WebSocket的HTTP请求已经经过了Laravel的路由，
        // 所以Laravel的Request、Auth等信息是可读的，Session是可读写的，但仅限在onOpen事件中。
        // \Log::info('New WebSocket connection', [$request->fd, request()->all(), session()->getId(), session('xxx'), session(['yyy' => time()])]);
        $userId = mt_rand(1000, 10000);
        $this->wsTable->set('uid:' . $userId, ['value' => $request->fd]);// 绑定uid到fd的映射
        $this->wsTable->set('fd:' . $request->fd, ['value' => $userId]);// 绑定fd到uid的映射
        $server->push($request->fd, "Welcome to Track");
    }
    public function onMessage(Server $server, Frame $frame)
    {
        if($this->videoList ==null){
            $data=$frame->data;
            $unvideoIds = explode(":",$data);
            $this->videoId = $unvideoIds[1];
            $unsortVideoList = Redis::lrange ($this->videoId,0,-1);
            usort($unsortVideoList,function($a,$b){
                $cur = explode(":",$a)[0];
                $nex = explode(":",$b)[0];
                return ($cur < $nex) ? -1: 1;
            });
            $this->videoList = $unsortVideoList;
        }
        // \Log::info('Received message', [$frame->fd, $frame->data, $frame->opcode, $frame->finish]);
        // 广播
        foreach ($this->wsTable as $key => $row) {
            if (strpos($key, 'uid:') === 0 && $server->isEstablished($row['value'])) {
                $server->push($row['value'], $frame->data);
            }
        }
        if($this->videoId !=null){
            // 推送当前秒数的弹幕
            foreach ($this->wsTable as $key => $row) {
                if (strpos($key, 'uid:') === 0 && $server->isEstablished($row['value'])) {
                    foreach($this->videoList as $val){
                        $cur = explode(":",$val)[0];
                        if(ceil($cur)==$frame->data){
                            $server->push($row['value'],explode(":",$val)[1] );
                            unset($val);
                        }
                    }
                }
            }
        }
        // if($this->videoList !=null){

        // }
  	}
    public function onClose(Server $server, $fd, $reactorId)
    {
        $uid = $this->wsTable->get('fd:' . $fd);
        if ($uid !== false) {
            $this->wsTable->del('uid:' . $uid['value']); // 解绑uid映射
        }
        $this->wsTable->del('fd:' . $fd);// 解绑fd映射
        $server->push($fd, "Goodbye #{$fd}");
        // throw new \Exception('an exception');// 此时抛出的异常上层会忽略，并记录到Swoole日志，需要开发者try/catch捕获处理
    }
}
