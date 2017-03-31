<?php
declare(encoding='UTF-8');
namespace framework\tasks;
/**
 * UC乐园  任务管理器
 *
 *  Example 1 添加一个分布式任务给image_worker处理，并且交由给当前类的actioncallback方法处理返回数据
 * 
 * <code>
 * <?php
 *     \framework\tasks\Manager::dispatcher(json_ecnode(array('file' => base64_encode('xxx'))), WorkerTypes::IMAGE_WORKER, 'callback');
 *     //任务执行完成后， 回调函数
 *     function callback($param)
 *     {
 *         var_dump($param);
 *     }
 * ?>
 * </code>
 * 
 *  Example 2 添加一个异步任务, 直接丢一个异步的写日志的任务给logger处理
 * 
 * <code>
 * <?php
 *    \tasks\Manager::dispatcher('hello', WorkerTypes::LOGGER);
 * ?>
 * </code>
 *  
 * @category   dispatcher
 * @package    tasks
 * @author     Jiuhong Deng <dengjiuhong@gmail.com>
 * @version    $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class Manager
{
    /**
     *    添加分配一个任务给后台处理
     *
     * @param mix    $taskData     - 传入进来需要放到后台处理的数据
     * @param String $workerType  - 任务的类型
     * @param Array  $callback - 处理返回数据的回调脚本，如果为空，则该任务是异步任务，不需要等待返回结果
     * @return boolean         - 任务是否添加成功
     * 
     */
    public static function dispatcher($taskData = '', $workerType = '', $callback = array())
    {
        self::initConfig();
        if (!isset(self::$config[$workerType]))
        {
            throw new \utils\TasksException("undefine workerType " . $workerType . ", please defined in \\workers\\WorkerTypes ");
        }
        if(isset(self::$config[$workerType]['serverType']) && isset(self::$config[$workerType]['serverType']) == self::MQ_SERVER_TYPE)
        {
        	//发送到消息队列
        	return self::dispatcherToMq($taskData, $workerType, $callback);
        }
        //发送到gearman
        return self::dispatcherToGearman($taskData, $workerType, $callback);
    }
    /**
     *    添加一个异步的事件(在输出到浏览器之后处理)
     *
     * 主要提高用户的响应速度
     * @param callback $callBack  - 事件处理回调的函数
     * @param mixed    $parameter - 事件处理需要用到的参数, 支持多个参数
     * @return boolean            - 是否添加成功
     *
     * @example
     * boolean \tasks\Manager::addSyncEvent ( callback $function [, mixed $parameter [, mixed $... ]] )
     * \tasks\Manager::addSyncEvent(array('\workers\script\Test', 'sayHello'), 'good job');
     * // 在用户请求完毕之后, 再请求 \workers\script\Test::sayHello($task); $task 为传入的字符串 good job!
     */
    public static function addSyncEvent($callBack, $parameter)
    {
        self::logger("addSyncEvent");
        $args = func_get_args();
        if (empty($callBack)) return false;
        unset($args[0]);
        self::$syncEvents[] = array(
            $args,
            $callBack
        );
        return true;
    }
    /**
     *   刷新之前添加的事件
     */
    public static function flushSyncEvent()
    {
        self::logger("flushSyncEvent");
        self::logger(json_encode(self::$syncEvents));
        if (!self::$syncEvents) return false;
        foreach(self::$syncEvents as $event)
        {
            call_user_func_array($event[1], $event[0]);
        }
        // 释放内存
        self::$syncEvents = array();
        return true;
    }
    /**
     *   刷新到任务系统
     */
    public static function flushSync()
    {
        self::logger("flushSync", 'debug');
        self::initConfig();
        foreach(self::$tasks as $workerType => $v)
        {
            self::logger("flushSync`" . $v[0] . "`" . $v[1], 'debug');
            $client = self::getProxyClient($v[2]);
            $client->addTaskBackground($v[0], $v[1]);
            $res = @$client->runTasks();
            self::logger("flushSync`res`".$res);
            if ($res === false)
            {
                $errorNo     = $client->getErrno();
                $errorString = $client->error();
                self::logger("dispatcher`" . $v[2] . "`" . $v[1] . "`" . $errorNo . "`" . $errorString, 'error');
            }
            unset($client);
        }
        self::$tasks = array();
    }
	/**
     *   各种异步操作的代理
     * 暂时返回gearman的操作代理
     */
    private static function getProxyClient($workerType)
    {
        // 默认是gearman
        $server =self::$config[$workerType]['jobServer'];
        return \tasks\gearman\Factory::getInstance()->getClientInstance($server, $workerType);
    }
	/**
     *    非异步任务的回调数据处理, 回调正真需要调用的类和方法
     * @param gearman object $job
     */
    public static function handleResult($task)
    {
        // 解析gearman返回来的数据
        if (!empty(self::$taskRestCallBack))
        {
        	call_user_func(self::$taskRestCallBack, $task->data());
        }
    }
    /**
     *   初始化配置
     */
    private static function initConfig()
    {
        if (self::$config === null)
        {
            self::$config = require dirname(__FILE__) . '/../resources/configs/Workers.inc.php';
            require_once dirname(__FILE__) . '/gearman/Factory.php';
        }
        return true;
    }
    
    /**
     * 往Gearman发送任务
     * @param $taskData 任务数据
     * @param $workerType 任务类型
     * @param $callback 任务回调函数
     */
    private static function dispatcherToGearman($taskData, $workerType, $callback)
    {
        self::$workerType = $workerType;
        if (!empty($callback))
        {
            // 非异步, 需要回调
            self::logger("dispatcher`callback`" . json_encode(self::$config[$workerType]['event']) . "`" . $taskData . "`" . $workerType, 'debug');
            $client = self::getProxyClient($workerType);
            $result = array();
            self::$taskRestCallBack = $callback;
        	$client->setCompletecallback(array('\tasks\Manager','handleResult'));
            $client->addTask(self::$config[$workerType]['event'], $taskData);
            $res = @$client->runTasks();
            if ($res === false)
            {
                $errorNo     = $client->getErrno();
                $errorString = $client->error();
                self::logger("dispatcher`" . $workerType . "`" . $taskData . "`" . $errorNo . "`" . $errorString, 'error');
                return false;
            }
        } 
        else 
        {
            // 异步方式, 先加到队列中, 程序执行完成后再进行
            self::logger("dispatcher`nocallback`" . self::$config[$workerType]['event'] . "`" . $taskData . "`" . $workerType, 'debug');
            self::$tasks[] = array(
                self::$config[$workerType]['event'],
                $taskData,
                $workerType
            );
            // 如果是命令行模式，直接将缓冲输出
            if (defined('UZONE_COMMAND'))
            {
                // 命令行模式
                self::flushSyncEvent();
                self::flushSync();
            }
        }
        return true;
    }
    
	/**
     * 往mq发送任务
     * @param $taskData 任务数据
     * @param $workerType 任务类型
     */
    private static function dispatcherToMq($taskData, $workerType)
    {
    	//获取消息队列服务器
        $servers = \tasks\gearman\Factory::getInstance()->getJobServers(self::$config[$workerType]['jobServer']);
        $servers = explode(',', $servers['addr']);
        //在数据中添加worker类型
        //$taskData['workerType'] = $workerType;        
        $result = mq_put_msg($servers, self::$config[$workerType]['mqName'], $taskData);
        unset($servers);
        if($result['result'] == -1)
        {
        	self::logger("dispatcher`mq`". self::$config[$workerType]['event'] . "`" . $taskData . "`" . $workerType . "`" . $result['reason'], 'error');
        	return false;
        }
        return true;
    }
    
    /**
     *    记录logger
     * @param String $msg  - 记录log的信息
     * @param String $type - 记录log的类型 debug, error
     */
    private static function logger($msg, $type = 'debug'){
        $msg = self::$workerType  . "`". $msg;
        return \utils\Logger::writeLog($type, 'task_Manager', $msg);
    }
    private static $workerType;
    private static $syncEvents = array();
    private static $config;
    private static $tasks            = array();
    private static $taskRestCallBack = array();
    
    const MQ_SERVER_TYPE = 'mq';
}
