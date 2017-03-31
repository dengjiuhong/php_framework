<?php
declare(encoding='UTF-8');
namespace framework\services;
use framework\base\Config as Config;
use framework\base\Exception as Exception;
/**
 * UC乐园  业务服务工厂类，创建并返回指定业务服务
 * <br>
 * Example 1: 获取ITest的业务逻辑层
 *
 * <code>
 * <?php
 *      // ITest有sayHello方法
 *      $service = \framework\services\Factory::getInstance()->getService('ITest');
 *      $service->sayHellot('hello');
 * ?>
 * 
 * </code>
 * @category   util
 * @package    services
 * @author liangrn <liangrn@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class Factory
{
    /**
     * 获取单实例
     */
    public static function getInstance()
    {
        if(self::$s_factory === null)
        {
            self::$s_factory = new self();
        }
        return self::$s_factory;
    }
    /**
     *   获取指定类型服务
     * @param string $serviceType --前面ServiceType定义的服务类型
     */
    public function getService($serviceType)
    {
        //先在缓存服务中检查是否已经创建，如果有则直接返回
        if(isset(self::$_services[$serviceType]))
        {
            return self::$_services[$serviceType];
        }
        //创建指定业务服务
        $svr = $this->createService($serviceType);
        if($svr === null)
        {
            return null;
        }
        //缓存服务
        self::$_services[$serviceType] = $svr;
        return $svr;
    }
    /**
     * 创建指定服务
     * @param string $serviceType --前面ServiceType定义的服务类型
     */
    private function createService($serviceType)
    {
        /**
         * 根据配置映射关系，创建实现类实例。
         * 而对于实现了多个接口的实现类，目前的规则会创建多个实例。
         */
        if(!isset($this->_svrImpls[$serviceType]))
        {
            //需要国际化
            throw new \framework\base\ServiceException("It hasn't such a service type:" . $serviceType);
        }
        //require_once __DIR__ . '/interfaces/' . $serviceType . '.php';
        
        return new $this->_svrImpls[$serviceType];
    }
    /**
     * 初始化
     * @throws Exception
     */
    private function __construct()
    {
        $conf = Config::getGlobal();
        if (!isset($conf['services']['type']) || !isset($conf['services']['implsMapper'])){
            throw new Exception("please init services's config");
        }
        $this->_svrImpls = require $conf['services']['implsMapper'];
    }
    private $_svrImpls         = null;
    private static $s_factory = null;
    private static $_services = array();
    public static $isLoad     = array();
}
