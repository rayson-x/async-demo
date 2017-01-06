<?php
include "vendor/autoload.php";
// 启动异步任务调度器
declare(ticks = 1);
\Async\Scheduler::run();

$max = 10;

for($i = 1;$i <= $max; $i++) {
    $client = new \Async\AsyncHttpRequest('GET','https://api.weixin.qq.com/cgi-bin/token');

    $client->setCallback(function(Ant\Http\Response $response)use($i){
        echo "{$i} requests have been completed",PHP_EOL;
    });

    $client->send();
}

// Code...

// 等待所有请求结束
\Async\Scheduler::wait();