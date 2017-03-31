<?php
/**
 * UC乐园 - 基础工具  url生成工具
 *
 * @category   url
 * @package    utils
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace framework\web;
use \framework\base\Config as Config;
use \framework\base\Exception as ViewException;
class Url
{
    
    // 模版解析url
    // 支持配置自定义的utl解析器
    public static function tplParse($vars)
    {
        $tmp   = explode(' ', $vars);
        $route = isset($tmp[0]) ? $tmp[0] : '';
        $param = isset($tmp[1]) ? $tmp[1] : '';
        $url   = '';
        switch($route){
            default:
                // 直接输入路由
                // 如 friend, /list  friend/list
                $tmp = explode('/', $route);
                if (count($route) == 1 && strpos($route, '/') === false){
                    $control = Web::$control;
                    $action  = $tmp[0];
                } else {
                    $control = isset($tmp[0]) ? $tmp[0] : Web::$control;
                    $action  = isset($tmp[1]) ? $tmp[1] : 'index';
                }
                $route   = $control . '/' . $action;
                $param   = empty($param) ? "" : "&amp;" . $param;
                $baseUrl = Config::get('domain.base', 'Common') ;
                $prefix  = strpos($baseUrl, '?') !== false ? '&' : '?';
                $url     = $baseUrl . $prefix . "r=" . $route . $param;
                // 再接上sid
                break;
        }
        return $url;
    }
    /**
     *    生成业务的url
     *
     * @param String $route  - 路由
     * @example  friend/list, friend/ profile
     *
     * @param Array  $param  - $_GET参数
     * @example array('key' => 'value')
     *
     * @return String        - 业务对应的Url
     */
    public static function route($route, $param = array())
    {
        $baseUrl = self::getBaseUrl();
        $prefix  = strpos($baseUrl,'?') !== false ? '&' : '?';
        $param   = empty($param) ? '' : '&' . http_build_query($param);
        $url     =  $baseUrl . $prefix . 'r=' . $route . $param;
        return $url;
    }
    /**
     *   获取小应用的入口地址
     * 获取乐园币、逗逗他等等小应用的入口地址
     */
    public static function getBaseAppsUrl()
    {
        $baseUrl = Config::get('domain.apps',"Common");;
        return $baseUrl;
    }
    /**
     *   获取反馈系统的入口url
     */
    public static function getFeedBackUrl()
    {
        $uid           = \base\User::getInstance()->getUid();
        $feedbackVcode = substr(md5("uzone" . $uid . Config::get('vkey', 'FeedBack')), 0, 10);
        $baseUrl = Config::get('server',"FeedBack");;
        $prefix  = strpos($baseUrl,'?') !== false ? '&' : '?';
        return $baseUrl . $prefix . "vcode=" . $feedbackVcode . "&uid=" . $uid;
    }
    /**
     *    获取一个应用的url
     * @param String  $appName  - 应用的名称
     *
     * @return String  - 应用的入口的url
     */
    public static function app($appName)
    {
        return \base\Util::getAppUrl($appName);
    }
    /**
     *    获取动态图片的URl
     *
     * @param   String  $type   - 动态图片的类型
     * @example mood, avatar, photo
     *
     * @param numeric $itemId - 图片的id
     * @param String  $size   - 图片的size
     * @example thum, large, normal
     * @package string $authorId - 图片的作者
     *
     * @return String         - 图片的访问地址
     */
    public static function img($type, $itemId, $size, $authorId = '-1')
    {
        // imgUrl为空了
        if (empty(self::$imgUrl)){
            self::$imgUrl = \framework\base\Config::get('domain.img', 'Common');
        }
        $uid = \apps\base\User::getInstance()->getUid();
        $show= empty($uid) ? rand(1, 9999) : $uid;
        if (self::$isSidInUrl){
            $vcode = substr($uid, 5, 1) . substr($uid, 2, 1);
            $uid   = $vcode . $uid;
            if (self::$imgRewrite){
                $url =  self::$imgUrl . 'imgproxy/' . $type . '/' . $itemId . '/'.$uid.'/' . $size . '/' . $show . '.' . self::$imgExt;
            } else {
                $url = self::$imgUrl . 'imgproxy.php?type=' . $type . '&amp;itemId=' . $itemId . '&amp;uid=' . $uid . '&amp;size=' . $size;
            }
        } else {
            if (self::$imgRewrite){
                $url =  self::$imgUrl . 'imgproxy/' . $type . '/' . $itemId . '/' . $size . '/' . $show . '.' . self::$imgExt;
            } else {
                $url =  self::$imgUrl . 'imgproxy.php?type=' . $type . '&amp;itemId=' . $itemId . '&amp;size=' . $size;
            }
        }
        self::setStaticUrls($url);
        return $url;
    }
    
    public static function staticImg($name, $paltform)
    {
    	$pf = $paltform == 'v5' ? '/v5/' : '/';
    	return \framework\base\Config::get('domain.static', 'Common') . 'images/web' . $pf . $name;
    }
    
    
    
    /**
     *   从静态html里面提出静态文件的图片地址, 放到合并请求的数组里面去
     *
     * @param String $html   - 待抽取的html
     * @return boolean       - 是否成功
     */
    public static function getStaticUrlByHtml($html)
    {
        $attr = array();
        $sid  = \services\Factory::getInstance()->getService('ISession')->getBuildedSid();
        if(preg_match_all("/src\s*=\s*[\"|'](.*?)[\"|']/",$html, $attr)){
            // 抽取出attr
            $imgs = isset($attr[1]) ? $attr[1] : array();
            if (!empty($imgs)){
                foreach($imgs as $img){
                    if (!is_string($img) || empty($img)) continue;
                    // 加到图片数组里面去
                    self::setStaticUrls($img);
                }
            }
        }
        return true;
    }
    /**
     *    获取css的图片地址
     * @param unknown_type $param
     */
    public static function cssUrl($param)
    {
        return self::staticUrl('css/' . $param, false);
    }
    /**
     *    获取静态图片的地址
     * @param unknown_type $param
     */
    public static function staticUrl($param, $isProxy = true)
    {
        if (isset(self::$urlCache[$param])) return self::$urlCache[$param];
        self::$urlCache[$param] = self::getStaticUrl() . $param;
        if ($isProxy){
            self::setStaticUrls(self::$urlCache[$param]);
        }
        return self::$urlCache[$param];
    }
    /**
     *   输出图片合并请求的特殊标签
     */
    public static function renderExtraProxy()
    {
        $urls = implode('`', self::$staticUrls);
        $extraProxy = '<!-- ExtraProxy="'.\framework\base\Config::get('imgServer', 'ExtraProxy').'" ' . "\n" . 'urls="' . $urls . '" -->';
        return $extraProxy;
    }
    /**
     *     获取乐园的基础Url
     * @return String 乐园的基础Url
     */
    public static function getBaseUrl()
    {
        if (self::$baseUrl == null){
            self::$baseUrl = \framework\base\Config::get('domain.base',"Common");
        }
        return self::$baseUrl;
    }
    /**
     *   获取动态图片的url
     */
    private static function getImgUrl()
    {
        if (self::$imgUrl == null){
            self::$imgUrl = \framework\base\Config::get('domain.img', 'Common');
        }
        return self::$imgUrl;
    }
    private static function getStaticUrl()
    {
        if (self::$staticUrl == null){
            self::$staticUrl = \framework\base\Config::get('domain.static', 'Common');
        }
        return self::$staticUrl;
    }
    /**
     *   获取当前缓存的图片的地址的集合
     */
    public static function getStaticUrls()
    {
        return self::$staticUrls;
    }
    /**
     *    添加一个合并请求需要新增的图片的url
     * @param string $url
     */
    private static function setStaticUrls($url)
    {
        if (isset(self::$staticUrls[$url])) return false;
        self::$staticUrls[$url] = $url;
        return true;
    }
    /**
     *   时候在url 上面传递sid
     * @var  boolean
     */
    public static $isSidInUrl = false;
    /**
     *   需要在url上面传递的sid
     * @var  String
     */
    public static $sid;
    public static $urlCache = array();
    public static $staticUrls = array();
    /**
     *   动态图片url是否rewrite
     * @var  boolean
     */
    private static $imgRewrite = true;
    /**
     *   动态图片url的默认扩展名
     * @var  String
     */
    private static $imgExt     = 'jpg';
    private static $imgUrl;
    private static $staticUrl;
    /**
     *   当前的基础url
     * @var  String
     */
    private static $baseUrl;
}
