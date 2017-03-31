<?php
/**
 * UC乐园  基础支撑  http请求工具
 *
 * @category   HttpClient
 * @package    utils
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding = 'UTF-8');
namespace utils;
class HttpClient
{
    /**
     *   获取单实例
     * @return HttpClient
     */
    public static function getInstance()
    {
        if (self::$obj === null){
            self::$obj = new self();
        }
        return self::$obj;
    }
    /**
     *    发起一个get请求
     * @param String  $url  - get请求的url
     * @example
     * \utils\HttpClient::getInstance()->get('http://www.baidu.com')
     *
     * @return http body (不带header)
     */
    public function get ($url) {
        if (empty($url)){
            throw new Exception("HttpClient get Url can not empty");
        }
        $this->url = $url;
        $this->log("get`" . $url);
        return $this->parseRes();
    }
    /**
     *    获取指定的header信息
     * @param $key 获取的Header的Key， 如：HTTP_USER_AGENT
     * @return String
     */
    public function getHeaders ($key = '')
    {
        return empty($key) ? $this->responseHeader : (isset($this->responseHeader[$key]) ? $this->responseHeader[$key] : '');
    }
    /**
     *    手工设置请求头
     * @param $key    自定义Header的key
     * @param $value  自定义Header的值
     * @return boolean 是否设置成功
     */
    public function setHeaders($key, $value)
    {
        $this->headers[] = $key . ": " . $value;
        return true;
    }
    /**
     *    发起一个post请求
     * @param $url    - 请求的url
     * @param $data   - post过去的数据 两种格式 key=value&key=value 或者 数组 array('key' => 'value')
     * @example
     * \utils\HttpClient::getInstance()->post('http://www.baidu.com', array('key' => 'value'));
     *
     * @return http body
     */
    public function post($url, $data = array())
    {
        $this->url           = $url;
        $this->requestMehtod = "post";
        $this->requestParam  = $data;
        $this->log("POST`" . $url . "`" . json_encode($data));
        return $this->parseRes();
    }
    /**
     *   解析结果
     */
    private function parseRes()
    {
        //TODO 这里有问题，重试的时候没有断开链接, 待修复
        $return = $this->exec();
        $this->proecess = '';
        return $return;
    }
    /**
     *   发出http请求, 并且记录需要用到的log
     */
    private function doHttpRequest()
    {
        $this->proecess = curl_init($this->url);
        if ($this->requestMehtod == "post"){
            $this->requestParam   = is_array($this->requestParam) ? http_build_query($this->requestParam) : $this->requestParam;
        }
        if (!empty($this->headers)){
            curl_setopt($this->proecess, CURLOPT_HTTPHEADER, $this->headers);
        }
        curl_setopt($this->proecess, CURLOPT_HEADER, 0);
        curl_setopt($this->proecess, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($this->proecess, CURLOPT_ENCODING, $this->compression);
        curl_setopt($this->proecess, CURLOPT_HEADERFUNCTION, array(&$this, 'readHeader'));
        curl_setopt($this->proecess, CURLOPT_HTTPHEADER, array("Expect:"));
        // Response will be read in chunks of 64000 bytes
        curl_setopt($this->proecess, CURLOPT_BUFFERSIZE, 64000);
        curl_setopt($this->proecess, CURLOPT_TIMEOUT, self::$timeout);
        if ($this->requestMehtod == "post"){
            curl_setopt($this->proecess, CURLOPT_POST, 1);
            curl_setopt($this->proecess, CURLOPT_POSTFIELDS, $this->requestParam);
        }
        curl_setopt($this->proecess, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->proecess, CURLOPT_FOLLOWLOCATION, 1);
        $return           = curl_exec($this->proecess);
        $res              = array();
        $info             =  curl_getinfo($this->proecess);
        $this->status     = array(
            'http_code'          => $info['http_code'],
            'total_time'         => $info['total_time'],
            'namelookup_time'    => $info['namelookup_time'],
            'connect_time'       => $info['connect_time'],
            'pretransfer_time'   => $info['pretransfer_time'],
            'starttransfer_time' => $info['starttransfer_time'],
        );
        if ($this->status['http_code'] != '200'){
            self::$requestStatus = false;
            $this->status['error']     = curl_errno($this->proecess);
            $this->status['errorMsg']  = curl_error($this->proecess);
            $this->log("parseStatus`" . $this->url . "`" . json_encode($this->status), 'warn');
            // 更新不可用的状态
            $this->setUnAvaliable();
            // 非200返回false
            $return = false;
        } else {
            self::$requestStatus = true;
        }
        $this->log("parseStatus`" . $return . "`" . json_encode($this->status));
        $this->log('parseHeader`' . json_encode($this->responseHeader));
        curl_close($this->proecess);
        return $return;
    }
    /**
     *   执行请求
     */
    private function exec()
    {
        $res = '';
        if (!$this->checkIsAvaliable()){
            // 记录失败日志
            $this->log('notAvaliable`' . $this->url . "`" . json_encode($this->requestParam), 'info');
            return false;
        }
        while (!self::$requestStatus && self::$num <= self::$retryNum){
            if (self::$num > 0){
                // 第一次不行之后，休息0.5秒再次请求
                $this->log("retry`" . $this->url . "`" . self::$num . "`" . self::$sleep, 'warn');
                usleep(self::$sleep);
            }
            $res = $this->doHttpRequest();
            self::$num++;
        }
        // 计数器清零
        self::$requestStatus = false;
        self::$num           = 0;
        return $res;
    }
    /**
     *   check domain is avaliable
     *
     * @return boolean  - 是否可用
     */
    private function checkIsAvaliable()
    {
        if (self::$isAvaliable === null){
            $domain = $this->getDomainByUrl();
            if (empty($domain)) return false;
            $mc     = \datalevel\base\Factory::getInstance()->getMc();
            // 检测是否有关闭的标记
            $key    = 'utils.HttpClient.down.' . $domain;
            $res    = $mc->get($key);
            // debug log
            $res    = $res ? false : true;
            $str    = $res ? 'true' : 'false';
            $this->log("checkIsAvaliable`" . $key . "`" . $str);
            self::$isAvaliable = $res;
        }
        return self::$isAvaliable;
    }
    private static $isAvaliable;
    /**
     *   设置当前不可用的情况
     *
     * @return boolean  - 是否设置成功
     */
    private function setUnAvaliable()
    {
        // 获取当前url的domain
        $domain = $this->getDomainByUrl();
        if (empty($domain)) return false;
        $mc     = \datalevel\base\Factory::getInstance()->getMc();
        $key    = 'utils.HttpClient.downNum.' . $domain;
        $res    = $mc->get($key);
        if ($res){
            // 递增
            $res++;
            $mc->increment($key);
        } else {
            // 初始化, 记录5分钟之内连续失败的次数
            $res = 1;
            $mc->set($key, 1, $this->shutdownNumLife);
        }
        // debug log
        $this->log("setUnAvaliable`downNum`" . $key . "`" . $res . "`" . $this->shutdownNumLife);
        if ($res >= $this->shutdownNum){
            // 失败次数超过阀值
            $key    = 'utils.HttpClient.down.' . $domain;
            $this->log('setUnAvaliable`down`' . $key . "`1");
            $mc->set($key, 1, $this->shutdownLfie);
        }
        return true;
    }
    /**
     *   从请求的url里面抽取 host & port
     */
    private function getDomainByUrl()
    {
        if (empty($this->url)) return '';
        $info = parse_url($this->url);
        $host = isset($info['host']) ? $info['host'] : '';
        $port = isset($info['port']) ? $info['port'] : '';
        return $host . $port;
    }
    /**
     * CURL callback function for reading and processing headers
     * Override this for your needs
     *
     * @param object $ch
     * @param string $header
     * @return integer
     */
    private function readHeader ($ch, $header)
    {
        //extracting example data: filename from header field Content-Disposition
        $this->parseHeader($header);
        return strlen($header);
    }
    /**
     *    解析头部
     * @param string $header
     */
    private function parseHeader ($header)
    {
        if (empty($header)) return false;
        $tmp = explode(': ', $header);
        if (!empty($tmp[0]) && !empty($tmp[1])){
            $this->responseHeader[trim($tmp[0])] = trim($tmp[1]);
        }
        return true;
    }
    /**
     *    记录log
     * @param string $msg
     * @param string $level
     */
    private function log($msg, $level = 'debug')
    {
        \utils\Logger::writeLog($level, 'utils_HttpClient', $msg);
    }
    /**
     *    初始化
     * @param $cookies
     * @param $cookie
     * @param $compression
     * @param $proxy
     */
    public function __construct ($compression = 'gzip', $proxy = '')
    {
        $this->proxy       = $proxy;
        // 获取配置
        self::$config      = framework\base\Config::get('', 'HttpClient');
        self::$timeout     = self::$config['timeout'];
        self::$sleep       = self::$config['sleep'];
        self::$retryNum    = self::$config['retryNum'];
        if (isset(self::$config['shutdownLfie'])){
            $this->shutdownLfie    = self::$config['shutdownLfie'];
            $this->shutdownNum     = self::$config['shutdownNum'];
            $this->shutdownNumLife = self::$config['shutdownNumLife'];
        }
        $this->user_agent  = self::$config['ua'];
        $this->compression = self::$config['compression'];
    }
    /**
     *   手工设置超时时间
     *
     * @param $timeout  - 超时时间, 单位是秒
     * @return void
     */
    public function setTimeout($timeout)
    {
	self::$timeout = $timeout;
    }
    /**
     *   设置是否重试试
     *
     * @param boolean  - 是否重试, 如果是false，则不重试
     * @return void
     */
    public function setIsRetry($isRetry)
    {
	if (!$isRetry){
	    self::$retryNum = 0;
	}
	return true;
    }
    private $shutdownLfie         = 300;
    private $shutdownNum          = 10;
    private $shutdownNumLife      = 300;
    private $requestMehtod;
    private $requestParam;
    private static $config        = array();
    private static $requestStatus = false;
    private static $isRetry       = true;
    private static $num           = 0;
    private static $retryNum      = 5;
    private static $sleep         = 10000;
    private static $timeout       = 1;
    private $responseHeader       = array();
    private $time;
    private $status;
    private $url;
    private $proecess;
    private static $obj;
    private $headers;
    private $user_agent;
    private $compression;
}

