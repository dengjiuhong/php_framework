<?php
declare(encoding='UTF-8');
namespace framework\base;
use framework\web\Request as Request;
use framework\base\I18n as I18n;
use framework\web\View as View;
use framwork\web\Validator as Validator;
/**
 * UC乐园基础框架 - 基础类 - control 层
 *
 * @category   control
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class Control
{
    /**
     *    设置当前action的网页标题
     *
     * @param string  $title  当前的网页标题
     */
    public function setTitle($title = '')
    {
        $this->title = $title;
        return View::getInstance()->setTitile($title);
    }
    /**
     *   设置页面的布局方法
     *
     * @param  string  $layout  - 布局的文件名字，如果为空，则不使用布局规则
     * @return layout          - 返回当前的布局文件名字
     * @example
     *    layout 设为main的时候, 使用标准XHtml 布局
     *    layout 设为空的时候，不使用布局文件
     */
    public function setLayout($layout = '')
    {
        $this->layout = $layout;
        return View::getInstance()->setLayout($layout);
    }
    /**
     *    显示一个模板
     *
     * @param string  $tpl   - 要显示的模板, 默认使用当前action对应的模板
     * @param array   $vars  - 模板要设置进去的变量
     */
    public function render($tpl = '', $vars = array())
    {
        $fileName =  View::getInstance()->render($tpl, $vars);
        $_tpl_vars = array_merge($vars, View::getInstance()->vars);
        extract($_tpl_vars, EXTR_OVERWRITE);
        require $fileName;
    }
    /**
     *    通过302跳转
     *
     * @param $url     - 目标的完整Url
     * @param $isProxy - 是否为中转模式 默认为直连
     * @param $header  - 是否带自定义的header
     * @param $type    - 如果type为非空，是防止跳转太多出现的中间页面
     */
    public function go($url, $isProxy = false, $header = array(), $type = '')
    {
        // 注册一个条转的事件
        Event::getInstance()->register(self::EVENT_REDIRECT_302, array(
            'srcControl' => Web::$control,
            'srcAction'  => Web::$action,
            'dstUrl'     => $url
        ));
        // 判断url里面的特殊标签
        // 如果有 metarefresh=1, 使用metarefresh的方式跳转
        if (strpos($url, 'metarefresh=1')) {
            $type = 'metarefresh';
        }
        if (!$isProxy){
            header("direct-wap: 1");
        }
        // 设置自定义的header
        foreach($header as $key => $v){
            header($key . ": " . $v);
        }
        // 如果url是以http%3A%2F%2F 开头，手工urldecode一下
        if (strpos($url, 'http%3A%2F%2F') === 0){
            // 应用可能多次urlencode
            // 在这里做多一次urldecode
            $url = urldecode($url);
        }
        // 在url后面加个特殊的标签，标记来源于302跳转
        $prefix      = strpos($url,'?') !== false ? '&' : '?';
        $res         = parse_url($url);
        if (empty($res['query']) && empty($res['path'])){
            $prefix = '/?';
        }
        if (empty($type)){
        $url  = $url . $prefix . "302=1";
            header("Location: " . $url, true, 302);
        } else {
            if ($isProxy) {
                $url = 'ext:e:' . $url;
            }
            $this->redirectProxy($url, 0);
        }
        // 停止当前操作
        $this->end();
    }
    /**
     *    自动跳转的中间页面 （防止手机不支持跳转太多）
     * @param string  $url   - 目标url
     * @param numeric $time  - 间隔时间
     */
    private function redirectProxy($url, $time)
    {
        $date    = date("Y年m月d日, G:i:s");
        echo '
        <?xml version="1.0" encoding="UTF-8"?>
        <!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <title>正在加载中</title>
                <meta http-equiv="refresh" content="'.$time.';url='.$url.'">
            </head>
            <body>
                <div class="login"> &nbsp;&nbsp;<a href="'.$url.'">正在加载中，如果页面长时间无自动跳转，请点击这里。</a></div>
                <span class="login_footer">' . $date . '</span><br />
                ©UC mobile 2010
            </body>
        </html>
        ';
    }
    /**
     *    程序内部跳转
     *
     * @param \apps\base\Control  $class  - 负责处理的cntrol
     * @param String       $action - 负责处理的方法
     * @param Array        $vars   - 要传递的参数
     * @param String       $pf     - 需要跳转的平台号
     * @return mix                 - 跳转目标的返回数据
     */
    public function redirect($class, $action, $vars = array(), $pf = '')
    {
        // 跳转之前，先flush一把
        Event::shutDownCallBack();
        $control = '';
        if (!is_object($class) || $class !== $this){
            $srcControl = get_class($this);
            $control    = $class;
            $className  = ucfirst($control) . 'Control';
            if (!class_exists($class, false)){
                $class = $this->loadControl($control, $pf, $className);
            }
            $dstControl = strtolower($control);
            // 修改当前的action和control
            Web::$control = strtolower($control) ;
            View::getInstance()->setControl(strtolower($control));
        } else {
            $srcControl = $dstControl = get_class($class);
        }
        $srcAction      = Web::$action;
        Web::$action    = $action;
        View::getInstance()->setAction($action);
        $this->action   = $action;
        // 注册个跳转的事件
        $this->bindRedirectEvent($srcControl, $dstControl, $srcAction, $action);
        $class->isShowTips = $this->isShowTips;
        $class->tipsMsg    = $this->tipsMsg;
        $class->tipsState  = $this->tipsState;
        if (!empty($control)){
            $class->control    = strtolower($control);
        }
        // 还原默认layout
        $class->setLayout('main');
        $class->action     = $action;
        $class->setRedirectVar($vars);
        $class->request->setRequestMethod('GET');
        // 停止当前当前动作
        // 调用目标control, action处理请求
        call_user_func_array(array($class, 'action' . ucfirst($action)), $vars);
        $this->end();
    }
    /**
     *    注册站内跳转的事件
     * @param String $srcControl  - 初始的Control名字
     * @param String $dstControl  - 目标的Control名字
     * @param String $srcAction   - 初始的Action名字
     * @param String $dstAction   - 目标的Action名字
     */
    private function bindRedirectEvent($srcControl, $dstControl, $srcAction, $dstAction)
    {
        Event::getInstance()->register(self::EVENT_REDIRECT, array(
            'srcControl' => $srcControl,
            'dstControl' => $dstControl,
            'srcAction'  => $srcAction,
            'dstAction'  => $dstAction
        ));
    }
    /**
     *    自动加载control类
     *
     * @param String $class     -  Control 的名字
     * @param String $pf        - 平台的名字
     * @param String $className - 类的名字
     * @return \apps\base\Control      - 该类的对象
     */
    private function loadControl($class, $pf = '', $className = '')
    {
        // 手工加载control
        $pf = empty($pf) ? Web::$platform : $pf;
        if (!isset(self::$classs[$class . $pf])){
            $file     = PROJECT_BASEPATH . '/apps/modules/' . strtolower($class) . '/controls/' . $pf . '/Control.php';
            if (!is_file($file)){
                $file = PROJECT_BASEPATH . '/apps/modules/' . strtolower($class) . '/controls/v3/Control.php';
            }
            require $file;
            self::$classs[$class . $pf] = new $className();
        }
        return self::$classs[$class . $pf];
    }
    /**
     *    设置通用的跨页面提示块
     *
     * @param $state  - 提示的状态
     * @param $msg    - 提示的消息
     * @param $key    - 提示的类型
     * @return boolean  - 是否设置成功
     */
    public function setLiteTips($state, $msg, $key = '')
    {
        $this->isShowTips = true;
        $this->tipsState  = $state;
        $this->tipsMsg    = $msg;
        return true;
    }
    /**
     *    显示统一的页面提示页面
     *
     * @param String  $level - 提示等级 error | notice | success | warning
     * @param String  $msg   - 提示信息的标记
     * @param String  $title - 提示页面的标题
     * @param Array   $route - 自动跳转的目的地址
     *
     * @return void
     */
    public function showTips($level, $msg, $url = '')
    {
        // 具体页面显示待完善
        $vars = array(
            'state'=> $level,
            'url'  => empty($url) ? 'ext:back' : $url,
            'time' => empty($url) ? 0 : 3,
            'name' => '返回'
        );
        //@ob_clean();
        View::getInstance()->showBusy($msg, $vars);
        $this->end();
    }
    /**
     *   获取从其他control跳转过来的时候，传递的数据
     * @return ArrayObject
     */
    public function getRedirectVar()
    {
        return $this->redirectVar;
    }
    /**
     *     设置从其他control跳转过来的时候，传递的数据
     *
     * @param  ArrayObject   $var   - 传递的数据
     * @return 当前的传递的数据
     */
    public function setRedirectVar($var = array())
    {
        $this->redirectVar = $var;
    }
    /**
     *   统一的表单校验
     *
     * @param  array   $attr  - 表单校验的数据
     * @example
     * $attr = array(
     *     'realName' => array($this->request->post('realName'), array('required', 'length' => array(0, 10))),
     *     'email'    => array($htis->request->post('email'), 'email'),
     *     'mobi'     => array($this->request->post('mobi'), 'mobi'),
     *     'content'  => array($this->request->post('content'), array('required', 'length' => array(0, 70), 'audit'))
     * );
     * @param  &array  $errors - 返回的错误提示
     * @return boolean         - 是否通过校验
     */
    public function valid(&$attr = array(), &$errors = array())
    {
        return Validator::getInstance()->valid($attr, $errors);
    }
    /**
     *   自动生成表单的校验
     */
    public function getFormVerify()
    {
        $attr = array(
            Validator::getInstance()->buildFormHash()
        );
        return implode("\n", $attr) . "\n";
    }
    /**
     *   停止当前control, render redirect, go 回自动调用end()
     */
    public function end()
    {
        $this->writeLog("end");
        // 直接退出
        exit();
    }
    /**
     *   记录control层的普通日志
     *
     * @param $msg   - 日志的内容
     * @param $level - 日志的等级别
     * @return bool  - 是否记录成功
     */
    public function writeLog($msg, $level = 'debug')
    {
    return Logger::writeLog($level, 'apps_modules_' . self::$obj->control . "_" . self::$obj->action, $msg);
    }
    /**
     *    记录control层的业务日志log
     *
     * @param mix  $msg      - 信息
     * @param string  $level - log的等级
     */
    public function log($msg, $app = '')
    {
        Logger::writeAppLog($this->control, $this->action, $msg, $app);
    }
    /**
     *   初始化 control 父类
     */
    public function __construct()
    {
        // 初始化的时候调用
        $this->request = Request::getInstance();
        $this->i18n    = I18n::getInstance();
        
        // 获取当前浏览器参数结束
        $this->control     = Web::$control;
        $this->action      = Web::$action;
        self::$obj         = $this;
    }
    /**
     *    解析配置
     * @param array $configs
     */
    private function configure($configs)
    {
        if (empty($configs)) return false;
        $this->isLoadUserCache = isset($configs['isLoadUserCache']) && $configs['isLoadUserCache'] ? true : false;
        $this->isInitUser = isset($configs['isInitUser']) && $configs['isInitUser'] ? true : false;
        return true;
     }
    /**
     *   析构 control 父类
     */
    public function __destruct()
    {
        // action执行完之后触发
        unset($this->uc);
    }
    /**
     * @var ArrayObject 从其他control跳转过来的时候，传递的数据
     */
    private $redirectVar  = array();
    /**
     *   多国语言对象
     * @var  \utils\I18n
     */
    public $i18n          = null;
    /**
     *   请求统一处理的对象
     * @var \apps\base\Request
     */
    public $request       = null;
    /**
     *   用户缓存
     * @var  array()
     */
    private static $uc = array();
    /**
     *   自动加载其他的Control用到的常量
     * @var  ArrayObject  - 每个实例化过的对象
     */
    private static $classs = array();
    public $title            = '';
    public $layout           = '';
    public $isShowTips       = false;
    public $tipsState        = '';
    public $tipsMsg          = '';
    public $isInitUser       = true;
    public $isLoadUserCache  = true;
    public $ucweb            = '';
    public static $obj;
    private static $isModify = false;
    /**
     *   当前手机浏览器的参数
     * @var  array()
     */
    protected $uc_param    = array();
    /**
     *   当前手机的平台
     * @var  String
     */
    protected $platform    = '';
    /**
     *   当前用户uid
     * @var  numeric
     */
    protected $uid           = '';
    /**
     *   当前的control
     * @var  String
     */
    protected $control       = '';
    /**
     *   当前的action
     * @var  String
     */
    protected $action        = '';
    const EVENT_REDIRECT     = 'redirect.control';
    const EVENT_REDIRECT_302 = 'redirect.302';
}
