<?php
/**
 * UC乐园  异步任务  - worker启动器 - 简单版本
 *
 * @category   Runner
 * @package    workers
 * @author     Jiuhong Deng <dengjiuhong@gmail.com>
 * @version    $Id:$
 * @copyright  优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare ( ticks = 1 );
declare(encoding='UTF-8');
define('UZONE_COMMAND', true);
define('UZONE_COMMAND_TIMESTAMP', time());
use \tasks\gearman\Factory as GearmanFactory;
use \workers\mq\SubscriberFactory as SubscriberFactory;

$opts = getopt("w:");
$workerType = isset($opts['w']) ? $opts['w'] : '';
if (empty($workerType)){
    Runner::showHelp();
}
require dirname(__FILE__) . '/../utils/Loader.php';
$config = require dirname(__FILE__) . '/../resources/configs/Workers.inc.php';
if (empty($config[$workerType])){
    Runner::showHelp();
}
// 设置为在10分钟之内分开自杀
$timeStep = rand(0,600);
$config   = $config[$workerType];

if(!isset($config['serverType']) || $config['serverType'] != 'mq')
{//gearman任务处理
	$job = GearmanFactory::getInstance()->getWorkerInstance($config['jobServer']);
	$job->addFunction($config['event'], array("Runner", "init"));
	while(Runner::$runnerstatus){
	    $job->work();
	}
}
else
{//消息队列消息处理
	$subscriber = SubscriberFactory::getInstance()->getSubscriber($config['event'], $config['jobServer']);
	$subscriber->addFunction($config['event'], array("Runner", "init"));
	while(Runner::$runnerstatus)
	{
	    if(!$subscriber->work())
	    {
	    	//没有任务处理的时候，挂起一段时间，避免消耗CPU资源
			usleep(2000000);	// wait for 2 secondes    	
	    }
	    
	}
}

class Runner 
{
    public static $runnerstatus = true;
    public static $jobClass;
    /**
     * 执行任务
     * @param $data 要处理的任务的数据
     */
    public static function init($data)
    {
        global $config, $timeStep;
        // 注意，这里可能有抛出异常的问题
        // 有问题，记log，继续往下跑
        $res = '';
        //用于区分gearman或消息队列的数据 modified by rnl 20110223
     	if($data instanceof GearmanJob)
        {//gearman数据
            $data = $data->workload();
        }
        try
        {
            // 初始化worker更新对像, 避免每次都 new 一个类
            $handler = $config['handler'][0];
            $methods = get_class_methods($handler);
            if (in_array('getInstance', $methods)){
                // 用单实例的方式初始化操作对像
                self::$jobClass = $handler::getInstance();
            } else {
                // 使用默认的new方式初始化操作对像
                self::$jobClass = new $handler();
            }
            $res = self::$jobClass->$config['handler'][1]($data);
        } catch(Exception $e)
        {
            // 手工捕捉异常, 防止worker退出
            $msg = $e->getMessage();
            $msg = 'uncatchException`' . $config['handler'][0] . "`" . $config['handler'][1] . "`" . $msg . "`" . $data;
            self::log($msg, 'error');
        }
        // 计数，满了自动退出
        self::checkLife();
        return is_array($res) ? json_encode($res) : $res;
    }
    /**
     * 检查时候应该退出
     */
    private static function checkLife()
    {
        global $config, $timeStep;
        // 检查任务执行的个数
        self::$num++;
        self::log("checkLife`num`" . self::$num);
        if (self::$num >= $config['handleNum']){
            self::killSelf('maxNum', self::$num);
        }
        // 检查生命周期, 暂时不根据生命周期去获取
        $t = time() - UZONE_COMMAND_TIMESTAMP;
        self::log("checkLife`time`" . $t . "`" . $config['lifeTime']);
        if ($t >= ($config['lifeTime'] + $timeStep)){
            self::killSelf('expreTime', $t);
        }
        self::$jobClass = null;
        // 自动更新配置文件
        self::refreshConfig();
        return true;
    }
    /**
     * 自动退出
     */
    private static function killSelf($reason, $value)
    {
        global $config;
        $pid = getmypid();
        self::log("killSelf`" . $pid . "`" . $reason . "`" . $value, 'info');
        // 通知原类desctruct
        @self::$jobClass->__desctruct();
        // 把自己杀了
        sleep (1);
        self::$runnerstatus = false;
    }
    /**
     * 记录worker的log
     * @param  $msg   日志内容 
     * @param  $level 日志等级
     */
    private static function log($msg, $level = 'debug')
    {
        // 不记录debug日志
        if ($level == 'debug') return false;
        global $config;
        $pid   = getmypid();
        $msg   = date('Ymd H:i:s') . "`" . $pid . "`" . $msg;
        $class = str_replace('\\', '_', $config['handler'][0]);
        file_put_contents(__DIR__ . '/../../logs/worker.RunnerStatus.' . $class . "_" . date('Ymd') .  "_" . $level  . ".log", $msg . "\n", FILE_APPEND);
        return true;
    }
    /**
     *   每隔特定的时间自动更新&加载配置文件
     * 默认为10分钟，避免修改配置都要重启worker
     */
    private static function refreshConfig()
    {
        if (self::$lastRefresh == null){
            self::$lastRefresh = UZONE_COMMAND_TIMESTAMP;
        }
        $now = time();
        if ($now - self::$lastRefresh > self::$refreshTime){
            self::$lastRefresh = $now;
            $pid = getmypid();
            self::log('refreshConfig`' . $pid, 'info');
            \utils\Config::refresh();
        }
        return true;
    }
    /**
     * 显示帮助
     */
    public static function showHelp()
    {
        echo "Hello, This is Uzone Worker Runner\n";
        echo "Usage:\n";
        echo "php Runner.php -w [workerName] \n";
        echo "eg: php Runner.php -w writeLog \n";
        exit();
    }
    private static $num         = 0;
    private static $lastRefresh;
    private static $refreshTime = 300; //每1分钟reload一次配置文件
}

