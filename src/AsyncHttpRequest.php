<?php
namespace Async;

use Ant\Http\Request;
use Ant\Http\Response;

class AsyncHttpRequest extends Request
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @param callable $callback
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * 发起请求
     */
    public function send()
    {
        if(!$this->callback){
            throw new \RuntimeException;
        }

        // 将请求作为任务添加到调度器中
        Scheduler::addTask($this->asyncConnection());
    }

    /**
     * 创建一个异步的http连接
     *
     * @return \Generator
     */
    protected function asyncConnection()
    {
        $ch = $this->curlInit();

        // curl批处理时为非阻塞的IO
        // 所以通过批处理的方式,创建一个非阻塞的请求
        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);

        do {
            $status = curl_multi_exec($mh, $active);
            // 协程出去,避免阻塞
            yield;
        } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

        // 获取响应信息
        $res = curl_multi_getcontent($ch);
        // 销毁句柄
        curl_multi_remove_handle ($mh,$ch);
        curl_close($ch);

        // 完成后回调函数
        call_user_func($this->callback,Response::createFromResponseStr($res));
    }

    /**
     * @return resource
     */
    protected function curlInit()
    {
        // 发起一个请求
        $ch = curl_init((string)$this->getUri());
        // 获取完整的Http流
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if($this->getUri()->getScheme() === 'https') {
            // 严格校验
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,true);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);
        }

        // 设置Http动词
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->getOriginalMethod());
        $header = [];
        foreach($this->getHeaders() as $headerName => $headerValue){
            if (is_array($headerValue)) {
                $headerValue = implode(',', $headerValue);
            }

            $headerName = implode('-',array_map('ucfirst',explode('-',$headerName)));
            $header[] = sprintf('%s: %s',$headerName,$headerValue);
        }
        // 设置Http header内容
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 设置Http body内容
        curl_setopt($ch, CURLOPT_POSTFIELDS, (string)$this->getBody());

        return $ch;
    }
}