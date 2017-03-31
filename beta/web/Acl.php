<?php
/**
 * UC乐园  基础支撑  权限控制
 *
 * @category   base
 * @package    apps
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace framework\web;
class Acl
{
    /**
     *   检测访问权限
     */
    public function auth()
    {
        // 访问限制
        $route  = isset(self::$conf['access'][$this->control]) ? self::$conf['access'][$this->control] : $this->defaultRoute;
        if (is_array($route)){
            if (in_array($this->action, $route)){
                // 检测身份
                $this->checkIsGuest();
            }
        }
        // 默认全部需要校验
        if ($route == '*'){
            $this->checkIsGuest();
        }
        // 检测是否允许发言, 默认是没有限制
        $post = isset(self::$conf['post'][$this->control]) ? self::$conf['post'][$this->control] : $this->defaultRoute;
        if ($post !== '*' && is_array($post)){
            if (in_array($this->action, $post)){
                // 检测是否允许发言
                $this->checkReadOnly();
            }
        }
        return true;
    }
    /**
     *   检测是否允许发言
     */
    public function checkReadOnly()
    {
        $userApi = \utils\Bridge::getInstance()->load('snsapiUser', false);
        $userApi->rejectReadOnlyUser(self::$uid);
    }
    /**
     *   检测是否已经登陆
     */
    public function checkIsGuest()
    {
        if (empty(self::$uid)){
            // 判断是否为用户中心的用户
            $isUser  = \services\Factory::getInstance()->getService('ISso')->checkIsUser();
            $isUser  = !$isUser ? '3' : $isUser;
            // 这里的$isUser 有三种情况
            // 1 已经是乐园用户
            // 2 是用户中心的用户，但是不是乐园的帐号
            // 3 全新的用户
            // 跳转逻辑
            $uzoneApi = \apps\base\Uzone::getInstance();
            $res      = $uzoneApi->getRoute();
            // 退出登录的页面不做这个检查
            if (isset($res[0]) && $res[0] == 'sso' && isset($res[1]) && $res[1] == 'sigout'){
                return false;
            }
            if (isset($res[0]) && $res[0] == 'sso' && isset($res[1]) && $res[1] == 'login'){
                return false;
            }
            $routeMapper = array(
                '1' => array('sso', 'login'),         // 已经是乐园的用户，直接登录
                '2' => array('sso', 'registerGuide'), // 引导页面
                '3' => array('sso', 'registerGuide')  // 引导页面
            );
            $uzoneApi = \apps\base\Uzone::getInstance();
            $preRoute = $uzoneApi->getRoute();
            $route    = $routeMapper[$isUser];
            $uzoneApi->setRoute($route[0], $route[1]);
            $request  = \apps\base\Request::getInstance();
            $request->setGetParamManual('isUser', $isUser);
            $param = array();
            foreach($_GET as $k => $v){
                if (in_array($k, array('li', 'gi', 'wi', 'gid', 'dn', 'pf', 'ss', 'uc_param_str'))) continue;
                $param[$k] = $v;
            }
            $param['r'] = implode('/', $preRoute);
            $backUrl  = framework\base\Config::get('domain.base', 'Common') . "&" . http_build_query($param);
            $request->setGetParamManual('backUrl', $backUrl);
            return false;
        }
        return true;
    }
    /**
     *   初始化配置
     * @param $control  - 当前 control
     * @param $action   - 当前 action
     */
    public function __construct($control, $action, $conf = '')
    {
        $file          = empty($conf) ? dirname(__FILE__) . '/../configs/Acl.inc.php' : $conf;
        self::$conf    = include $file;
        $this->control = $control;
        $this->action  = $action;
        self::$uid     = \apps\base\User::getInstance()->getUid();
    }
    /**
     *    取得单实例
     * @param string $control
     * @param string $action
     */
    public static function getInstance($control, $action, $config = '')
    {
        if (self::$obj == null){
            self::$obj = new self($control, $action, $config);
        }
        return self::$obj;
    }
    public static $uid;
    public static $conf  = array();
    public static $obj   = null;
    public $defaultRoute = '*';
    public $control   = '';
    public $action    = '';
    const SSO_CONTROL = 'sso';
    const SSO_LOGIN   = 'login';
    const SSO_REG     = 'registerGuide';
}

