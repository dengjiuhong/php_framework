<?php
/**
 * UC乐园  基础支撑  基础用户类
 *
 * @category   User
 * @package    base
 * @author Jiuhong Deng <dengjh@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace base;
class User
{
    /**
     * @desc 获取当前用户的uid
     */
    public function getUid()
    {
        return isset($this->userInfo['uid']) ? $this->userInfo['uid'] : '';
    }
    /**
     * @desc  手工设置当前uid
     * @param $uid
     */
    public function setUid($uid)
    {
        return $this->userInfo['uid'] = $uid;
    }
    /**
     * @desc 获取当前用户的详细信息
     */
    public function getUserInfo()
    {
        if (isset($this->userInfo['uid']) && !empty($this->userInfo['uid'])){
            if (self::$userInfoCache === null){
                $this->uc = $this->userCacheApi->get($this->userInfo['uid']);
                self::$userInfoCache = isset($this->uc['userInfo']) ? $this->uc['userInfo'] : array();
                self::$userInfoCache['uid'] = $this->userInfo['uid'];
            }
            return self::$userInfoCache;
        }
        return array();
    }
    /**
     * @desc 初始化当前用户的基本信息
     */
    public function init()
    {
        if (!$this->itWorks) return true;
        if (!$this->isParseCookie){
            $this->parseCookie();
        }
        return true;
    }
    /**
     * @desc 检测进来的用户是否为合法的用户
     */
    public function checkVerify()
    {
        $config   = require dirname(__FILE__) . '/../../resources/configs/Platform.inc.php';
        $userInfo = $this->getUserInfo();
        if (empty($userInfo)) return false;
        // 检测用户是否被绑定
        if ($config['isCheckBlock']){
            $userInfo['isBlock'] = isset($userInfo['isBlock']) ? $userInfo['isBlock'] : '';
            $this->log("checkVerify`checkIsBlock`" . $userInfo['isBlock']);
            if (!empty($userInfo['isBlock']) && $userInfo['isBlock'] == '2'){
                $this->showNotVerify('isblock');
            }
        }
        // 检测唯一客户端
        if ($config['isCheckClientLimit']){
            $client = $this->ucwebApi->imei . $this->ucwebApi->sn;
            $userInfo['client'] = isset($userInfo['client']) ? $userInfo['client'] : '';
            $this->log("checkVerify`checkClient`" . $client . '`' . $userInfo['client']);
            if (!empty($userInfo['client']) && $userInfo['client'] != $client){
                $this->showNotVerify('client.kid', $userInfo['client']);
            }
        }
        // 监听parseCookie的事件
        $this->event->register('login.parseCookie.Success', $userInfo);
        return true;
    }
    /**
     * @desc 显示不能进入乐园的提示
     */
    private function showNotVerify($type, $msg = '')
    {
        if (!empty($this->userInfo['uid'])){
            // 清除用户登录状态
            \framework\services\Factory::getInstance()->getService('ISso')->sigout($this->userInfo['uid'], false);
        }
        $this->userInfo = array();
        $mapper         = array(
            'mobi.empty'   => 'user_mobi_empty',
            'mobi.invalid' => 'user_mobi_invalid',
            'client.kid'   => 'user_client_limit',
            'isblock'      => 'user_block_limit'
        );
        $msg    = isset($mapper[$type]) ? $mapper[$type] : 'system_busy';
        $msg    = \utils\I18n::getInstance()->parse($msg);
        // 退出乐园
        $config  = \utils\Config::get('sso', 'Common');
        $url     = \utils\Config::get('protocol.sso', 'Common');
        $param   = array(
            'msg'     => $msg,
            // 退出也返回乐园的登录页面
            'backUrl' => \utils\Url::route('sso/login')
        );
        $param   = http_build_query($param);
        $url     = $url . 'logout.php?' . $param;
        \utils\ExceptionHandler::showBusy($msg, array(), $url, 3);
    }
    /**
     * @desc 尝试在cookie里面获取用户的登录状态
     */
    private function parseCookie()
    {
        $this->isParseCookie = true;
        $userInfo = array();
        //$this->event->register('login.parseCookie');
        // 查询session里面的数据，看看这个用户是否登录
        $userInfo = \framework\services\Factory::getInstance()->getService('ISession')->getUser();
        if (!empty($userInfo['uid'])){
            // 直接从用户cache里面拿
            $this->setUid($userInfo['uid']);
            return true;
        }
        return false;
    }
    
    /**
     * @desc  记录日志
     * @param unknown_type $msg
     * @param unknown_type $level
     */
    private function log($msg, $level = 'debug')
    {
        return \utils\Logger::writeLog($level, 'apps_base_user', $msg);
    }
    /**
     * @desc 获取到单实例
     */
    public static function getInstance()
    {
        if (self::$obj === null)  self::$obj = new self();
        return self::$obj;
    }
    /**
     * @desc 初始化类
     */
    public function __construct()
    {
        //初始化用到的接口
        $this->userApi      = \framework\services\Factory::getInstance()->getService('IUser');
        $this->event        = \framework\base\Event::getInstance();
        $this->ucwebApi     = \base\Ucweb::getInstance();
        $this->userCacheApi = \base\cache\Factory::getInstance('IndexCache');
    }
    /**
     * @desc 当前用户的详细信息
     * @var  array()
     */
    private $userInfo = array();
    /**
     * @desc 当前操作对象
     * @var  Object
     */
    public static $obj = null;
    private $event;
    private $userApi;
    private $ucwebApi;
    private static $userInfoCache;
    private $uc;
    private $userCacheApi;
    private $isParseCookie = false;
    private $itWorks = true;
}
