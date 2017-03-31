<?php
declare(encoding='UTF-8');
namespace framework\base;
/**
 * UC乐园 - 基础工具  i18n语言包工具
 *
 * @category   i18n
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class I18n
{
    /**
     *   获取i18n语言包的单实例
     */
    public static function getInstance()
    {
        if (self::$obj == null){
            self::$obj = new self();
        }
        return self::$obj;
    }
    /**
     *   初始化，加载配置
     */
    public function __construct()
    {
        // 初始化配置文件
        $config = Config::getGlobal();
        if (!empty($config['base']['i18n'])){
            foreach($config['base']['i18n']['lan'] as $type => $file)
            {
                $this->i18ns[$type] = require $file;
            }
        }
        unset($config);
    }
    /**
     *    解析语言包
     *
     * @example  支持    total user num: {$num}  这样
     * @param String  $lang  - 语言包里面对应的key
     * @param Array   $vars  - 语言包需要替换的变量
     * @return String        - 替换出来的语言包
     */
    public function parse($lang, $vars = array())
    {
        if (isset(self::$parsed[$lang])) return self::$parsed[$lang];
        $langs = isset($this->i18ns[$this->location]) ? $this->i18ns[$this->location] : '';
        $tmp    = isset($langs[$lang]) ? $langs[$lang] : $lang;
        if (!empty($vars)){
            foreach ($vars as $key => $var) {
                $tmp = preg_replace('/{?\$'.$key .'}?/', $var, $tmp);
            }
            unset($key);
            unset($var);
        }
        self::$parsed[$lang] = $tmp;
        unset($tmp);
        unset($langs);
        unset($vars);
        return self::$parsed[$lang];
    }
    /**
     *    设置当前使用的语言包
     * @param $location
     */
    public function setLocation($location)
    {
        if(empty($location) || !in_array($location, $this->available)){
            throw new \utils\UtilsException("un verify i18n location " . $location);
        }
        $this->location = $location;
    }
    /**
     *   返回当前在使用的语言包
     */
    public function getCurrentLocation()
    {
        return $this->location;
    }
    /**
     *   可用的语言包
     * @var  Array
     */
    private $available     = array('zh_CN', 'en_US');
    /**
     *   语言包
     * @var  array
     */
    private $i18ns         = array();
    /**
     *   当前使用的语言包, 默认为zh_CN
     * @var  String
     */
    private $location      = 'zh_CN';
    /**
     *   缓存起来的解析过的语言
     * @var  Array
     */
    private static $parsed = array();
    /**
     *   i18n工具的单实例
     * @var unknown_type
     */
    private static $obj    = null;
}
