<?php
use \framework\web\Url as BaseUrl;
class Url extends BaseUrl
{
    /**
     * @desc  生成业务的url
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
        if (self::$isSidInUrl){
            $url     =  $baseUrl . $prefix . 'sid=' . self::$sid . '&r=' . $route . $param;
        } else {
            $url     =  $baseUrl . $prefix . 'r=' . $route . $param;
        }
        return $url;
    }
    /**
     * @desc 获取url上面的sid
     *
     * @return
     * 如果是启用sid模式, 直接返回&amp;sid=xxx
     * 如果是非sid模式, 直接返回空
     */
    public static function getSid()
    {
        if (self::$isSidInUrl & !empty(self::$sid)){
            return '&amp;sid=' . self::$sid;
        }
        return '';
    }
}