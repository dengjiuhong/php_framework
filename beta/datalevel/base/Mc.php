<?php
declare(encoding='UTF-8');
namespace framework\datalevel\base;
use framework\base\Config as Config;
use framework\base\DalException as DalException;
/**
 * 缓存数据访问接口
 *
 * @category   base
 * @package    datalevel
 * @author liangrn <liangrn@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class Mc
{
	//tc的访问实例
	private $_currentMd = null;
	private $_mds = array();
	
	const PERSISTENT_CONNENT_NAME = "uzone_mc_persistent_connect_";

	public function __construct($group)
	{
	    McServersConfig::init();
		$this->changeGroup($group);
	}

	public function changeGroup($group)
	{
		if(isset($this->_mds[$group]))
		{
			$this->_currentMd = $this->_mds[$group];
			return true;
		}
		$servers = McServersConfig::getServers($group);
		if(empty($servers))
		{
			throw new DalException("mc服务器地址不能为空,请检查组名{$group}是否存在！", 3002);
		}
		if(!is_array($servers))
		{
			throw new DalException("参数必须为数组", 3003);
		}
		$compressed = McServersConfig::getCompressed();
		$usePersistentConnect = McServersConfig::getConnectType();
		if($usePersistentConnect == 1)//是否长连接
		{
			$this->_currentMd = new \Memcached(self::PERSISTENT_CONNENT_NAME . $group);
		}
		else
		{
			$this->_currentMd = new \Memcached();
		}
		
		//如果是长连接，且已经存在servers，则不需要重复增加
		//注：目前的判断方式还是不够准确的，应该需要对已经加进去的Servers跟需要加进去的Server进行差异比较等等
		//    但由于Memcahce更多是用短连接，所以暂时先简化判断处理
		if($usePersistentConnect == 1 && count($this->_currentMd->getServerList()) == count($servers))
		{
			$this->_mds[$group] = $this->_currentMd;
			return true;
		}
		
		//设置一致性Hash
		$this->_currentMd->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
		$this->_currentMd->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
		//$this->_currentMd->setOption(\Memcached::OPT_HASH, \Memcached::HASH_CRC);
		//设置是否压缩
		$this->_currentMd->setOption(\Memcached::OPT_COMPRESSION, $compressed);
		
		$failedCount = 0;
		$arrServers = array();
		foreach($servers as $server)
		{
			$arrServer = explode(':', $server);
			$arrServer[] = 0;//权重，默认都为0
			//添加服务器
			$arrServers[] = $arrServer;
		}
		if(!empty($arrServers))
		{
			if(!$this->_currentMd->addServers($arrServers))
			{
				$this->_currentMd = null;
				throw new DalException("添加缓存服务器失败", 3004);
			}
		}
		$this->_mds[$group] = $this->_currentMd;
		return true;
	}
	/**
	 * 获取指定key的值
	 * @param string $keys
	 * @return 返回指定KEY的值，如果KEY不存在，则返回false
	 */
	public function get($key)
	{
		if(!$this->_currentMd) return false;
		$v = $this->_currentMd->get($key);
        // 当无该缓存时，写下日志
        // 格式为"key|[0|1]"，0表示无缓存，1表示命中缓存
        if (McServersConfig::getLogEnabled() && $v === false)
        {
            \utils\Logger::i('datalevel_Mc_get', $key . '|0');
        }
        return $v;
	}
	public function getResultCode()
	{
	    return $this->_currentMd->getResultCode();
	}
	/**
	 * 获取多个key的值
	 * @param array $keys
	 * @return 返回多个的值，分别对应于各个KEY，如果某KEY不存在，则对应的值为false
	 * 返回值结构是数组
	 */
	public function multiGet($keys)
	{
		if(!$this->_currentMd) return false;
		return $this->_currentMd->getMulti($keys);
	}

	/**
	 * 增加指定key的值，如果key已存在则失败
	 * @param string $key
	 * @param string $value
	 * @return true-成功, fase-失败，错误抛异常信息
	 */
	public function add($key, $value, $expire=0)
	{
		if(!$this->_currentMd) return false;
		return $this->_currentMd->add($key, $value, $expire);
	}

	/**
	 * 设置指定key的值，如果存在则覆盖，否则增加
	 * @param string $key
	 * @param string $value
	 * @return  true-成功, fase-失败，错误抛异常信息
	 */
	public function set($key, $value,$expire=0)
	{
		if(!$this->_currentMd) return false;
		return $this->_currentMd->set($key, $value,$expire);
	}

	/**
	 * 替换指定key的值，如果存在则覆盖，否则失败
	 * @param string $key
	 * @param string $value
	 * @return  true-成功, fase-失败，错误抛异常信息
	 */
	public function replace($key, $value,$expire)
	{
		if(!$this->_currentMd) return false;
		return $this->_currentMd->replace($key, $value,$expire);
	}

	/**
	 * 删除指定key，如果key不存在则失败
	 * @param string $key
	 * @return  true-成功, fase-失败，错误抛异常信息
	 */
	public function delete($key)
	{
		if(!$this->_currentMd) return false;
		return $this->_currentMd->delete($key);
	}

	public function increment($key, $offset=1)
	{
		if(!$this->_currentMd) return false;
		return $this->_currentMd->increment($key, $offset);
	}

	public function decrement($key, $offset=1)
	{
		if(!$this->_currentMd) return false;
		return $this->_currentMd->decrement($key, $offset);
	}
	
	/**
	 * 
	 *   注意，这是危险操作，用于清空memcache所有缓存
	 */
	public function flush()
	{
	    if(!$this->_currentMd) return false;
        return $this->_currentMd->flush();
	}
}
/**
 * 缓存数据访问接口配置
 *
 * @category   base
 * @package    datalevel
 * @author liangrn <liangrn@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class McServersConfig
{
	public static function getServers($group)
	{
		if(!isset(self::$s_configs[$group]))
		{
			return array();
		}
        return self::$s_configs[$group];
	}
	public static function getConnectType()
	{
	    return self::$s_configs['connectType'];
	}
	public static function getCompressed()
	{
		if(isset(self::$s_configs['compressed']))
		{
			return self::$s_configs['compressed'];
		}
		return false;
	}
    public static function getLogEnabled()
    {
        if(isset(self::$s_configs['logEnabled']))
        {
            return self::$s_configs['logEnabled'];
        }
        return false;
    }
	public static function init()
	{
	    if (self::$s_configs !== null) 
	    {
	    	return self::$s_configs;
	    }
	    $config = Config::getGlobal();
	    self::$s_configs = require $config['base']['configs']['basePath'] . '/Mc_Servers.inc.php';
	}
	
	private static $s_configs = null;
}
