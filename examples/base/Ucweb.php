<?php
/**
 * UC乐园  基础支撑  浏览器基础参数处理
 *
 * @category   base
 * @package    apps
 * @author Jiuhong Deng <dengjh@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace base;
use \framework\base\AppsException as AppsException;
class Ucweb
{
    /**
     * @desc 判断当前平台
     *
     * 包括：
     * 1, 检查当前pf是否可用
     * 2, 检查当前是否为手机
     *
     * @return mix
     * 如果通过，则返回true, 如果不通过，直接退出程序，显示提示信息
     */
    public function checkPlatform()
    {
        $this->log("checkPlatform`start");
        $config = $this->getPlatformConfig();
        // 关闭版本限制
        if (!$config['isCheck']) return true;
        // 获取当前浏览器的参数
        $isComputer = $this->checkIsComputer();
        $sn         = $this->snId;
        $imei       = $this->imei;
        if ($this->simulateFlag){
            $ip = $this->getIp();
            // 如果不支持电脑, 直接退出
            if (!$config['isPcBrowserSupport']){
                $this->showPlatformLimit('browser.deny', isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : 'unknow browser');
            }
            // 如果是电脑，只是限制ip
            $this->log("checkPlatform`IsComputer`" . $ip . "`" . $this->snId . "`" . $this->imei);
            if (isset($config['ipRules'])){
                if (!empty($config['ipRules']['deny']) && $this->checkWhiteIp($ip, $config['ipRules']['deny'])){
                    // 在ip 黑名单中
                    $this->showPlatformLimit('ip.deny', $ip);
                }
                if (!empty($config['ipRules']['allow']) && !$this->checkWhiteIp($ip, $config['ipRules']['allow'])){
                    // 在ip 黑名单中
                    $this->showPlatformLimit('ip.allow', $ip);
                }
            }
            return true;
        }
        // 开始检查pf的配置
        $pfRules = $config['pfRules'];
        // 如果配置留空，不做任何的限制
        if (empty($pfRules)) return true;
        // 检查pflatform
        $pfid   = $this->pfid;
        if (!isset($pfRules[$pfid])){
            // 现在pf不合适
            $this->showPlatformLimit('pfid', $pfid);
        } else {
            foreach($pfRules[$pfid] as $key => $v){
                if (empty($v)) continue;
                $tmp = $this->get($key);
                $this->log("checkPlatform`checkPf`" . $pfid . "`" . $key . "`" . $tmp);
                if ((is_string($v) && ($tmp < $v)) || (is_array($v) && !in_array($tmp, $v))){
                    $this->showPlatformLimit($key, $tmp);
                }
            }
        }
        // 非模拟的情况下，才去检查imei,sn黑白名单
        if (!$this->simulateFlag) {
            // 检查imei, snId 黑白名单
            $rules = array('imeiRules' => 'imei', 'snIdRules' => 'snId');
            foreach($rules as $type => $r){
                if (isset($config[$type])){
		    $tmp = $this->get($r);
		    if (empty($tmp)) continue;
                    $this->log("checkPlatform`check" . $type . "`" . $tmp);
                    if ($tmp && in_array($tmp, $config[$type]['deny']) && !empty($config[$type]['deny'])){
                        // 在denny名单里面
                        $this->showPlatformLimit($type . '.deny', $tmp);
                    }
                    if ($tmp && !in_array($tmp, $config[$type]['allow']) && !empty($config[$type]['allow'])){
                        // 不在allow名单里面
                        $this->showPlatformLimit($type . '.allow', $tmp);
		    }
		    break;
                }
            }
        }
        if (!$config['isPcBrowserSupport']){
            // 如果不支持pc浏览器，需要去检查dn, pf, ve等等参数
            $this->security();
        }
    }
    /**
     * @desc 检测是否为中转模式
     * @return boolean
     */
    public function checkIsProxy()
    {
        $res = isset($_SERVER['HTTP_UCCPARA']) && !empty($_SERVER['HTTP_UCCPARA']) ? true : false;
        $this->log("checkIsProxy`" . $res);
        return $res;
    }
    /**
     * @desc 检查是否为电脑
     * @return boolean   - 检查当前是否为pc浏览器
     */
    public function checkIsComputer()
    {
        if (self::$isComputer === null){
            $pcAgents = array(
                'MSIE 8.0', 'MSIE 7.0', 'MSIE 6.0', 'NetCaptor', 'Netscape', 'Lynx',
                'Opera', 'Konqueror', 'Mozilla/5.0', 'Firefox', 'Firefox/3', 'Firefox/2',
                'Chrome', 'Ubuntu', 'X11', 'maverick', 'Gecko/20101013', 'Firefox/3.6.11'
            );
            if (isset($_SERVER["HTTP_USER_AGENT"])){
                $this->log("checkIsComputer`start`user_agent: " . $_SERVER["HTTP_USER_AGENT"] . "`snId: " . $this->param['snId'] . "`imei: " . $this->param['imei']);
                foreach($pcAgents as $agent){
                    // snId, imei都为空, 并且 USER_AGENT 不等于UCWEB的时候，判断为
                    if (strstr($_SERVER["HTTP_USER_AGENT"], $agent) && !strstr($_SERVER['HTTP_USER_AGENT'], 'UCWEB')){
                        self::$isComputer = true;
                    }
                }
            } else {
                self::$isComputer = false;
            }
            $tmp = self::$isComputer ? '1' : '0';
            $this->log("checkIsComputer`res`" . $tmp);
        }
        return self::$isComputer;
    }
    /**
     * 检测是否为QQ浏览器 - 简单根据一些特有的Header判断
     * 如果检查到是QQ浏览器(包括GO, safari, opera), 直接显示非UC浏览器的提示页面。
     */
    public function checkIsQQBrowser()
    {
        //经过观察QQ浏览器有以下几个比较特别的Header
        // s60, android应该都有
        if (isset($_SERVER['HTTP_Q_UA']) || isset($_SERVER['HTTP_Q_AUTH']) || isset($_SERVER['HTTP_Q_GUID'])){
            // QQ Browser特有的UA
            $ua   = isset($_SERVER['HTTP_Q_UA']) ? isset($_SERVER['HTTP_Q_UA']) : '';
            // QQ Browser特有的Auth授权码
            $au   = isset($_SERVER['HTTP_Q_AUTH']) ? $_SERVER['HTTP_Q_AUTH'] : '';
            // QQ Browser s60机器上面，唯一标识码，类似UC的sn
            $guid = isset($_SERVER['HTTP_Q_GUID']) ? $_SERVER['HTTP_Q_GUID'] : '';
            $this->showPlatformLimit('qqbrowser', $ua . "`" . $au . "`" . $guid);
        }
        // GO browser有如下比较特别的header
        $keyWord = 'GoBrowser';
        if (isset($_SERVER["HTTP_USER_AGENT"])){
            if (strstr($_SERVER['HTTP_USER_AGENT'], $keyWord)){
                $this->showPlatformLimit('gobrowser', $_SERVER['HTTP_USER_AGENT']);
            }
        }
        // ipod、ipad等等的Safari
        // AppleWebKit, iPhone, iPod, Mobile
        if (isset($_SERVER["HTTP_USER_AGENT"])){
            if (strstr($_SERVER['HTTP_USER_AGENT'], 'AppleWebKit') && strstr($_SERVER['HTTP_USER_AGENT'], 'iPhone') && strstr($_SERVER['HTTP_USER_AGENT'], 'iPod') && strstr($_SERVER['HTTP_USER_AGENT'], 'Mac OS')){
                $this->showPlatformLimit('iphonebrowser', $_SERVER['HTTP_USER_AGENT']);
            }
        }
        // ucmobile也暂时不支持
        // UC AppleWebkit
         if (isset($_SERVER["HTTP_USER_AGENT"])){
            if (strstr($_SERVER['HTTP_USER_AGENT'], 'UC AppleWebkit') && strstr($_SERVER['HTTP_USER_AGENT'], 'Gecko') && strstr($_SERVER['HTTP_USER_AGENT'], 'Safari')){
                $this->showPlatformLimit('ucmobilebrowser', $_SERVER['HTTP_USER_AGENT']);
            }
        }
        // opera Mobi 的特征header
        // 采集自android版本
        if (isset($_SERVER['HTTP_X_OPERAMINI_UA']) || isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']) || isset($_SERVER['HTTP_X_OPERAMINI_FEATURES']) || isset($_SERVER['HTTP_X_OPERAMINI_PHONE'])){
            $ua = isset($_SERVER['HTTP_X_OPERAMINI_UA']) ? isset($_SERVER['HTTP_X_OPERAMINI_UA']) : '';
            $ua .= isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']) ? $_SERVER['HTTP_X_OPERAMINI_PHONE_UA'] : '';
            $ua .= isset($_SERVER['HTTP_X_OPERAMINI_FEATURES']) ? $_SERVER['HTTP_X_OPERAMINI_FEATURES'] : '';
            $ua .= isset($_SERVER['HTTP_X_OPERAMINI_PHONE']) ? $_SERVER['HTTP_X_OPERAMINI_PHONE'] : '';
            $this->showPlatformLimit('operabrowser', $ua);
        }
        return true;
    }
    /**
     * @desc 获取当前HTTP header里面的HTTP_UCCPARA
     */
    public function getHttpUccPara()
    {
        $this->log("getHttpUccPara`" . $this->http_uccpara);
        return $this->http_uccpara;
    }
    /**
     * @desc 获取当前浏览器的platform
     * @return  v3, v5, java, android, iphone, ppc
     */
    public function getPlatform()
    {
        if (self::$pf === null){
            $config = $this->getPlatformConfig();
            $this->log("getPlatform`start`pf: " . $this->param['pfid'] . "`ss: " . $this->param['ss']);
            self::$pf =  !empty($this->param['pfid']) && isset($config['pfMapper'][$this->param['pfid']]) ? $config['pfMapper'][$this->param['pfid']] : $this->defaultPlatform;
            $this->log("getPlatform`res`" . self::$pf);
        }
        return self::$pf;
    }
    /**
     * 手工设置当前的浏览器的platform
     * @param $platform 浏览器platform, v3, v5, java, android, iphone, ppc
     */
    public function setPlatform($platform)
    {
        self::$pf = $platform;
        return true;
    }
    /**
     * @desc    取得单入口
     * @return  UcWeb Object
     */
    public static function getInstance($configs = array())
    {
        if (self::$obj == null) {
            self::$obj = new self($configs);
        }
        return self::$obj;
    }
    /**
     * @desc 获取ip
     */
    public function getip ()
    {
        $unknown = 'unknown';
        $ip      = '';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if (false !== strpos($ip, ','))
            $ip = reset(explode(',', $ip));
        return $ip;
    }
    /**
     * @desc  魔术方法，获取浏览器基本参数
     * @param $key
     */
    public function __get($key)
    {
        if ($key == 'imei') {
            //处理imei,去掉有可能有问题的字符
            $imei = $this->param [$key];
            $imei = str_replace ( array ('IMEI', '"', ':', ' ', '\'' ), array ('', '', '', '', '' ), $imei );
            //过滤一些字符(IMEI,",:,空格)
            if (strlen ( $imei ) <= 7 || preg_match ( '/[^\x01-\x7F]+/', $imei )) {
                //imei太短或者有非法字符,不使用
                $imei = '';
            }
            $this->param[$key] = $imei;
        }
        if (!isset($this->param[$key])) return false;
        return trim($this->param[$key]);
    }
    /**
     * @desc  魔术方法，获取浏览器基本参数
     * @param $key
     */
    public function get($key)
    {
        return $this->__get($key);
    }
    /**
     * @desc  手工设置param
     * @param $key
     * @param $value
     */
    public function setParamManual($key, $value)
    {
        $this->param[$key] = $value;
    }
    /**
     * @desc 获取所有的param
     *
     * @return array()
     */
    public function getParam()
    {
        return $this->param;
    }
    /**
     * @desc 手工浏览器模拟参数
     * 注意
     * 只在debug模式下使用
     */
    public function simulateHttpUccPara()
    {
        if (\framework\base\Config::get('debug', 'Common') && self::$isComputer){
            $snid = isset ( $_COOKIE ['snid'] ) ? $_COOKIE ['snid'] : '';
            if (empty ($snid)) {
                // 嘘, 手工生成个合法的snId
                $snid = $this->buildComputerSn();
                setcookie ('snid', $snid, time () + 13600 );
            }
            $imei  = sprintf("%u", (int)crc32($snid));
            $ver   = $this->configs['simulate']['ver'];
            $pfid  = $this->configs['simulate']['pfid'];
            $li    = $this->configs['simulate']['li'];
            $gi    = $this->configs['simulate']['gi'];
            $ua    = $this->configs['simulate']['ua'];
            $str   = 'ver=' . $ver . '`sn=' . $snid . '`cver=None`width=240`height=320`ua='.$ua.'`ip=211.139.190.202`nbr=`fr=sis`ln=zh_CN`disp=innerip%3D10.21.123.71`feature_bit1=482303`pfid='.$pfid.'`cp=isp:%E7%A7%BB%E5%8A%A8;prov:%E5%B9%BF%E4%B8%9C;city:%E5%B9%BF%E5%B7%9E;`mdn=`sms_no=%2B8613800200500`bid=999`gi=gtidibri%2BMnUwZY=`imei='.$imei.'`bseq=10031311`innerip=10.21.123.71,211.139.190.202`imsi=`li='.$li . "`gi=" . $gi . "`simulateFlag=1";
            $src   = trim($str) . 'p98aufiowqru39e';
            $vcode = substr(md5($src), 0, 8) ;
            $str   = $str . '`vcode=' . $vcode;
            // 直接模拟HTTP_UCCPARA
            $_SERVER['HTTP_UCCPARA'] = $str;
            $this->log("simulateHttpUccPara`" . $_SERVER['HTTP_UCCPARA']);
            $this->proxy();
            $this->simulateFlag = true;
        }
    }
    /**
     * @desc 从信任域取得公共参数
     */
    private function auth()
    {
        $keys = array(
            'dn', 'ss', 'li', 'sn', 'fr', 'pf', 'bi', 'cp', 'gi', 'mi', 'wi', 'ch', 've'
        );
        $tmp = array();
        foreach($keys as $key){
            if (!isset($_GET[$key]) || empty($_GET[$key])) continue;
            $tmp[$key] = $_GET[$key];
        }
        $this->authParam       = $tmp;
        $this->preParam        = $this->param;
        $this->param['dn']     = isset($tmp['dn']) && !empty($tmp['dn']) ? $tmp['dn'] : $this->param['dn'];
        $this->param['ss']     = isset($tmp['ss']) && !empty($tmp['ss']) ? $tmp['ss'] : $this->param['ss'];
        $this->param['fr']     = isset($tmp['fr']) && !empty($tmp['fr']) ? $tmp['fr'] : $this->param['fr'];
        $this->param['pfid']   = isset($tmp['pf']) && !empty($tmp['pf']) ? $tmp['pf'] : $this->param['pfid'];
        $this->param['ver']    = isset($tmp['ve']) && !empty($tmp['ve']) ? $tmp['ve'] : $this->param['ver'];
        $this->param['mi']     = isset($tmp['mi']) && !empty($tmp['mi']) ? $tmp['mi'] : $this->param['mi'];
        $this->param['ch']     = isset($tmp['ch']) && !empty($tmp['ch']) ? $tmp['ch'] : $this->param['ch'];
        $this->param['cp']     = isset($tmp['cp']) && !empty($tmp['cp']) ? urldecode($tmp['cp']) : $this->param['cp'];
        $this->param['ip']     = $this->getip();
        $this->param['li']     = isset($tmp['li']) && !empty($tmp['li']) ? $tmp['li'] : $this->param['li'];
        $this->param['gi']     = isset($tmp['gi']) && !empty($tmp['gi']) ? $tmp['gi'] : $this->param['gi'];
        $this->param['wi']     = isset($tmp['wi']) && !empty($tmp['wi']) ? $tmp['wi'] : $this->param['wi'];
        if (!empty($this->param['cp'])){
            // 根据cp获取省市
            $a = str_replace(array(':', ';'), array('=', '&'), $this->param['cp']);
            $lbs = array();
            parse_str($a, $lbs);
            $this->param['prov']  = isset($lbs['prov']) ? $lbs['prov'] : '';
            $this->param['city']  = isset($lbs['city']) ? $lbs['city'] : '';
        }
        if (!empty($this->param['dn'])){
            $this->param['snId'] = $this->getSnIdByDn($this->param['dn']);
        }
        $str = empty($tmp) ? '' : json_encode($tmp);
        $this->log("auth`" . $str);
        return true;
    }
    /**
     * @desc 安全校验, 看看浏览器送上来的参数是否和sess不一致，不一致则说明客户端变化了，session没有更新
     * 因为客户端实现之间的差别，已经废弃掉
     */
    private function security()
    {
        // 默认以为在直连模式下面，有uc_param_str的话, uc浏览器都会传 dn , pf, ve 参数上来。
        if (!empty($_GET['uc_param_str']) && (empty($this->authParam['dn']) || empty($this->authParam['pf']) || empty($this->authParam['ve']) || self::$dnError)){
            // 这样我就判断为非ucweb浏览器
            // 因为部分客户端并不是每次都传dn, pf, ve上来，只好把这个校验去掉，杯具。。。
            // $this->showPlatformLimit('browser.deny', 'emptyDnPfVe');
        }
        // 直链与中转拿到的参数不一致，说明可能有变化，需要再去中转模式拿一次
        // pf应该是一个数字
        if (!is_numeric($this->preParam['pfid']) || $this->preParam['pfid'] != $this->param['pfid']){
            //$this->securityFail('pfid');
        }
        if ($this->preParam['snId'] != $this->param['snId']){
            // 部分客户端dn与sn不一致,这个校验去掉
            //$this->securityFail('snId');
        }
        // ve版本号应该是由四个数字组成
        $tmp = explode('.', $this->preParam['ver']);
        if (count($tmp) != 4 || $this->preParam['ver'] != $this->param['ver']){
            //$this->securityFail('ver');
        }
        return true;
    }
    /**
     * @desc  安全检查不通过的时候触发的操作
     * @param string $type   - 安全不通过的类型, pfid, snId, ver
     */
    private function securityFail($type = '')
    {
        // 防止多次退出
        if (self::$securityFailFlag) return false;
        if ($type == 'pcbrowser'){
            $this->log("securityFail`no_auth_param");
        } else {
            $this->log('securityFail`' . $type . "`" . $this->param[$type] . "`" . $this->preParam[$type]);
        }
        // 1, 清除uc乐园登录信息
        \framework\services\Factory::getInstance()->getService('ISso')->sigout();
        // 2, 跳转到中转页面获取重新最新的浏览器参数
        $msg                    = '正在检测浏览器基本信息, 如果页面无法自动跳转，请点击刷新';
        self::$securityFailFlag = true;
        $this->go2Proxy($msg);
    }
    /**
     * @desc 直接获取用户中心通过get方式传送过来的浏览器参数信息
     * @return boolean - 是否操作成功
     */
    private function getUccParamFromUrl()
    {
        // 不再解析来自用户中心传过来的参数
        return false;
        // 尝试通过get方式获取
        // $uc_param_str = urlencode(base64_encode($_SERVER['HTTP_UCCPARA']));
        // $time         = date('Ymd H');
        // $vode         = substr(md5($uc_param_str . $appId . $vKey . $time), 8);
        $http_uccpara  = isset($_GET['u1']) ? $_GET['u1'] : '';
        $code          = isset($_GET['v1']) ? $_GET['v1'] : '';
        if (!empty($http_uccpara) && !empty($code)){
            $this->log("getUccParamFromUrl`start`" . $http_uccpara . "`" . $code);
            // 只信任从用户中心域名过来的的ur1
            $host  = \framework\base\Config::get('protocol.sso', 'Common');
            $host  = parse_url($host);
            $host  = !empty($host['host']) ? $host['host'] : '';
            $refer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            if (!empty($refer)){
                $refer = parse_url($refer);
                $refer = !empty($refer['host']) ? $refer['host'] : '';
            }
            //if ($refer != $host){
            //    $this->log("getUccParamFromUrl`not_auth_domain`" . $refer . "`" . $host);
                // 直接退出
            //    return false;
            //}
            // 1, 解密http_uccpara
	    $http_uccpara = @gzuncompress(base64_decode($http_uccpara));
            // 2, 安全校验, 失效时间为1个小时
            $time   = date('Ymd H');
            $vcode  = substr(md5($http_uccpara . \framework\base\Config::get('sso.appId', 'Common') . \framework\base\Config::get('sso.vKey', 'Common') . $time), 0, 8);
            if ($vcode != $code){
                // 安全校验失败, 可能是没有验证码失败，可能是时间过期了
                $this->log("getUccParamFromUrl`error`" . $http_uccpara . "`" . $code . "`" . $vcode, 'warn');
                return false;
            }
            $this->log("getUccParamFromUrl`ok`" . $http_uccpara);
            $this->uccpara2Session = $http_uccpara;
            return $http_uccpara;
        }
        return false;
    }
    /**
     * @desc 将在url里面传递过来的http_uccpara保存到session里面
     * @return boolean  - 是否操作成功
     */
    private function flushUccParam2Session()
    {
        if (!empty($this->uccpara2Session)){
            $this->log("flushUccParam2Session`" . $this->uccpara2Session);
            $session = \framework\services\Factory::getInstance()->getService('ISession');
            // 保存session
            $session->setBySid($this, array(
                'uccpara' => $this->uccpara2Session
            ));
            $gid     = $session->getSidByUcweb($this->imei, $this->snId, true);
            $this->log("flushUccParam2Session`sid`" . $gid);
            // sid 放到url里面去
            \utils\Url::$isSidInUrl = true;
            \utils\Url::$sid        = $gid;
            return true;
        }
        return false;
    }
    /**
     * @desc 从中间件取得参数
     */
    private function proxy()
    {
        // 优先信任从用户中心过来的参数
        $http_uccpara  = $this->getUccParamFromUrl();
        if (!$http_uccpara){
            // 尝试在cookie加密信息中获取
            $http_uccpara  = \framework\services\Factory::getInstance()->getService('ISession')->getUccPara();
            $this->log("proxy`session`" . $http_uccpara);
        }
        \framework\web\Request::getInstance()->parseRequestTempParam();
        // 确保中转页在中间件模式下也可以正确获取浏览器的参数, 注意，这里只信任中转页面下面的http_uccpara
        $host = \framework\base\Config::get('domain.sso', 'Common');
        // 如果有带ext:e开头的, 将ext:e去掉
        $host = str_replace('ext:e:', '', $host);
        $host = parse_url($host);
        $_SERVER['SERVER_NAME'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
        $this->log("proxy`checkDomain`" . $host['host'] . "`" . $_SERVER['SERVER_NAME']);
        if (!empty($host['host']) && !empty($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == $host['host']){
            // 在信任域名里面，尝试从Header里面获取HTTP_UCCPARA
            $_http_uccpara = isset($_SERVER['HTTP_UCCPARA']) ? $_SERVER['HTTP_UCCPARA'] : '';
            $this->log("proxy`HTTP_UCCPARA`" . $_http_uccpara);
            $http_uccpara       = empty($_http_uccpara) ? $http_uccpara : $_http_uccpara;
        } else {
            $this->log("proxy`checkDomain`fail`" . $host['host'] . "`" . $_SERVER['SERVER_NAME']);
        }
        $this->http_uccpara = $http_uccpara;
        if (!empty($http_uccpara)){
            // 解析出`vcode
            $tmp          = explode('`vcode=', $http_uccpara);
            $http_uccpara = empty($tmp[0]) ? '' : $tmp[0];
            $uccparaVcode = empty($tmp[1]) ? '' : $tmp[1];
            // 校验HTTP_UCCPARA里面的vcode是否合法
            $this->log("proxy`vcode`" . $uccparaVcode . "`http_uccpara`" . $http_uccpara);
            $src = trim($http_uccpara) . 'p98aufiowqru39e';
            // debug模式下面，不校验http_uccpara的vcode
            if ( $uccparaVcode == substr(md5($src), 0, 8) || \framework\base\Config::get('debug', 'Common') ) { 
                // 校验通过，再解析里面的内容
                $paraStr = str_replace("`", "&", $http_uccpara);
                $this->parseHttpUccpara($paraStr);
            } else {
                // vcode解析失败，记录log
                $this->log("proxy`codefail`" . $http_uccpara . "`" . $uccparaVcode . "`" . substr(md5($src), 0, 8), 'info');
            }
        }
        return true;
    }
    /**
     * @desc   解析ucc_para参数
     * @param  $httpUccpara  - HTTP_UCCPARA参数(去除`vcode)
     * @return boolean 是否解析成功
     */
    private function parseHttpUccpara($httpUccpara)
    {
        // 数据校验todo
        $this->log("parseHttpUccpara`start`" . $httpUccpara);
        $httpUccpara = str_replace("`", "&", $httpUccpara);
        $_param      = array();
        parse_str($httpUccpara, $_param);
        if (isset($_param['sn'])){
            $snId    = $this->getSnIdBySn($_param['sn']);
            $this->param['sn']     = $_param['sn'];
            $this->param['snId']   = $snId;
        }
        $this->param['ss']        = isset($_param['ss']) ? $_param['ss'] : '';
        if (empty($this->param['ss']) && isset($_param['width'])){
            $_param['height'] = isset($_param['height']) ? $_param['height'] : '';
            $this->param['ss'] = $_param['width'] . 'x' . $_param['height'];
        }
        $this->param['ver']       = isset($_param['ver']) ? $_param['ver'] : '';
        // 在生产环境, 不信任中间模式里面的li, gi, wi参数
        // 每次都从url里面去拿
        // debug 模式下面为了测试方便，还是支持直接在HTTP_UCCPARA里面传li, gi, wi参数
        if (\framework\base\Config::get('debug', 'Common')){
            $this->param['li']    = isset($_param['li']) ? $_param['li'] : '';
            $this->param['gi']    = isset($_param['gi']) ? $_param['gi'] : '';
            $this->param['wi']    = isset($_param['wi']) ? $_param['wi'] : '';
        }
        $this->param['imei']      = isset($_param['imei']) ? $_param['imei'] : '';
        $this->param['imsi']      = isset($_param['imsi']) ? $_param['imsi'] : '';
        $this->param['ua']        = isset($_param['ua']) ? $_param['ua'] : '';
        $this->param['ip']        = isset($_param['ip']) ? $_param['ip'] : '';
        $this->param['nbr']       = isset($_param['nbr']) ? $_param['nbr'] : '';
        $this->param['fr']        = isset($_param['fr']) ? $_param['fr'] : '';
        $this->param['ln']        = isset($_param['ln']) ? $_param['ln'] : '';
        $this->param['disp']      = isset($_param['disp']) ? $_param['disp'] : '';
        $this->param['feature_bit1']  = isset($_param['feature_bit1']) ? $_param['feature_bit1'] : '';
        $this->param['pfid']      = isset($_param['pfid']) ? $_param['pfid'] : '';
        $this->param['cp']        = isset($_param['cp']) ? urldecode($_param['cp']) : '';
        $this->param['mdn']       = isset($_param['mdn']) ? $_param['mdn'] : '';
        $this->param['sms_no']    = isset($_param['sms_no']) ? $_param['sms_no'] : '';
        $this->param['bid']       = isset($_param['bid']) ? $_param['bid'] : '';
        $this->param['via']       = isset($_param['via']) ? $_param['via'] : '';
        $this->param['bseq']      = isset($_param['bseq']) ? $_param['bseq'] : '';
        $this->param['innerip']   = isset($_param['innerip']) ? $_param['innerip'] : '';
        $this->param['cver']      = isset($_param['cver']) ? $_param['cver'] : '';
        $this->param['width']     = isset($_param['width']) ? $_param['width'] : '';
        $this->param['height']    = isset($_param['height']) ? $_param['height'] : '';
        // 模拟的的参数有比较特的标记
        if (isset($_param['simulateFlag']) && $_param['simulateFlag'] == '1'){
            $this->simulateFlag = true;
        }
        return true;
    }
    /**
     * @desc 根据sn获取snId
     * @param string  $sn  - 传入的sn
     * @return numeric     - 取到的snId
     */
    private function getSnIdBySn($sn)
    {
        if (empty($sn)){
            return false;
        }
        $snId = '';
        if ( isset($sn) ) {
            $tmp = explode("-", $sn);
            $snId = isset($tmp[1]) ? $tmp[1] : '';
        }
        return $snId;
    }
    /**
     * @desc 生成电脑上用的sn
     */
    private function buildComputerSn()
    {
        $buildId = date('ymdH');
        $snId    = substr(md5(time() . rand ( 0, 100000 ) . rand ( 100, 200 )), 0, 10);
        $snId    = sprintf("%u", (int)crc32($snId));
        $snNbr   = "{$buildId}-{$snId}";
        return $snNbr .'-'. substr(md5($snNbr . self::MD5_SN_KEY ), - self::MD5_SN_KEY_LEN );
    }
    /**
     * @desc 跳转到中转页面获取http_uccpara
     * 
     * 使用meta refresh的方式跳转到中转页面，获取浏览器参数信息
     * 
     * @param $msg 中间页面的提示信息
     * @return void
     */
    public function go2Proxy($msg = '')
    {
        $config      = \framework\base\Config::get('sso', 'Common');
        // 获取当前url
        // 清空session的cookie
        $time        = \framework\base\Config::get('cookie.sess.expiretime', 'Common');
        setcookie('uu_auth', '', time() - 360 * 24 * 60 * 60, \framework\base\Config::get('cookie.sess.path', 'Common'), \framework\base\Config::get('cookie.sess.domain', 'Common'));
        $params       = $_GET;
        $params['r']  = isset($_GET['r']) && $_GET['r'] !== 'sso/sigout' ? $_GET['r'] : 'profile';
        // backUrl 不带已有的参数
        $filters      = array('dn', 'pf', 'li', 'gi', 'wi', 've', 'cp', 'sid', 'gid', 'uc_param_str');
        $tmp          = $params;
        $params       = array();
        foreach($tmp as $key => $v){
            // backUrl里面不带dn, pf, ve等参数
            if (in_array($key, $filters)) continue;
            $params[$key] = $v;
        }
        // 保存临时数据
        $hash         = '';
        if (\framework\web\Request::getInstance()->setRequestTempParam($hash)){
            $params[\framework\web\Request::getInstance()->tmpParamTrigger] = $hash;         
        }
        $params = http_build_query($params);
        $url    = \framework\base\Config::get('domain.base', 'Common') . "&" . $params;
        $times  = empty($msg) ? 0 : 3;
        $msg    = empty($msg) ? \framework\base\I18n::getInstance()->parse('ucweb_go2proxy') : $msg;
        $param  = array(
            'appId' => $config['appId'],
            'reUrl' => urlencode($url),
            'vcode' => substr(md5($config['appId'] . $config['vKey'] . $url), 0, 10),
        );
        $proxy   = \framework\base\Config::get('domain.sso', 'Common');
        $prefix  = strpos($proxy, '?') !== false ? '&' : '?';
        $via     = isset($_GET['s1']) ? $_GET['s1'] : '';
        $via     = !empty($via) ? '&s1=' . $via : '';
        $url     = $proxy . $prefix . http_build_query($param) . $via;
        $this->log('go2proxy`' . $url);
        // 记录业务日志
        \utils\Logger::writeApp(array(
            'via' => isset($_GET['s1']) ? $_GET['s1'] : ''
        ), 'go2proxy');
        $date    = date("Y年m月d日, G:i:s");
        exit('
        <?xml version="1.0" encoding="UTF-8"?>
        <!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <title>' . $msg . '</title>
                <meta http-equiv="refresh" content="' . $times . ';url=' . $url . '">
            </head>
            <body>
                <div class="login"><a href="' . $url . '">' . $msg . '</a></div>
                <span class="login_footer">' . $date . '</span><br />
                ©UC 2010
            </body>
        </html>
        ');
    }
    /**
     * @desc 根据dn取得snId
     * @param $dn
     */
    private function getSnIdByDn($dn)
    {
        // 检验该DN是否合法
        if (empty($dn)){
            return false;
        }
        $tmp  = explode('-', $dn);
        $snId = isset($tmp[0]) ? $tmp[0] : '';
        $_dn  = $snId . "-" . substr(md5($snId . self::MD5_DN_KEY), - self::MD5_DN_KEY_LEN);
        if ($dn != $_dn){
            $this->log("getSnIdByDn`unVerifydn`" . $dn . '`' . $_dn, 'warn' );
            self::$dnError      = true;
            self::$dnErrorValue = $dn;
            return false;
        }
        return $snId;
    }
    /**
     * @desc  记录log
     * @param String $msg
     * @param String $level
     */
    private function log($msg, $level = 'debug')
    {
        return \framework\base\Logger::writeLog($level, 'apps_base_Ucweb', $msg);
    }
    /**
     * @desc  支持带IP短的检测
     *
     * @param $userip  - 当前用户的ip
     * @param $iplist  - 允许的ip列表
     * @return boolean 是否通过校验
     */
    private function checkWhiteIp($userip, $iplist) {
        $a_ippart = explode(".", $userip);
        if (!is_array($iplist) || empty($iplist)) return true;
        for ($i = 0; $i < count($iplist); $i++) {
            $counter     = 0;
            $a_badippart = explode(".", $iplist[$i]);
            for ($j = 0; $j < count($a_badippart); $j++) {
                if (((string)$a_badippart[$j] == "*") || ((string)$a_ippart[$j] == (string)$a_badippart[$j])) {
                    $counter++;
                }
            }
            if ($counter == 4) return true;
        }
        return false;
    }
    /**
     * @desc 获取platform的配置
     * @return array 返回Platform.inc.php 的配置
     */
    private function getPlatformConfig()
    {
        if (self::$platformConfig == null){
            self::$platformConfig = require dirname(__FILE__) . '/../../resources/configs/Platform.inc.php';
        }
        return self::$platformConfig;
    }
    /**
     * @desc  显示提示升级之类的页面
     * @param $type  错误的类型
     * @param $value 错误的值
     */
    private function showPlatformLimit($type, $value)
    {
        // 防止多次执行
        if (self::$limitFlag) return false;
        $langMapper = array(
            'imei.allow'       => 'ucweb_limit_imei',
            'imei.deny'        => 'ucweb_limit_imei',
            'ip.allow'         => 'ucweb_limit_ip',
            'ip.deny'          => 'ucweb_limit_ip',
            'snId.allow'       => 'ucweb_limit_sn',
            'snId.deny'        => 'ucweb_limit_sn',
            'pfid'             => 'ucweb_limit_pf',
            'ver'              => 'ucweb_limit_ve',
            'bid'              => 'ucweb_limit_bi',
            'browser.deny'     => 'ucweb_limit_pc',
            'ucweb_unverifydn' => 'ucweb_unverifydn',
            'ucmobilebrowser'  => 'ucweb_limit_ucmobilebrowser'
        );
        // 记录log
        $this->log("showPlatformLimit`" . $type . "`" . $value, 'info');
        $msg = isset($langMapper[$type]) ? $langMapper[$type] : 'ucweb_limit_pc';
        $msg = \framework\base\I18n::getInstance()->parse($msg, array('type' => $type, 'value' => $value));
        if (\framework\base\Config::get('debug', 'Common')){
            $msg = "未授权的操作, 错误类型:". $type . "当前值: " . $value;
        }
        // 退出登录状态
        \framework\services\Factory::getInstance()->getService('ISso')->sigout();
        // 直接清除cookie
        self::$limitFlag = true;
        \framework\base\ExceptionHandler::showBusy($msg, array('status' => 'error'));
    }
    /**
     * @desc 初始化
     */
    public function __construct($configs = array())
    {
	// 如果是在命令行模式下面，不再初始化该信息
	if (defined('UZONE_COMMAND')) return false;
        $this->proxy();
        $this->auth();
        $this->checkIsComputer();
        $this->configs = $configs;
        if (isset($configs['isSimulateHttpUccPara']) && $configs['isSimulateHttpUccPara']){
            $this->simulateHttpUccPara();
        }
        $this->flushUccParam2Session();
    }
    /**
     * @desc dn的key
     * @var  string
     */
    const MD5_DN_KEY          = "BeiJing gogogo 2008!!!";
    /**
     * @desc dn的校验字符的长度
     * @var  numeric
     */
    const MD5_DN_KEY_LEN      = 8;
    /**
     * sn校验key
     * @var string
     */
    const MD5_SN_KEY          = "HeiHeiFlySn!;;";
    /**
     * sn的校验字符的长度
     * @var numeric
     */
    const MD5_SN_KEY_LEN      = 8;
    public $isSimulate        = true;
    private static $limitFlag = false;
    public static $pf;
    public static $isComputer;
    public static $platformConfig;
    public static $obj               = null;
    private static $securityFailFlag = false;
    private static $dnError          = false;
    private static $dnErrorValue     = '';
    private $configs                 = array();
    private $authParam;
    private $param                   = array(
            'imei'      => '',
            'sn'        => '',
            'ip'        => '',
            'snId'      => '',
            'imsi'      => '',
            'fr'        => '',
            'pfid'      => '',
            'ver'       => '',
            'ss'        => '',
            'width'     => '',
            'height'    => '',
            'bseq'      => '',
            'sn'        => '',
            'dn'        => '',
            'ua'        => '',
            'ext_param' => '',
            'sms_no'    => '',
            'rms_size'  => '',
            'cp'        => '',
            'li'        => '',
            'gi'        => '',
            'mi'        => '',
            'wi'        => '',
            'ch'        => ''
    );
    private $uccpara2Session;
    private $http_uccpara;
    private $preParam;
    private $uccpara;
    private $simulateFlag     = false;
    private $defaultPlatform  = 'v3';
    private $pfDispatcher     = array();
}
