<?php
declare(encoding='UTF-8');
namespace framework\web;
use \framework\base\AppsException as Exception;
use \framework\web\Request as Request;
use \framework\base\Web as Web;
use \framework\web\tpl\Template as Template;
/**
 * UC乐园  基础支撑  视图基础层
 *
 * @category   View
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link   http://u.uc.cn/
 * @since  File available since Release 2.2.0
 */
class View
{
    /**
     *    设置当前的control
     * @param string $control  - 当前的control
     */
    public function setControl($control)
    {
        return $this->control = $control;
    }
    /**
     *   获取当前的control
     */
    public function getControl()
    {
        return $this->control;
    }
    /**
     *    设置当前的action
     * @param string $action  - 当前的action
     */
    public function setAction($action)
    {
        return $this->action = $action;
    }
    /**
     *   取得当前action
     * @return string 当前action
     */
    public function getAction()
    {
        return $this->action;
    }
    /**
     *    设置当前平台
     * @param string  $pf  当前平台号
     * @return string      当前平台
     */
    public function setPf($pf)
    {
        return $this->platform = $pf;
    }
    /**
     *   取得当前平台号
     */
    public function getPf()
    {
        return $this->platform;
    }
    /**
     *    设置页面标题
     * @param string   $title  - 需要设置的页面标题
     * @return string          - 当前页面的主题
     */
    public function setTitile($title)
    {
        return $this->title = $title;
    }
    /**
     *  获取当前页面的标题
     * @return string    - 当前页面的标题
     */
    public function getTitle()
    {
        return $this->title;
    }
    /**
     *    设置使用的布局文件
     * @param string   $layout    - 设置布局文件的名字
     */
    public function setLayout($layout)
    {
        return $this->layout = $layout;
    }
    /**
     *    保存变量到模板里面去
     * @param $key     - 变量的名字
     * @param $value   - 变量的值
     */
    public function assert($key, $value)
    {
        return $this->vars[$key] = $value;
    }
    /**
     *    渲染一个模板
     * @param $tpl         - 模板的名字
     * @param $_tpl_vars   - 模板用到的变量
     */
    public function render($tpl, $_tpl_vars = array())
    {
        $this->action = empty($tpl) ? $this->action : $tpl;
        $fileName     = $this->tmpPath . '/'. strtolower(Web::$language) . '_tpl_' . $this->platform . '_' . strtolower($this->control) . '_' . $this->action . '.php';
        if ($this->cache){
            if (!file_exists($fileName)){
                $this->buildTemplate($fileName);
            }
        } else {
            $this->buildTemplate($fileName);
        }
        return $fileName;
        $this->showHtml($fileName, $_tpl_vars);
    }
    /**
     *    输出html
     * @param string $fileName  - 要输出的文件
     * @param $_tpl_vars
     */
    public function showHtml($fileName, $_tpl_vars)
    {
        $_tpl_vars = array_merge($_tpl_vars, $this->vars);
        extract($_tpl_vars, EXTR_OVERWRITE);
        if ($this->cleanHtml){
            ob_start();
            require $fileName;
            $html = ob_get_clean();
            // Replace CR, LF and TAB to spaces
            $html = str_replace(array("\n", "\r", "\t"), " ", $html);
            // Replace multiple to single space
            $html = preg_replace("/\s\s+/", " ", $html);
            $html = preg_replace("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/e", "\\apps\base\Util::transamp('\\0')", $html);
            echo $html;
        } else {
            require $fileName;
        }
    }
    /**
     *    创建模板文件
     * @param string $fileName  模板文件存放的路径
     * @return bool             是否创建成功
     */
    public function buildTemplate($fileName)
    {
        require_once dirname(__FILE__) . '/tpl/Template.php';
        if (!$this->tmplate){
            $this->tmplate     = Template::getInstance();
        }
        $layout = '';
        if (!empty($this->layout)){
            $_layout = $this->tmplatePath . strtolower($this->platform) . '/layout/' . $this->layout . '.html';
            if (!is_file($_layout)){
                $_layout  = $this->tmplatePath . strtolower(Web::DEFAULT_PLATFORM) . '/layout/' . $this->layout . '.html';
            }
            $layout = @file_get_contents($_layout);
            if (!$layout){
                throw new Exception("layout " . $_layout . 'not found ', 404);
            }
        }
        $file = PROJECT_BASEPATH . '/apps/modules/' . strtolower($this->control) . '/templates/' . strtolower($this->platform) . '/' . $this->action . '.html';
        if(!is_file($file)){
            $file = PROJECT_BASEPATH . '/apps/modules/' . strtolower($this->control) . '/templates/' . strtolower(Web::DEFAULT_PLATFORM) . '/' . $this->action . '.html';
        }
        $html = @file_get_contents($file);
        if (false == $html){
            throw new Exception('tmplate file ' . $file . ' not found', 404);
        }
        if (!empty($layout)){
            $html    = str_replace(array(self::$tagBody), array($html), $layout);
        }
        return file_put_contents( $fileName, $this->tmplate->parse($html));
    }
    /**
     *    读取layout通用模板
     *
     * @param string $block   - 通用模板的名字
     * @return string         - 编译好的模板片段
     */
    public function layout($block)
    {
        if (!$this->tmplate){
            $this->tmplate     = Template::getInstance();
        }
        $file = $this->tmplatePath . strtolower($this->platform) . '/layout/' . $block . '.html';
        if (is_file($file)){
            $block = file_get_contents($file);
        }else{
            $block = file_get_contents($this->tmplatePath . Web::DEFAULT_PLATFORM . '/layout/' . $block . '.html');
        }
        return $this->tmplate->parse($block);
    }
   /**
     *    读取layout通用模板
     *
     * @param string $block   - 通用模板的名字
     * @return string         - 编译好的模板片段
     */
    public function _include($block)
    {
        if (!$this->tmplate){
            $this->tmplate     = \framework\web\tpl\Template::getInstance();
        }
        $file = dirname(__FILE__) . '/../modules/' . Web::$control . '/templates/' . strtolower(Web::$platform) . '/layout/' . $block . '.html';
        if (!is_file($file)){
            $file  = dirname(__FILE__) . '/../modules/' . Web::$control . '/templates/' . Web::DEFAULT_PLATFORM . '/layout/' . $block . '.html';
            if (!is_file($file)){
                throw new Exception("include layout: " . $file . 'not found ', 404);
            }
        }
        $block = file_get_contents($file);
        return $this->tmplate->parse($block);
    }
    /**
     *    渲染分页条
     * @param unknown_type $total
     * @param unknown_type $pageSize
     */
    public function page($total, $pageSize, $url = '')
    {
        $tmp = $this->layout('page');
        $tmp = str_replace(array('_total', '_pageSize', '_currentUrl'), array($total, $pageSize, $url), $tmp);
        return $tmp;
    }
    /**
     *    显示友好错误提示页面s
     * @param string $msg   - 错误提示信息
     */
    public function showBusy($msg = '', $vars)
    {
        if (!$this->tmplate){
            $this->tmplate     = \framework\web\tpl\Template::getInstance();
        }
	    $fileName = $this->tmpPath . '/layout_' . $this->platform . '_showBusy.php';
        if (!file_exists($fileName) || !$this->cache){
	       $busyFile = $this->tmplatePath . strtolower($this->platform) . '/layout/showBusy.html';
	       if (!is_file($busyFile)) {
	       	   $busyFile = $this->tmplatePath . Web::DEFAULT_PLATFORM . '/layout/showBusy.html';
	       }
	       $layout = file_get_contents($busyFile);
           file_put_contents($fileName, $this->tmplate->parse($layout));
        }
        $this->showHtml($fileName, array(
           'msg'  => $msg,
           'vars' => $vars,
        ));
    }
    /**
     *    建立模板目录
     * @param string  $path  - 路径
     * @param numeric $mode  - 权限
     */
    public function rmkdir($path, $mode = 0755) {
        $path = rtrim(preg_replace(array("/\\\\/", "/\/{2,}/"), "/", $path), "/");
        $e = explode("/", ltrim($path, "/"));
        if(substr($path, 0, 1) == "/") {
            $e[0] = "/".$e[0];
        }
        $c = count($e);
        $cp = $e[0];
        for($i = 1; $i < $c; $i++) {
            if(!is_dir($cp) && !@mkdir($cp, $mode)) {
                return false;
            }
            $cp .= "/".$e[$i];
        }
        return @mkdir($path, $mode);
    }
    /**
     *   获取单实例
     */
    public static function getInstance($config = array())
    {
        if (self::$obj == null){
            self::$obj = new self($config);
        }
        return self::$obj;
    }
    /**
     *    初始化
     * @param array $config  - 配置
     */
    public function __construct($config = array())
    {
        $this->tmplatePath = $config['tplPath'];
        $this->tmpPath     = $config['tplCache'];
        $this->request     = Request::getInstance();
        // 如果是debug模式, 开启模板缓存
        $this->cache       = $config['realTimeParse'];
		$this->control     = Web::$control;
		$this->action      = Web::$action;
		$this->platform    = Web::$platform;
        return $this;
    }
    /**
     *   请求统一处理的对象
     * @var \apps\base\Request
     */
    public $request       = null;
    public $cleanHtml      = false;
    public $cache          = false;
    public $vars           = array();
    public $tmpPath        = '';
    public $control        = 'profile';
    public $action         = 'index';
	public $platform       = 'v3';
    public $tmplatePath    = '';
    public $isLayout       = true;
    public $layout         = 'main';
    public $title          = '';
    public $pf             = 'v3';
    public static $tagBody = '<!--{layout body}-->';
    public static $obj;
    public $tmplate;
}

