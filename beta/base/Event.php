<?php
declare(encoding='UTF-8');
namespace framework\base;
use \framework\base\AppsException as Exception;
/**
 * UC乐园  基础支撑  事件捕捉
 *
 * @category   event
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class Event
{
    /**
     *   系统开始的时候执行
     */
    public function onRun()
    {
        if (!empty($this->configs['onRun'])){
            foreach($this->configs['onRun'] as $key => $class){
                $this->callEvents($key, $class);
            }
        }
        return true;
    }
    /**
     *   系统执行完之后触发的事件
     */
    public function onFinish()
    {
        // 先断开链接, 将用户需要用到的信息反馈给用户
        // 依赖 fastcgi_finish_request 这个特性
        if (function_exists('fastcgi_finish_request') && Config::get('fastcgiFinishRequest', 'Common')){
            //\fastcgi_finish_request();
        }
        if (!empty($this->configs['onFinish'])){
            foreach($this->configs['onFinish'] as $key => $class){
                $this->callEvents($key, $class);
            }
        }
        // 
        \framework\base\Logger::flush();
        return true;
    }
    /**
     *    根据不同的条件唤醒不同的事件捕捉类
     * @param $event   - 事件的名称
     * @param $class   - 事件处理的类
     */
    public function callEvents($event, $class)
    {
        $event = $this->bind($event);
        call_user_func($class, $event);
    }
    /**
     *   取得单实例模式
     */
    public static function getInstance($configs = array())
    {
        if (self::$obj == null){
            self::$obj = new self($configs);
        }
        return self::$obj;
    }
    /**
     *     监听一个事件
     * @param  string  $event   - 需要监听的事件
     * @return mix              - 该事件在注册的时候记录的变量
     */
    public function bind($event)
    {
        if (isset(self::$events[$event])){
            $tmp = self::$events[$event];
            unset(self::$events[$event]);
            return $tmp;
        }
        return false;
    }
    /**
     *             显示现在监听的
     * @return array   - 返回现在监听到的事件
     */
    public function getList()
    {
        return self::$events;
    }
    /**
     *    注册一个事件
     * @param $event   - 事件的名称
     * @param $param   - 事件的属性
     * @param $isSync  - 是否异步
     * @return bool    - 是否注册成功
     */
    public function register($event, $param = array(), $isSync = false)
    {
        if (!$isSync){
            $tmp = explode('.', $event);
            $key = isset($tmp[0]) ? $tmp[0] : '';
            self::$events[$key] = isset(self::$events[$key]) ? self::$events[$key] : array();
            array_push(self::$events[$key], array(
                'category' => $event,
                'param'    => $param
            ));
        } else {
            throw new Exception("not implement yet ... ");
        }
    }
    /**
     *   初始化，读取基础配置
     */
    public function __construct($configs = array())
    {
        $global = Config::getGlobal();
        $config = isset($global['base']['events']) ? $global['base']['events'] : array();
        $this->configs = $configs ? $configs : $config;
        register_shutdown_function(array($this, 'onFinish'));
    }
    /**
     * @var Event  - 单实例对象
     */
    public static $obj       = null;
    /**
     * @var ArrayObject   - 事件的配置
     */
    private $configs          = array();
    /**
     * @var ArrayObject   - 缓存起来的事件
     */

    private static $events    = array();
    public static $isShutdown = false;
}
