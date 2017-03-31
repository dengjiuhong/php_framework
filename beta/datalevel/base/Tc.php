<?php
/**
 *    tc数据访问接口
 *
 * @category   base
 * @package    datalevel
 * @author liangrn <liangrn@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace framework\datalevel\base;
class Tc
{
	const CONNECT_FAILED_CODE = 2001;
	
	//tc访问实例，以配置的“服务器地址+是否持久连接”为数组下标。 如: "192.168.3.150:1411_0" => new Tc()
	private $_tcs = array();
	//当前的tc实例；
	private $_currentTc = null;

	public function __construct($serverAddr, $usePersistentConnect)
	{
		$this->changeServer($serverAddr, $usePersistentConnect);
	}

	/**
	 * 切换服务器
	 * 为了避免当多台TC服务器的事情，沟通多个Tc对象，
 	 * 这里采用一个Tc类管理多个tc访问实例，通过changeServer来切换。
	 * @param unknown_type $serverAddr
	 * @param unknown_type $usePersistentConnect
	 */
	public function changeServer($serverAddr, $usePersistentConnect)
	{
		if(empty($serverAddr))
		{
			throw new \utils\DalException("tc服务器地址不能为空", 2000);
		}

		//构造key : 服务器地址_是否持久连接
		$key = $usePersistentConnect ? '1' : '0';
		$key = $serverAddr . '_' . $key;
		if(!isset($this->_tcs[$key]))
		{
			if($usePersistentConnect)
			{
				$this->_tcs[$key] = new \Memcached($serverAddr);
			}
			else
			{
				$this->_tcs[$key] = new \Memcached();
			}
			//如果已经设置过则不需要再次设置
			if(count($this->_tcs[$key]->getServerList()) == 0)
			{
				//必须设置不压缩
				$this->_tcs[$key]->setOption(\Memcached::OPT_COMPRESSION, false);
				//设置为非阻塞连接方式，timeout才能生效
				$this->_tcs[$key]->setOption(\Memcached::OPT_NO_BLOCK, true);
				//设置超时时间为3秒
				$this->_tcs[$key]->setOption(\Memcached::OPT_POLL_TIMEOUT, 3000);
				$this->_tcs[$key]->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 1000);
				$this->_tcs[$key]->setOption(\Memcached::OPT_SEND_TIMEOUT, 1000000);
				$this->_tcs[$key]->setOption(\Memcached::OPT_RECV_TIMEOUT, 1000000);
				//设置重连间隔为3秒
				$this->_tcs[$key]->setOption(\Memcached::OPT_RETRY_TIMEOUT, 3);	
				
				//设置当前tc
				$this->_currentTc = $this->_tcs[$key];
				//连接服务器
				return $this->connect($serverAddr, $usePersistentConnect);
			}
		}
		//设置当前tc
		$this->_currentTc = $this->_tcs[$key];
		return true;
	}

	/**
	 * 获取指定key的值
	 * @param string $key
	 * @return 返回指定KEY的值，如果KEY不存在，则返回false
	 */
	public function get($key)
	{
		return $this->_currentTc->get(strval($key));
	}

	/**
	 * 获取多个key的值
	 * @param string $keys
	 * @return 返回多个的值，分别对应于各个KEY，如果某KEY不存在，则对应的值为false
	 * 返回值结构是数组
	 */
	public function multiGet($keys)
	{
		return $this->_currentTc->getMulti($keys);
	}

	/**
	 * 类似set(),设置指定key的值，如果key已存在则失败
	 * @param string $key
	 * @param string $value
	 * @return true-成功, fase-失败，错误抛异常信息
	 */
	public function add($key, $value)
	{
		return $this->_currentTc->add($key, $value);
	}

	/**
	 * 设置指定key的值，如果存在则覆盖，否则增加
	 * @param string $key
	 * @param string $value
	 * @return  true-成功, fase-失败，错误抛异常信息
	 */
	public function set($key, $value)
	{
		return $this->_currentTc->set($key, $value);
	}

	/**
	 * 替换指定key的值，如果存在则覆盖，否则失败
	 * @param string $key
	 * @param string $value
	 * @return  true-成功, fase-失败，错误抛异常信息
	 */
	public function replace($key, $value)
	{
		return $this->_currentTc->replace($key, $value);
	}

	/**
	 * 删除指定key，如果key不存在则失败
	 * @param string $key
	 * @return  true-成功, fase-失败，错误抛异常信息
	 */
	public function delete($key)
	{
		return $this->_currentTc->delete($key);
	}

	/**
	 * increment对key的值进行累加，key不存在时返回false，
	 * value为非数值时将value当成0处理然后改成一个数值，
	 * 数值类型为int64_t，即：最大取值为long long的最大值，最小值为0
	 * @param unknown_type $key
	 * @param unknown_type $offset
	 */
	public function increment($key, $offset=1)
	{
		return $this->_currentTc->increment($key, $offset);
	}

	/**
	 * decrement对key的值进行减法，key不存在时返回false，
	 * value为非数值时将value当成0处理然后改成一个数值，最小为0，
	 * 数值类型为int64_t，即：最大取值为long long的最大值，最小值为0
	 * @param $key
	 * @param $offset
	 */
	public function decrement($key, $offset=1)
	{
		return $this->_currentTc->decrement($key, $offset);
	}
	
	public function getResultCode()
	{
		return $this->_currentTc->getResultCode();
	}

	public function getResultMessage()
	{
		return $this->_currentTc->getResultMessage();
	}
	
	/**
	 * 连接服务器
	 * @param string $serverAddr 服务器地址
	 * @param bool $usePersistentConnect 是否使用持久连接
	 */
	private function connect($serverAddr)
	{
		if($this->_currentTc == null)
		{
			return false;
		}
		//获取ip和port
		list($host, $port) = explode(":", $serverAddr);
		
		$this->_currentTc->addServer($host, $port);
		//验证服务器是否正常
		return $this->_currentTc->set("uc_test_liangrn", 1);

		//第一次试图连接首选服务器
		/*$flag = false;
		if($usePersistentConnect)
		{
			$flag = $this->_currentTc->pconnect($host, $port);
		}
		else
		{
			$flag = $this->_currentTc->connect($host, $port);
		}

		if(!$flag)
		{
			//等待0.1秒
			usleep(100000);
			//进行第二次连接
			if($usePersistentConnect)
			{
				$flag = $this->_currentTc->pconnect($host, $port, 3);
			}
			else
			{
				$flag = $this->_currentTc->connect($host, $port, 3);
			}
			if(!$flag)
			{
				throw new \utils\DalException("服务器{$serverAddr}连接失败!", self::CONNECT_FAILED_CODE);
			}
		}
		return $flag;*/
	}
    /**
     * 设置指定key的value值，如果存在则覆盖，否则增加
     * @param array $keyvals 格式如:array(key1=>val1, key2=>val2)
     * @return  true-成功, fase-失败
     */
    public function setMulti($keyvals)
    {
        return $this->_currentTc->setMulti($keyvals);
    }
    /**
     * 
     *   注意，这是危险操作，用于清空TC所有数据
     */
    public function flush()
    {
        return $this->_currentTc->flush();
    }
}
