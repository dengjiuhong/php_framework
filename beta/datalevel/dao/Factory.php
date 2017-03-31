<?php
/**
 *   数据访问对象工厂类
 *
 * @category   dao
 * @package    datalevel
 * @author liangrn <liangrn@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace framework\datalevel\dao;
require __DIR__ . '/Base.php';
class Factory
{
    /**
     *    获取一个dao对象
     *
     * @param String $daoName  - dao对象的名字
     * @return Object
     */
	public function getDao($daoName)
	{
		if(empty($daoName))
		{
			return null;
		}
		if(!isset(self::$_daos[$daoName]))
		{
			$class = $daoName;
			self::$_daos[$daoName] = new $class();
		}
		return self::$_daos[$daoName];
	}
	public static $_daos = array();
    /**
     *   获取单实例
     */
	static public function getInstance()
	{
		if(self::$s_daoFactory == null)
		{
			self::$s_daoFactory = new self();
		}
		return self::$s_daoFactory;
	}
	static private $s_daoFactory = null;
}
