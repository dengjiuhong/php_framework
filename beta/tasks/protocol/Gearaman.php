<?php
/**
 * UC乐园  任务管 - gearman 操作
 *
 * @category   dispatcher
 * @package    tasks
 * @author     Jiuhong Deng <dengjiuhong@gmail.com>
 * @version    $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace tasks\gearman;
class Factory
{
    public static $gearmanClientObj;
    public static $gearmanWorkerObj;
    public static $obj             = null;
    public static $serverConfig    = array();
    public static $jobRouterConfig = array();
    /**
     *   初始化配置
     */
    public function __construct()
    {
        if (empty(self::$serverConfig)){
            self::$serverConfig   = include dirname(__FILE__) . '/../../resources/configs/JobServers.inc.php';
        }
        return  self::$serverConfig;
    }
    /**
     *   获取单实例
     */
    public static function getInstance()
    {
        if (self::$obj === null){
            self::$obj = new self();
        }
        return self::$obj;
    }
    /**
     *    根据业务取得job server
     * @param string $module  - 业务类型
     * @return array          - 机器的配置
     */
    public function getJobServer($module)
    {
        if (!isset(self::$serverConfig[$module])){
            throw new \framework\base\Exception("unkow job server " . $module);
        }
        return self::$serverConfig[$module];
    }
    /**
     *   取得gearman client的操作对象
     *
     * @param string $module    - 业务名称
     * @return object
     */
    public function getClientInstance($module = '', $workerType = '')
    {
	$workerType = empty($workerType) ? $module : $workerType;
        if (!isset(self::$gearmanClientObj[$workerType])) {
            $server = $this->getJobServer($module);
            self::$gearmanClientObj[$workerType] = new \GearmanClient();
            $server['addr'] = explode(',', $server['addr']);
            shuffle($server['addr']);
            $server['addr'] = implode(',', $server['addr']);
            self::$gearmanClientObj[$workerType]->addServers($server['addr']);
            self::$gearmanClientObj[$workerType]->setTimeout($server['timeout']);
        }
        return self::$gearmanClientObj[$workerType];
    }
    /**
     *   取得gearman worker的操作对象
     *
     * @param string $module    - 业务名称
     * @return object
     */
    public function getWorkerInstance($module = '')
    {
        if (!isset(self::$gearmanWorkerObj[$module])){
            $server = $this->getJobServer($module);
            self::$gearmanWorkerObj[$module] = new \GearmanWorker();
            $server['addr'] = explode(',', $server['addr']);
            shuffle($server['addr']);
            $server['addr'] = implode(',', $server['addr']);
            self::$gearmanWorkerObj[$module]->addServers($server['addr']);
            //self::$gearmanWorkerObj->setTimeout($server['timeout']);
            $server = self::getJobServer($module);
        }
        return self::$gearmanWorkerObj[$module];
    }
}
