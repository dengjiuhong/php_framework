<?php
/**
 * UC乐园 - 基础工具  短信网关接口
 *
 * @category   sms
 * @package    utils
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace utils;
class SmsGate
{
    /**
     *    push一条手机短线到用户的手机里面去
     *
     * @param string $number    11位的手机号码
     * @param string $msg       消息的内容
     * @return boolean          是否发送成功
     * @example
     * \utils\SmsGate::getInstance()->send('13560121992', 'hello man!');
     */
    public function send($number, $msg) {
        $this->log("send\t" . $number . "\t" . $msg);
        $number = substr($number, - 11);
        if ( strlen($number) != 11 || ! $msg ) {
            return false;
        }
        // 短信网关统一使用gbk
        $prefix  = strpos($this->protocol, '?') !== false ? '&' : '?';
        $url     = $this->protocol . $prefix . 'from=' . $this->appName . "&to=" . $number . "&sms=" . urlencode(iconv('utf-8', 'gbk//IGNORE', $msg));
        $this->log("send`url`" . $url);
        $re      = \utils\HttpClient::getInstance()->get($url);
        if (trim($re) == 'OK' ) {
            $this->log("send`success`" . $number . "`" . $msg);
            return true;
        } else {
            $re = !empty($re) ? iconv('gbk', 'utf-8//IGNORE', $re) : '';
            $this->log("send`failt`" . $number . "`" . $msg . "`" . $re, 'warn');
            return false;
        }
    }
    /**
     *   获取单入口模式
     */
    public static function getInstance()
    {
        if (self::$obj == null){
            self::$obj = new self();
        }
        return self::$obj;
    }
    /**
     *    记录log
     * @param string $msg
     * @param string $level
     */
    private function log($msg, $level = 'debug')
    {
        \utils\Logger::writeLog($level, 'utils_SmsGate', $msg);
    }
    /**
     *   初始化, 读取配置
     */
    public function __construct()
    {
        $this->appName  = framework\base\Config::get('appName', 'SmsGate');
        $this->protocol = framework\base\Config::get('protocol', 'SmsGate');
        $this->timeout  = framework\base\Config::get('timeout', 'SmsGate');
    }
    public static $obj = null;
    private $timeout;
    private $appName;
    private $protocol;
}
