<?php
declare(encoding='UTF-8');
namespace framework\datalevel\base;
use framework\base\DalException as DalException;
/**
 * 数据访问工厂类
 * 
 * Example 1: 获取Memcache操作对像
 *
 * <code>
 * <?php
 * $mc = \framework\datalevel\base\Factory::getInstance()->getMc();
 * $mc->get('hello');
 * ?>
 * </code>
 * 
 * Example 2: 获取MySQL操作对像
 * 
 * <code>
 * <?php
 * $mysql = \framework\datalevel\base\Factory::getInstance()->getMysql();
 * $mysql->update('hello');
 * ?>
 * </code>
 * 
 * @category   base
 * @package    datalevel
 * @author liangrn <liangrn@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class Factory
{
	//mc默认组
	const MC_DEFAULT_GROUP = 'default';
	
	static private $s_daFactory = null;
	//tc访问实例
	private $_tc        = null;
	//缓存访问实例
	private $_mc        = null;
	//mysql访问实例
	private $_mysql     = null;
	//sphinx访问实例
	private $_sc        = null;
	/**
	 * 获取memcache访问实例
	 * @param $group 缓存组
	 */
	public function getMc($group = self::MC_DEFAULT_GROUP)
	{
		if(empty($group))
		{
			throw new DalException("getMc Failed, group is empty");
		}
		if($this->_mc == null)
		{
			$this->_mc = new Mc($group);
			return $this->_mc;
		}
		$this->_mc->changeGroup($group);
		return $this->_mc;
	}
	/**
	 * 获取tc访问实例
	 * @param string $serverAddr
	 * @param bool $usePersistentConnect 是否持久连接
	 */
	public function getTc($serverAddr, $usePersistentConnect = true)
	{
		if(empty($serverAddr))
		{
			throw new DalException("getTc Failed, serverAddr is empty");
		}
		if($this->_tc == null)
		{
			$this->_tc = new Tc($serverAddr, $usePersistentConnect);
			return $this->_tc;
		}
		$this->_tc->changeServer($serverAddr, $usePersistentConnect);
		return $this->_tc;
	}
	/**
	 * 获取mysql访问实例
	 * @param string $serverName 服务器名称
	 */
	public function getMysql($serverAddr)
	{
		if(empty($serverAddr))
		{
			throw new DalException("getMysql Failed, serverAddr is empty");
		}
		if($this->_mysql == null)
		{
			require "Mysql.php";
			$this->_mysql = new Mysql($serverAddr);
			return $this->_mysql;
		}
		$this->_mysql->changeServer($serverAddr);
		return $this->_mysql;
	}
    /**
     * 获取sphinx访问实例
     * @param string $group 服务器组名称
     */
    public function getSphinx($group='default')
    {
        if($this->_sc == null)
        {
            require "Sphinx.php";
            $this->_sc = new \datalevel\base\Sphinx($group);
            return $this->_sc;
        }
        $this->_sc->setCurrServer($group);
        return $this->_sc;
    }
	static public function getInstance()
	{
		if(self::$s_daFactory == null)
		{
			self::$s_daFactory = new self();
		}
		return self::$s_daFactory;
	}
}

