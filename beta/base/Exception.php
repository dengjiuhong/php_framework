<?php
declare(encoding='UTF-8');
namespace framework\base;
/**
 * UC乐园  基础支撑  异常捕捉
 *
 * @category   exception
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class ExceptionHandler
{
    /**
     * 命令行模式下面的异常报错
     * @param $exception
     */
    private static function commandException($exception)
    {
        // these are our templates
        $traceline = "#%s %s(%s): %s(%s)";
        $msg       = "Uncaught exception: '%s' exit with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";
        // alter your trace as you please, here
        $trace     = $exception->getTrace();
        foreach ($trace as $key => $stackPoint) {
            // I'm converting arguments to their type
            // (prevents passwords from ever getting logged as anything other than 'string')
            $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
        }
        // build your tracelines
        $result = array();
        $key    = '';
        foreach ($trace as $key => $stackPoint) {
            $stackPoint['file']     = isset($stackPoint['file']) ? $stackPoint['file'] : '';
            $stackPoint['line']     = isset($stackPoint['line']) ? $stackPoint['line'] : '';
            $stackPoint['function'] = isset($stackPoint['function']) ? $stackPoint['function'] : '';
            $stackPoint['args']     = isset($stackPoint['args']) ? $stackPoint['args'] : array();
            $result[] = sprintf(
                $traceline,
                $key,
                $stackPoint['file'],
                $stackPoint['line'],
                $stackPoint['function'],
                implode(', ', $stackPoint['args'])
            );
        }
        // trace always ends with {main}
        $result[] = '#' . ++ $key . ' {main}';
        // write tracelines into main template
        $tmp = sprintf(
            $msg,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            implode("\n", $result),
            $exception->getFile(),
            $exception->getLine()
        );
        print_r($tmp);
        exit();
    }
    /**
     *    监听未捕捉的异常
     * @param object $exception  - 异常对象
     */
    public static function init($exception)
    {
        if (defined('UZONE_COMMAND')){
            // 命令行模式，打印命令行的信息
            self::commandException($exception);
        }
        // these are our templates
        $traceline = "#%s %s(%s): %s(%s)";
        $msg       = "Uncaught exception: '%s' exit with message '%s' in %s:%s<br />\nStack trace:<br />\n%s<br />\n  thrown in %s on line %s";
        // alter your trace as you please, here
        $trace = $exception->getTrace();
        foreach ($trace as $key => $stackPoint) {
            // I'm converting arguments to their type
            // (prevents passwords from ever getting logged as anything other than 'string')
            $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
        }
        // build your tracelines
        $result = array();
        $key    = '';
        foreach ($trace as $key => $stackPoint) {
            $stackPoint['file']     = isset($stackPoint['file']) ? $stackPoint['file'] : '';
            $stackPoint['line']     = isset($stackPoint['line']) ? $stackPoint['line'] : '';
            $stackPoint['function'] = isset($stackPoint['function']) ? $stackPoint['function'] : '';
            $stackPoint['args']     = isset($stackPoint['args']) ? $stackPoint['args'] : array();
            $result[] = sprintf(
                $traceline,
                $key,
                $stackPoint['file'],
                $stackPoint['line'],
                $stackPoint['function'],
                implode(', ', $stackPoint['args'])
            );
        }
        // trace always ends with {main}
        $result[] = '#' . ++ $key . ' {main}';
        // write tracelines into main template

        $tmp = sprintf(
            $msg,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            implode("<br />\n", $result),
            $exception->getFile(),
            $exception->getLine()
        );
        // 记 error log
        Logger::e('exception', $tmp);
        $isDebug = Config::get('debug', 'Common');
        if (!$isDebug){
            // 生产环境
            $msg  = '系统繁忙, 请稍候再试。';
        } else {
            $msg  = "<h1>Exception:</h1>\n";
            $msg .= $tmp;
            $msg .= '</pre>';
        }
        $vars = array(
            'url'  => 'ext:back',
            'name' => '返回'
        );
        self::showBusy($msg, $vars);
    }
    /**
     *    显示异常提示信息页面
     *
     * @param String $msg   - 提示内容
     * @param array  $vars  - 额外参数
     */
    public static function showBusy($msg, $vars = array(), $redirect = '', $redirectTime = 0)
    {
        @ob_clean();
        $imgUrl = Config::get('domain.static', 'Common');
        $vars['state'] = isset($vars['state']) ? $vars['state'] : 'notice3';
        $vars['url']   = isset($vars['url']) ? $vars['url'] : 'ext:back';
        $vars['name']  = isset($vars['name']) ? $vars['name'] : '返回';
        $redirectHtml  = '';
        if (!empty($redirect)){
            $redirectHtml = '<meta http-equiv="refresh" content="' . $redirectTime . ';url=' . $redirect . '">';
            $vars['url'] = $redirect;
        }
        $html   = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>乐园提示</title>
<style>*{color:#373737;font-size:small;}.limit{background-color:#cde8f9;border-top:solid 1px #add6f1}img {border:0;}.login {background-color:#eaf2fc;border-top:solid 1px #bed7f8;border-bottom:solid 1px #bed7f8;}.login_footer {color:#797979;}.login_msg {background-color:#f8ebd3;border-top:solid 1px #fbcba2;}a {color:#2c85ff;text-decoration:none;}.jiange {width:100%;}</style>
'.$redirectHtml.'
<meta name="viewport" content="width=device-width; initial-scale=1.0; minimum-scale=1.0; maximum-scale=2.0"/>
</head>
<body>
<img src="'.$imgUrl.'images/web/logo.gif" alt="UC乐园" uc-margin="0" noselect="true" />
<div class="login">
<div class="jiange"><img src="'.$imgUrl.'images/web/blank.gif" width="1" height="2" noselect="true" uc-margin="0" /></div>
&nbsp;<img src="'.$imgUrl.'images/web/'. $vars['state'] . '.png" alt="" width="16" height="16" noselect="true" />&nbsp;'.$msg.'
<div class="jiange"><img src="'.$imgUrl.'images/web/blank.gif" width="1" height="2" noselect="true" uc-margin="0" /></div>
</div>
<img src="'.$imgUrl.'images/web/skin1/shadow.gif" alt="" width="129" height="4" noselect="true" uc-margin="0" />
<div class="jiange"><img src="'.$imgUrl.'images/web/blank.gif" width="1" height="1" noselect="true" uc-margin="0" /></div>
<img src="'.$imgUrl.'images/web/arrow.gif" width="14" height="13" alt="返回" noselect="true" /><a href="'.$vars['url'].'">'.$vars['name'].'</a>
</div>
<div class="limit">
    <div class="jiange"><img src="'.$imgUrl.'images/sso/blank.gif" width="1" height="5" noselect="true" uc-margin="0" /></div>
    UC乐园是真实身份的手机社区
    <div class="jiange"><img src="'.$imgUrl.'images/sso/blank.gif" width="1" height="5" noselect="true" uc-margin="0" /></div>
</div>
<div class="jiange"><img src="'.$imgUrl.'images/sso/blank.gif" width="1" height="5" noselect="true" uc-margin="0" /></div>
<span class="boldfont">加入UC乐园你可以：</span>
<div class="jiange"><img src="'.$imgUrl.'images/sso/blank.gif" width="1" height="5" noselect="true" uc-margin="0" /></div>
1.联系你手机上和身边的真实朋友
<div class="jiange"><img src="'.$imgUrl.'images/sso/blank.gif" width="1" height="5" noselect="true" uc-margin="0" /></div>
<div class="boder"></div>
<div class="jiange"><img src="'.$imgUrl.'images/sso/blank.gif" width="1" height="5" noselect="true" uc-margin="0" /></div>
2.通过照片文字随时记录身边最真实的事
<div class="jiange"><img src="'.$imgUrl.'images/sso/blank.gif" width="1" height="5" noselect="true" uc-margin="0" /></div>
<div class="boder"></div>
<div class="jiange"><img src="'.$imgUrl.'images/sso/blank.gif" width="1" height="5" noselect="true" uc-margin="0" /></div>
3.和自己朋友一起分享每天的所见所闻
<div class="jiange"><img src="'.$imgUrl.'images/sso/blank.gif" width="1" height="5" noselect="true" uc-margin="0" /></div>
<div class="boder"></div>
<div class="jiange"><img src="'.$imgUrl.'images/sso/blank.gif" width="1" height="5" noselect="true" uc-margin="0" /></div>
4.和朋友一起玩最棒的游戏
<br />
<img src="'.$imgUrl.'images/sso/newgames.gif" width="186" height="40" noselect="true" uc-margin="0"/>
<div class="jiange"><img src="'.$imgUrl.'images/sso/blank.gif" width="1" height="5" noselect="true" uc-margin="0" /></div>
<span class="login_footer">'. date("Y年m月d日, G:i:s").'</span><br />
优视科技 2010 © 版权所有 </body></html>';
        exit($html);
    }
    /**
     *    记录php错误日志
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $log     = date("Ymd H:i:s") . "`" . $errno . "`" . $errstr . "`" . $errfile . "`" . $errline;
        // 记 error log
        $isDebug = Config::get('debug', 'Common');
        $msg     = "";
        $isExit  = false;
        $level   = '';
        switch ($errno) {
            case E_USER_ERROR:
                    $level = 'error';
                    $log .= "`" . json_encode(debug_backtrace());
                    $msg .= "<b>My ERROR</b> [$errno] $errstr<br />\n";
                    $msg .= "  Fatal error on line $errline in file $errfile";
                    $msg .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
                    $msg .= "Aborting...<br />\n";
                    $msg .= "<pre>";
                    $msg .= var_export(debug_backtrace(), true);
                    $msg .= '</pre>';
                    $isExit = true;
                break;
            case E_USER_WARNING:
                $level = 'warn';
                $log .= "`" . json_encode(debug_backtrace());
                $msg .= "<b>My WARNING</b> [$errno] $errstr<br />\n";
                break;
            case E_USER_NOTICE:
                $level = 'notice';
                $msg .= "<b>My NOTICE</b> [$errno] $errstr<br />\n";
                break;
            case E_NOTICE:
                $level = 'notice';
                $msg .= "<b>My NOTICE</b> [$errno] $errstr<br />\n";
                break;
            case E_STRICT:
                $level = 'strict';
                $msg .= "<b>My STRICT</b> [$errno] $errstr<br />\n";
                break;
                break;
            default:
                $level = 'unknow';
                $log .= "`" . json_encode(debug_backtrace());
                $msg .= "Unknown error type: [$errno] $errstr<br />\n";
                break;
            }
        // 记log
        Logger::p($level, $log);
        $m = array(
            'strict'
        );
        if ($isExit){
            $msg = $isDebug ? $msg : '系统繁忙(001)';
            self::showBusy($msg);
        } else {
            if ($isDebug && !in_array($level, $m)){
                echo '<pre>';
                echo $msg;
                echo '</pre>';
            }
        }
        return true;
    }
    /**
     *   设置php错误捕捉器
     */
    public static function setErrorHandler()
    {
        //set_error_handler(array("\framework\base\Exception", 'errorHandler'));
    }
    /**
     *    设置是否为开发环境
     * @param bool    $isDev   - 是否为开发环境
     */
    public static function setIsDev($isDev = true)
    {
        self::$isDev = $isDev;
    }
    public static $isDev = true;
}
/**
 * UC乐园  基础支撑  最上传层异常捕捉, 继承于exception
 *
 * @category   Exception
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class Exception extends \Exception
{
    /**
     * @var null|Exception
     */
    private $_previous = null;

    /**
     * Construct the exception
     *
     * @param  string $msg
     * @param  int $code
     * @param  Exception $previous
     * @return void
     */
    public function __construct($msg = '', $code = 0, \framework\base\Exception $previous = null)
    {
        parent::__construct($msg, (int)$code);
        $this->_previous = $previous;
    }
}
/**
 * UC乐园  基础支撑  control层异常捕捉
 *
 * @category   Exception
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class ControlException extends \framework\base\Exception
{

}

/**
 * UC乐园  基础支撑  apps异常捕捉
 *
 * @category   Exception
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class AppsException extends \framework\base\Exception
{

}
/**
 * UC乐园  基础支撑  应用层异常捕捉
 *
 * @category   Exception
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class ServiceException extends \framework\base\Exception
{

}
/**
 * UC乐园  基础支撑  Dal层异常捕捉
 *
 * @category   Exception
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class DalException extends \framework\base\Exception
{

}
/**
 * UC乐园  基础支撑  dao层异常捕捉
 *
 * @category   Exception
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class DaoException extends \framework\base\Exception
{

}

/**
 * UC乐园  基础支撑  Models层异常捕捉
 *
 * @category   Exception
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class ModelsException extends \framework\base\Exception
{

}
/**
 * UC乐园  基础支撑  Tasks层异常捕捉
 *
 * @category   Exception
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class TasksException extends \framework\base\Exception
{

}
/**
 * UC乐园  基础支撑  工具层异常捕捉
 *
 * @category   Exception
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class UtilsException extends \framework\base\Exception
{

}