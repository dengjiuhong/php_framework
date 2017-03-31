<?php
declare(encoding='UTF-8');
namespace framework\base;
use \framework\utils\XHProf as XHProf;
use \framework\base\Event as Event;
use \framework\web\Request as Request;
use \framework\web\View as View;
/**
 * UC乐园  基础支撑平台
 *
 * @category   Web
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class Web
{
    /**
     *   初始化乐园基础类
     */
    public function __construct($configs = array())
    {
        $this->configs = $configs;
        $this->init();
    }
    /**
     *   执行乐园程序
     */
    public function run()
    {
        $this->log("run");
        $this->event->register('appstart');
        $this->event->onRun();
        // 初始化模板
        $this->routeLocked = true;
        // 简单使用路由
        if (isset($this->configs['routesLimit'])) {
            if (!in_array(self::$control, $this->configs['routesLimit'])){
                self::$control = $this->configs['control']['default'][0];
                self::$action  = $this->configs['control']['default'][1];
            }
        }
        // 初始化模板
        View::getInstance($this->configs['web']['view']);
        // 
        $file = $this->configs['web']['basePath'] . 'modules/' . strtolower(self::$control) . '/controls/' . strtolower(self::$platform) . '/Control.php';
        
        if (is_file($file)){
            $this->excue($file);
        } else {
            $file = $this->configs['web']['basePath'] . 'modules/' . strtolower(self::$control) . '/controls/' . self::DEFAULT_PLATFORM . '/Control.php';
            if (is_file($file)){
                 $this->excue($file);
            } else {
                throw new Exception('control file not found' . $file, 404);
            }
        }
    }
    /**
     *    手工设置修改路由, 注意，需要在excue之前执行
     * @param $control   - 当前control
     * @param $action    - 当前action
     */
    public function setRoute($control, $action)
    {
        if ($this->routeLocked) return false;
        $this->preRoute = array(self::$control, self::$action);
        self::$control  = $control;
        self::$action   = $action;
        $this->routeLocked = true;
        return true;
    }
    /**
     *   获取当前路由
     *
     * @return array(
     *     '[control]', '[action]'
     * )
     */
    public function getRoute()
    {
        return array(self::$control, self::$action);
    }
    /**
     *   获取站内跳转的上一个路由
     */
    public function getPreRoute()
    {
        return $this->preRoute;
    }
    /**
     *   执行
     * @param  string  $file  - control
     * @return null
     */
    public function excue($file)
    {
        require $file;
        $configs   = isset($this->configs['control']) ? $this->configs['control'] : array();
        $className = ucfirst(self::$control) . 'Control';
        $obj       = new $className($configs);
        $action    = 'action' . ucfirst(self::$action);
        $obj->$action();
    }
    /**
     *   初始化
     */
    public function init()
    {
        $this->_initConfigs();
        $this->_initInput();
        $this->_initRoute();
        $this->_initEvent();
        $this->_initEnv();
        $this->_initPlatform();
        $this->_initLanguage();
        $this->_initOutPut();
    }

    /**
     *   初始化事件
     */
    private function _initEvent()
    {
        $this->log("_initEvent");
        $this->event = Event::getInstance(isset($this->configs['events']) ? $this->configs['events'] : array());
    }
    /**
     *   初始化路由控制
     */
    private function _initRoute()
    {
        $this->log("_initRoute");
        if (isset($_REQUEST['GLOBALS']) OR isset($_FILES['GLOBALS'])) {
            throw new Exception('Request tainting attempted.', 500);
        }
        // 是否启用短域名, 节约流量
        //$route         = require dirname(__FILE__) . '/../configs/Route.inc.php';
        $route         = array();
        $uzoneModel    = isset($_GET[self::DEFAULT_ROUTE]) ? strtolower($_GET[self::DEFAULT_ROUTE]) : self::DEFAULT_CONTROL;
        $uzoneModel    = explode('/', $uzoneModel);
        self::$control = strtolower($uzoneModel[0]);
        self::$control = (isset($route['control'][self::$control])) ? $route['control'][self::$control] : self::$control;
        if (isset($uzoneModel[1]) && !empty($uzoneModel[1])){
            self::$action = isset($route['action'][$uzoneModel[1]]) ? $route['action'][$uzoneModel[1]] : $uzoneModel[1];
        } else {
            self::$action  = isset($uzoneModel[1]) && !empty($uzoneModel[1]) ? $uzoneModel[1] : self::DEFAULT_ACTION;
        }
        return true;
    }
    /**
     *    初始化当前环境
     */
    private function _initEnv()
    {
        $this->log("_initEnv");
    }
    /**
     *   初始化配置
     */
    private function _initConfigs()
    {
        // 不再需要初始化
        $this->log("_initConfigs");
        Config::initGlobal($this->configs);
    }
    /**
     *   初始化输入数据
     */
    private function _initInput()
    {
        $this->log("_initInput");
        return Request::getInstance();
    }
    /**
     *   初始化输出数据
     */
    private function _initOutPut()
    {
        $this->log("_initOutPut");
//        // 安全检测
//        if(!empty($_SERVER['REQUEST_URI'])) {
//            $temp = urldecode($_SERVER['REQUEST_URI']);
////            if(strpos($temp, '<') !== false || strpos($temp, '"') !== false) {
////                throw new Exception('request_tainting', 500);
////            }
//        }
//        //ob_start('ob_gzhandler');
//        // 强制输出 utf-8 的头
        @header('Content-Type: text/html; charset=' . $this->configs['web']['charset']);
    }
    /**
     *   初始化平台类型
     */
    private function _initPlatform()
    {
        self::$platform = '';
    }
    /**
     *   初始化语言包
     */
    private function _initLanguage()
    {
        self::$language = 'zh_CN';
    }
    /**
     *   初始化当前时区
     */
    public function initTimezone() {
        //if(function_exists('date_default_timezone_set')) {
            //@date_default_timezone_set('Etc/GMT'.($this->config['timeoffset'] > 0 ? '-' : '+').(abs($this->config['timeoffset'])));
        //}
    }
    /**
     * 手工设置当前platform
     * @param unknown_type $platform
     */
    public function setPlatfrom($platform)
    {
        return self::$platform = $platform;
    }
    /**
     * 本地log
     * @param string $msg
     * @param string $level
     */
    private function log($msg, $level = 'debug')
    {
        //TODO
    }
    public static function getInstance($configs = array())
    {
        if (self::$obj === null){
            self::$obj = new self($configs);
        }
        return self::$obj;
    }
    public $var             = array();
    public $config          = array();
    public $configs         = array();
    private $event          = '';
    const IS_DEV            = true;
    const DEFAULT_ROUTE     = 'r';
    const DEFAULT_CONTROL   = 'profile';
    const DEFAULT_ACTION    = 'index';
    const DEFAULT_PLATFORM  = 'v3';
    public static $control  = '';
    public static $action   = '';
    public static $platform = '';
    public static $language = '';
    private $routeLocked    = false;
    private static $isInitProf = false;
    private $preRoute       = array();
    public static $obj;
}
