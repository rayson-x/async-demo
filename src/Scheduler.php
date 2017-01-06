<?php
namespace Async;

/**
 * Class Scheduler
 * @package Async
 */
class Scheduler
{
    /**
     * 连接列表
     *
     * @var \SplObjectStorage
     */
    public static $taskList;

    /**
     * @var bool
     */
    public static $isRun = false;

    /**
     * @param \Generator $coroutine
     */
    public static function addTask(\Generator $coroutine)
    {
        // 添加一个任务
        static::$taskList->attach($coroutine);
    }

    public static function init()
    {
        // 初始化任务列表
        static::$taskList = new \SplObjectStorage();
    }

    public static function run()
    {
        // 避免重复注册
        if(static::$isRun){
            return;
        }

        static::init();
        static::$isRun = true;
        // 注册调度器
        register_tick_function(array("\\Async\\Scheduler","tick"));
    }

    public static function tick()
    {
        // 检查任务是否完成
        foreach(static::$taskList as $task){
            if(!$task->valid()) {
                // 已完成,删除任务
                static::$taskList->detach($task);
            }

            // 未完成,继续任务
            $task->next();
        }
    }

    public static function wait()
    {
        while(static::$taskList->count() > 0){
            // 等待所有任务完成
        }
    }
}