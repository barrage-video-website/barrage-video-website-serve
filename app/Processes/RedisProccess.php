<?php
namespace App\Processes;
//use App\Tasks\TestTask;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
//use Hhxsv5\LaravelS\Swoole\Task\Task;
use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Process;
use Illuminate\Support\Facades\Redis;


class RedisProccess implements CustomProcessInterface
{
    /**
     * @var bool 退出标记，用于Reload更新
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        // 进程运行的代码，不能退出，一旦退出Manager进程会自动再次创建该进程。
        while (!self::$quit) {
            // sleep(1); // Swoole < 2.1
           // echo $swoole;
            Coroutine::sleep(1); // Swoole>=2.1 已自动为callback()方法创建了协程并启用了协程Runtime。
            // 自定义进程中也可以投递Task，但不支持Task的finish()回调。
            // 注意：修改config/laravels.php，配置task_ipc_mode为1或2，参考 https://wiki.swoole.com/#/server/setting?id=task_ipc_mode
            //$ret = Task::deliver(new TestTask('task data'));
           // var_dump($ret);
            // 上层会捕获callback中抛出的异常，并记录到Swoole日志，然后此进程会退出，3秒后Manager进程会重新创建进程，所以需要开发者自行try/catch捕获异常，避免频繁创建进程。
            // throw new \Exception('an exception');
        }
    }
    // 要求：LaravelS >= v3.4.0 并且 callback() 必须是异步非阻塞程序。          
    public static function onReload(Server $swoole, Process $process)
    {
        // Stop the process...
        // Then end process
        \Log::info('Test process: reloading');
        self::$quit = true;
        // $process->exit(0); // 强制退出进程
    }
}