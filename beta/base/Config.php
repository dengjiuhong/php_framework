<?php
declare(encoding='UTF-8');
namespace framework\base;
/**
 * UC乐园  通用工具类 - 获取配置参数
 *
 * @category   configs
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2011 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
final class Config {
    /**
     * 
     * 初始化基础框架的配置
     * @param array $configs
     */
    public static function initGlobal($configs)
    {
        self::$frameworkConfig = $configs;
    }
    /**
     * 获取框架的配置
     */
    public static function getGlobal()
    {
        return self::$frameworkConfig;
    }
    /*
     * 初始化基本配置
     * @param String  $type  - 配置文件的名字，不带 inc
     */
    public static function init($type = 'Common') {
        if (!isset(self::$configs[$type])) {
            self::$configType[]   = $type;
            $configs  = include self::$frameworkConfig['base']['configs']['basePath'] . "/" . ucfirst($type) . '.inc.php';
            self::$configs[$type] = $configs;
            self::$currentType    = $type;
            self::$isInit = true;
        }
    }
    /*
     * 读取基本配置的方法
     * @param String  $token  - 递进关系的token信息
     * @example               - domain.base
     */
    public static function get($token = '', $configType = 'Common') {
        if (!self::$isInit) self::init();
        if (!in_array($configType, self::$types)){
            self::$types[] = $configType;
        }
        if (!empty($configType)){
            if (!isset(self::$configs[$configType])){
                self::init($configType);
            }
            $configs     = self::$configs[$configType];
            $currentType = $configType;
        } else {
            $configs     = self::$configs[self::$currentType];
            $currentType = self::$currentType;
        }
        if (empty($token)){
       		return $configs;
        }
        if (strpos($token, '.') === false) {
            if (isset($configs[$token])) {
                return $configs[$token];
            } else {
                throw new Exception('config index ' . $token . ' not found, current config ' . $currentType);
            }
        } else {
            $tokens = explode('.', $token);
            $config = $configs;
            foreach ($tokens as $the_token) {
                if (!isset($config[$the_token])){
                    throw new Exception('config index ' . $the_token . ' not found, current config ' . $currentType);
                }
                $config = $config[$the_token];
            }
            return $config;
        }
    }
    /**
     *   重新加载配置
     */
    public static function refresh()
    {
        if (!empty(self::$types)){
            foreach(self::$types as $type){
               $configs  = include self::$frameworkConfig['base']['configs']['basePath'] . '/' . ucfirst($type) . '.inc.php';
               self::$configs[$type] = $configs;
            }
        }
        return true;
    }
    private static $types = array();
    private static $shmid           = 01322;
    private static $isInit          = false;
    private static $configType      = array();
    private static $currentType     = '';
    private static $configs         = array();
    private static $frameworkConfig;
}
