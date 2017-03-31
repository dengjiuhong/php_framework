<?php
declare(encoding='UTF-8');
namespace framework\utils;
/**
 * UC乐园  基础支撑  性能日志检查
 *
 * @category   xhprof
 * @package    benchmark
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class XHProf
{
    /**
     *   初始化
     * 在一定的条件下面触发记录性能日志
     */
    public function init()
    {
        // 在特定条件下面触发,记录性能日志 
        $trigger = isset($_GET['prof_trigger']) ? $_GET['prof_trigger'] : ''; 
        $trigger = isset($_POST['prof_trigger']) ? $_POST['prof_trigger'] : $trigger;
        if (!empty($trigger))
        {
            // 开启记录日志
            self::$isInitProf = true;
            xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
            return true;
        }
        return false;
    }
    /**
     *   将性能日志写到worker机器里面去
     */
    public function flush()
    {
        // 记录性能日志
        // control action method
        if (self::$isInitProf){
            // stop profiler
            $xhprof_data = xhprof_disable();
            $log         = array(
                'control'     => \apps\base\Uzone::$control,
                'action'      => \apps\base\Uzone::$action,
                'xhprof_data' => json_encode($xhprof_data)
            );
            \utils\Logger::writeProf($log);
            unset($log);
        }
        return true;
    }
    /**
     *   获取单实例
     */
    public static function getInstance()
    {
        if (self::$obj === null)
        {
            self::$obj = new self();
        }
        return self::$obj;
    }
    /**
     *   初始化
     */
    private function __construct()
    {
        //TODO
    }
    private static $obj       = null;
    public static $isInitProf = false;
}

