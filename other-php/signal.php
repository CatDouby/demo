<?php

/**
 * 要让在 pcntl_signal 安装的 handler 程序被执行，有两种方式：
 * 1) 在执行程序前声明 declare(ticks = n); 这样可以让程序没执行n次低级语句就检查进程中是否有未处理的信号。
 * 2) 在程序需要检查信号的地方手动调用 pcntl_signal_dispatch()，如进程有未处理的信号则会调用。
 */

// declare(ticks = 2);
// declare 还会执行注册在 register_tick_function() 内的代码段，每个 tick 都会执行。
// declare 另一种写法，将要检查的语句写在后面的语句块内，这样的 tick 检查更加精确。
// declare(ticks = 1) {
//     SignalHelper::bindHandler(function($sig) {
//         exit("get sig: $sig, exit\n");
//     });
//     for ($i=0; $i < 60; $i++) { 
//         echo date('y-m-d H:i:s'), "\n";
//         sleep(1);
//     }
// }


class SignalHelper
{
    private static $hasSignal = false;

    public static function hasSignal()
    {
        return self::$hasSignal;
    }

    public static function bindHandler($func)
    {
        if (!is_callable($func)) {
            exit("bind func must be callable\n");
        }
        if (!extension_loaded('pcntl')) {
            exit("need extension pcntl\n");
        }

        pcntl_signal(SIGINT, $func);
        pcntl_signal(SIGQUIT, $func);
        // pcntl_signal(SIGKILL, $func);
        pcntl_signal(SIGTERM, $func);
    }

    // 默认处理方法
    public static function defaultHandler($sig)
    {
        switch ($sig) {
            case SIGTERM:
            case SIGHUP:
            case SIGINT:
            case SIGQUIT:
                echo "get sig: $sig\n";
                self::$hasSignal = true;
                break;
            default:
                echo "default get sig: $sig\n"; // 其他信号，默认处理
        }
    }
}

SignalHelper::bindHandler('SignalHelper::defaultHandler');
// 每轮新任务执行开始前检查是否有结束信号，任务执行完成后获取信号，这样可以保证任务执行过程中不被中断
while (!SignalHelper::hasSignal()) {
    // run task codes
    echo date('y-m-d H:i:s'), "\n";
    sleep(1);

    // after task, call dispatch signal.
    pcntl_signal_dispatch();
}