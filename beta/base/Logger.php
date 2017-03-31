<?php
declare(encoding='UTF-8');
namespace framework\base;
/**
 * UC乐园  基础支撑  日志记录
 *
 * @category   Logger
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class Logger
{
    /**
     *    记录一个debug log
     *
     * @param string $cateogry - 业务的类型, 尽量使用类的命名空间
     * @param string $msg      - 日志的内容
     * @return bool            - 是否记录成功
     */
    public static function d($category, $msg)
    {
        return self::writeLog('debug', $category, $msg);
    }
    /**
     *    警告日志
     *
     * @param string $cateogry - 业务的类型, 尽量使用类的命名空间
     * @param string $msg      - 日志的内容
     * @return bool            - 是否记录成功
     */
    public  static function w($category, $msg)
    {
        return self::writeLog('warn', $category, $msg);
    }
    /**
     *    错误日志 error log
     * @param $category
     * @param $msg
     */
    public  static function e($category, $msg)
    {
        return self::writeLog('error', $category, $msg);
    }
    /**
     *    记录info日志 i
     * @param String $category
     * @param String $msg
     */
    public static function i($category, $msg)
    {
        return self::writeLog('info', $category, $msg);
    }
    /**
     *    具体verbose日志
     * @param $category
     * @param $msg
     */
    public static function v($category, $msg)
    {
        return self::writeLog('verbose', $category, $msg);
    }
    /**
     *    记录php日志
     * @param string $level
     * @param string $msg
     */
    public static function p($level, $msg)
    {
        $configs       = self::getConfig();
        return self::writeLog("php", $level, $msg);
    }
    /**
     *   写普通的log
     *
     * @param String   $level    - 等级 error | debug | warn | info | verbose | notice
     * @param String   $category - 分类, 子分类用_分割, 建议使用类的命名空间
     * @param String   $msg      - 日志内容
     * @return boolean           - 时候操作成功
     */
    public static function writeLog($level, $category, $msg)
    {
        // 基本的数据校验
        if (empty($level) || empty($category)) return false;
        $configs           = self::getConfig();
        // 将log buffer 一下
        $msg = is_array($msg) ? json_encode($msg) : $msg;
        self::$logCaches[] = array($category, $level, $msg);
        // flush基制
        if (self::$logCachesNum >= self::$logCachesMax || defined('UZONE_COMMAND') || defined('UZONE_WEB_IMGPROXY')){
            self::flush();
        }
        self::$logCachesNum++;
        return true;
    }
    /**
     *   将普通的log写到硬盘(或者rsyslog)
     */
    public static function flush()
    {
        // 普通log
        if (isset(self::$rsyslogConfig['isRsyslog']) && self::$rsyslogConfig['isRsyslog']){
            // 使用rsyslog方式写log
            self::saveToRsyslog();
        } else {
            // 使用本地方式写log
            self::saveToLocal();
        }
    }
    /**
     *   日志保存到本地
     * 只是在rsyslog不启用的时侯用到
     */
    public static function saveToLocal()
    {
        $fileNum = $logNum = 0;
        $tmp     = array();
        $configs = self::getConfig();
        if (!is_dir($configs['basePath'])) mkdir($configs['basePath'], 0777, true);
        // 把普通日志都写到一个文件里面去
        foreach(self::$logCaches as $log){
            $category       = isset($log[0]) ? $log[0] : '';
            $level          = isset($log[1]) ? $log[1] : '';
            $msg            = isset($log[2]) ? $log[2] : '';
            // 过滤掉不需要记录的log
            if (!isset($configs['level'][$level]) || !$configs['level'][$level]) continue;
            //使用默认的linux日志的标准格式
            // Dec 12 12:33:12 [host] [category]: [messages]
            $msg            = date('M j H:i:s')." ".php_uname('n')." ".$category.": " . $msg;
            $fileName       = $configs['prefix'] . "_" . $level . ".log";
            file_put_contents($configs['basePath'] . $fileName, $msg . "\n", FILE_APPEND);
            $logNum++;
        }
        self::$logCaches    = array();
        self::$logCachesNum = 0;
        self::selflog("saveToLocal`logNum:" . $logNum);
        return true;
    }
    /**
     *   日志保存到rsyslog
     */
    public static function saveToRsyslog()
    {
        $fileNum = $logNum = 0;
        self::$openlogs = array();
        foreach(self::$logCaches as $log){
            $c          = isset($log[0]) ? $log[0] : '';
            $level      = isset($log[1]) ? $log[1] : '';
            $msg        = isset($log[2]) ? $log[2] : '';
            $level      = isset(self::$priorityMapper[$level]) ? self::$priorityMapper[$level] : '';
            if (empty($level) || empty($msg)) continue;
            // 在配置里面的，或者有触发器的，才记录log
            if (in_array($level, self::$rsyslogConfig['priority']) || self::getLoggerTrigger($log[0])){
                self::openlog($c);
                syslog($level, $msg);
            }
            $logNum++;
            $fileNum++;
        }
        self::closelog();
        self::$openlogs     = array();
        self::$logCaches    = array();
        self::$logCachesNum = 0;
        self::selflog("saveToRsyslog`logNum:" . $logNum);
        return true;
    }
    /**
     * 打开rsyslog的链接
     */
    private static function openlog($c)
    {
        if (isset(self::$openlogs[$c])) return true;
        openlog($c, self::$rsyslogConfig['option'], self::$rsyslogConfig['facility']);
        self::$openlogs[$c] = 1;
        return true;
    }
    /**
     *   关闭rsyslog链接
     */
    private static function closelog()
    {
        if (!empty(self::$openlogs)){
            closelog();
        }
        return true;
    }
    /**
     *   检查是否有自定义触发器
     */
    private static function getLoggerTrigger($level)
    {
        $trggier = 'trigger_logger_' . $level;
        if (isset($_GET[$trggier]) && !empty($_GET[$trggier])) return true;
        if (isset($_POST[$trggier]) && !empty($_POST[$trggier])) return true;
        return false;
    }
    /**
     *    日志程序本身的log
     * @param String $msg    - 日志的内容
     * @param String $level  - 日志的等级
     */
    private static function selflog($msg, $level = 'debug')
    {
        return true;
    }
    /**
     *   回收资源
     */
    public static function gc()
    {
        self::selflog("gc...");
        self::$logCachesNum  = 0;
        self::$logCaches     = array();
        self::$synCaches     = array();
        self::$synCachesNum  = 0;
    }
    /**
     *   获取配置
     */
    private static function getConfig()
    {
        if (self::$configs === null){
            self::$configs       = \framework\base\Config::get('', 'Logger');
            self::$isDebug       =  self::$configs['level']['debug'];
            self::$rsyslogConfig = self::$configs['rsyslog'];
        }
        return self::$configs;
    }
    /**
     *   日志类型和rsyslog的
     */
    private static $priorityMapper = array(
        'debug'  => LOG_DEBUG,
        'warn'   => LOG_WARNING,
        'error'  => LOG_ERR,
        'info'   => LOG_INFO,
        'alert'  => LOG_ALERT,
        'notice' => LOG_NOTICE,
        'crit'   => LOG_CRIT,
        'php'    => LOG_EMERG,
    );
    private static $openlogs     = array();
    private static $routes       = array();
    private static $synCachesMax = 10;
    private static $synCachesNum = 0;
    private static $synCaches    = array();
    private static $appLoges     = array();
    private static $logCachesMax = 100;
    private static $logCachesNum = 0;
    private static $configs;
    private static $rsyslogConfig = array();
    private static $isDebug      = false;
    private static $logCaches    = array();
}
