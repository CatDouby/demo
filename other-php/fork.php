<?php

$shareNum = 0;

$pid = pcntl_fork();
if ($pid == -1) {
    exit('fork error');
} else if ($pid == 0) {
    for ($i=0; $i < 5; $i++) { 
        $shareNum++;
        echo "child process shareNum: $shareNum\n";
    }
    // 发送一个中断信号
    $_pid = posix_getpid();
    echo "child get pid=$_pid\n";
    // posix_kill($_pid, SIGUSR1);
    posix_kill($_pid, SIGKILL);
    // exit(10); // this not work
} else {
    $shareNum++;
    echo "parent process shareNum: $shareNum\n";
    echo "waiting child process $pid ...\n";
    // pcntl_wait 等待的是子进程的中断信号，而不是 exit 这样的退出
    $childPid = pcntl_wait($status);
    echo "child process exit with pid: $childPid, status: $status\n";
}