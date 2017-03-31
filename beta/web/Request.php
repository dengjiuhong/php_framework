<?php
/**
 * UC乐园  基础支撑  请求处理
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
class Request
{
    /**
     *   获取$_REQUEST里面的数据
     */
    public function getRequest($key, $default = '')
    {
	return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
    }
    /**
     *    获取一个$_GET变量
     * @param String  $key     - $_GET变量的指定的值
     * @param String  $default - 默认值
     * @return String          - $_GET对应的变量
     */
    public function get($key = '', $default = '')
    {
        return isset($_GET[$key]) ? urldecode($_GET[$key]) : $default;
    }
    /**
     * 手工设置一个GET的变量
     * @param  $key
     * @param  $value
     */
    public function setGetParamManual($key, $value)
    {
        $_GET[$key] = $value;
        return true;
    }
    /**
     *    获取一个$_POST变量
     * @param String  $key     - $_POST变量的指定的值
     * @param String  $default - 默认值
     * @return String          - $_POST对应的变量
     */
    public function post($key = '', $default = '')
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
    /**
     *    获取一个$_GET或者$_POST变量
     * @param String  $key     - 指定的值
     * @param String  $default - 默认值
     * @return String          - 对应的变量
     */
    public function param($key = '', $default = '')
    {
        return isset($_GET[$key]) ? $_GET[$key] : $this->post($key, $default);
    }
	/**
     *    获取一个$_GET或者$_POST变量
     * @param String  $key     - 指定的值
     * @param String  $default - 默认值
     * @return String          - 对应的变量
     */
    public function haveSet($key = '', $type = 'both')
    {
    	$type = strtolower($type);
    	if ($type == 'get') {
    		return isset($_GET[$key]);
    	} else if ($type == 'post') {
    		return isset($_POST[$key]);
    	} else {
    		return isset($_GET[$key]) || isset($_POST[$key]);
    	}
    }
    /**
     *   获取一个普通文件上传的信息$_FILES
     *
     * @param string $key  - 表单的名字
     * @return $_FILES
     */
    public function files($key = '')
    {
        $file = array();
        if (empty($key)){
            return $_FILES;
        } else {
            $file['name']     = isset($_FILES[$key]['name']) ? $_FILES[$key]['name'] : null;
            $file['type']     = isset($_FILES[$key]['type']) ? $_FILES[$key]['type'] : null;
            $file['tmp_name'] = isset($_FILES[$key]['tmp_name']) ? $_FILES[$key]['tmp_name'] : null;
            $file['error']    = isset($_FILES[$key]['error']) ? $_FILES[$key]['error'] : null;
            $file['size']     = isset($_FILES[$key]['size']) ? $_FILES[$key]['size'] : null;
        }

        if (empty($file['tmp_name']) && empty($file['size'])) {
        	return array();
        }
        return $file;
    }
    /**
     *   将POST数据缓存在临时数据里面
     *
     * @param $hash - 如果post为非空, 返回hash
     * @return bool - 是否操作成功
     */
    public function setRequestTempParam(&$hash = '')
    {
        if (!empty($_POST)){
            $hash        = \utils\IdGenerator::getBigIntId();
            // 失效时间10分种
            $mc          = \datalevel\base\Factory::getInstance()->getMc();
            // 将$_POST放到临时数据里面去
            $key         = $this->tmpParamPrefix . $hash;
	    $res         = $mc->set($key, array($_POST, $_GET), 300);
            // 记录log
            $this->log("setRequestTempParam`" . $hash . "`" . $key . "`" . $res);
            return true;
        } else {
            return false;
        }
    }
    /**
     *   将跳转到中转页面之前保存的POST的数据拿出来
     *
     * @return bool  - 是否操作成功
     */
    public function parseRequestTempParam()
    {
        // 
        $c1 = isset($_GET[$this->tmpParamTrigger]) ? $_GET[$this->tmpParamTrigger] : '';
        if (!empty($c1)){
            $mc      = \datalevel\base\Factory::getInstance()->getMc();
            // 将跳转到proxy之前的$_GET, $_POST还原
            $key     = $this->tmpParamPrefix . $c1;
            $request = $mc->get($key);
            $this->log("parseRequestTempParam`" . $key . "`" . json_encode($request));
	    if ($request) {
		$post  = isset($request[0]) ? $request[0] : array();
		$get   = isset($request[1]) ? $request[1] : array();
		if (!empty($get) && is_array($get)){
		    $_GET = array_merge($_GET, $get);
		}
		if (!empty($post) && is_array($post)){
		    $_POST = array_merge($_POST, $post);
		}
		$_REQUEST = array_merge($_GET, $_POST);
                // 将临时数据删掉
                $mc->delete($key);
                return true;
            }
        }
        return false;
    }
    private function log($msg, $level = 'debug')
    {
        return \utils\Logger::writeLog($level, 'apps_base_Request', $msg);
    }
    /**
     *    获取上传的图片的buffer
     * @param $key
     */
    public function fileBuffer($key)
    {
        if (isset($_FILES[$key]['tmp_name'])){
            return file_get_contents($_FILES[$key]['tmp_name']);
        }
        return '';
    }
    /**
     *   检查上一个页面是否为302
     * @return bool  true 为 302, false为非302
     */
    public function checkIs302()
    {
	if (isset($_GET['302']) && $_GET['302']) return true;
	return false;
    }
    /**
     *   检查当前的请求方式是否为post
     * @return boolean
     */
    public function checkIsPost()
    {
        $_SERVER['REQUEST_METHOD'] = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        $_SERVER['REQUEST_METHOD'] = $this->requestMethod !== null ? $this->requestMethod : $_SERVER['REQUEST_METHOD'];
        if("POST" == $_SERVER['REQUEST_METHOD']){
            return true;
        } else {
            return false;
        }
    }
    /**
     *    手工设置当前请求方式
     * @param string $method
     */
    public function setRequestMethod($method = 'POST')
    {
        $this->requestMethod = $method;
    }
    /**
     *   检测是否为ajax请求
     */
    public function checkIsAjax()
    {
        return false;
    }
    /**
     *   获取当前Url
     * @return Url  - 当前请求的Url
     */
    public function getUrl($preRoute = false)
    {
        $uzoneApi = Uzone::getInstance();
        $route    = array();
        if ($preRoute){
            $route = $uzoneApi->getPreRoute();
        }
        $route = empty($route) ? $uzoneApi->getRoute() : $route;
        $route = $route[0] . '/' . $route[1];
        $final = array();
        $param = '';
        $skip  = array('s1', 'gi', 'wi', 'r', 'li', 'dn', 'sn', 'fr', 'pf', 've', 'mi', 'cp', 'page', 'totalPage', 'uc_param_str', 'token', 'HTTP_UCCPARA', 'vcode', 'sid', 'amp;r', 'gid');
        foreach($_GET as $key => $v){
            if (in_array($key, $skip)) continue;
            if ($v == "") continue;
            $final[$key] = $v;
        }
        if (!empty($final)){
            $param = '&' . http_build_query($final);
        }
        if (\utils\Url::$isSidInUrl){
            $sid = \utils\Url::getSid();
            return Config::get('domain.base', 'Common') .$sid.'&r=' . $route . $param;
        } else {
            return Config::get('domain.base', 'Common') . '&r=' . $route . $param;
        }
    }
    /**
     *    获取链接参数之前的间隔符
     * @param string $url  - 需要获取的url
     * @return string      - 返回传入的url需要用到的 ? 或者 & 或者 /?
     */
    public function getPrefix($url)
    {
        $prefix      = strpos($url,'?') !== false ? '&' : '?';
        $res         = parse_url($url);
        if (empty($res['query']) && empty($res['path'])){
            $prefix = '/?';
        }
        return $prefix;
    }
    /**
     *   获取来源
     */
    public function getReferer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    }
    /**
     *    手工设置sid在url上面
     * @param unknown_type $flag
     */
    public function setSidInUrl($flag = true)
    {
        $this->isSidInUrl = $flag;
    }
    /**
     *   检查时候要吧sid放在url上面
     */
    public function checkSidInUrl()
    {
        return $this->isSidInUrl;
    }
    private $isSidInUrl = false;
    /**
     * 单实例对象
     * @var \apps\base\Request
     */
    public static function getInstance()
    {
        if (self::$obj == null){
            self::$obj = new self();
        }
        return self::$obj;
    }
    public function __construct()
    {
        // 安全检测
        if (isset($_GET['GLOBALS']) ||isset($_POST['GLOBALS']) ||  isset($_COOKIE['GLOBALS']) || isset($_FILES['GLOBALS'])) {
            throw new \utils\AppsException('request tainting', 500);
        }
    }
    public $tmpParamTrigger  = 'c1';
    private $tmpParamPrefix  = 'apps_base_request_tmp_';
    public static $obj       = null;
    private $requestMethod;
}
