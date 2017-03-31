<?php
/**
 * 服务器选择策略算法
 *
 * @category   dal
 * @package    datalevel
 * @author liangrn <liangrn@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace framework\datalevel\dal\common;
class ServerSelectStrategy
{
	/**
	 * 用于标记是否存在写数据。
	 * 如果存在写数据，则后续的读操作也选择主服务器，不选择从服务器
	 * 从而可以避免主从服务器数据同步延期导致写数据后，再查数据引起找不到数据的情况。
	 * 
	 * 本方式主要是利用了PHP一次请求申请资源和释放资源的特点来实现，
	 * 此静态变量只会在一次请求中生效，请求之间没有任何影响。
	 */
	static $s_modifyFlag = false; 
	
	/**
	 * 获取服务器
	 * @param  array $serverInfo
	 * array (
	 *       'move' => true /false
	 *       'src'=> array(
				'master'=>'192.168.3.150:1611',
				'slaver'=>'192.168.3.151:1611',
				'connectType'=>1,
				),
			 'dst'=> array(//如果不是迁移的情况，则不存在此服务器信息
				'master'=>'192.168.3.150:1611',
				'slaver'=>'192.168.3.151:1611',
				'connectType'=>1,
				),
	 * @return 返回ip地址、是否长连接和是否从服务器，如：array('addr'=>'192.168.3.150:1411', 'connectType'=>1, 'isSlaver'=>false);
	 */
	static public function getServerAddr($serverInfo, $isRead)
	{
		if(empty($serverInfo))
		{
			return '';
		}
		if($isRead)//读的情况
		{
			if($serverInfo['move'])
			{
				//如果存在修改数据，则选择主服务器
				if(self::$s_modifyFlag || empty($serverInfo['dst']['slaver']))
				{
					return array('addr'			=> $serverInfo['dst']['master'], 
								 'connectType'	=> $serverInfo['dst']['connectType'], 
								 'isSlaver'		=> false
					);
				}
				$addr = self::selectSlaverAddr(explode(',', $serverInfo['dst']['slaver']));
				return array('addr'			=> $addr, 
							 'connectType'	=> $serverInfo['dst']['connectType'], 
							 'isSlaver'		=> true
				);
			}
			else
			{
				//如果存在修改数据，则选择主服务器
				if(self::$s_modifyFlag || empty($serverInfo['src']['slaver']))
				{
					return array('addr'			=> $serverInfo['src']['master'], 
								 'connectType'	=> $serverInfo['src']['connectType'], 
								 'isSlaver'		=> false
					);
				}
				$addr = self::selectSlaverAddr(explode(',', $serverInfo['src']['slaver']));
				return array('addr'			=> $addr, 
							 'connectType'	=> $serverInfo['src']['connectType'], 
							 'isSlaver'		=> true
				);
			}
		}
		else//写的情况
		{
			if($serverInfo['move'])
			{
				return array('addr'			=> $serverInfo['dst']['master'], 
							 'connectType'	=> $serverInfo['dst']['connectType'], 
							 'isSlaver'		=> false
				);
			}
			else
			{
				return array('addr'			=> $serverInfo['src']['master'], 
							 'connectType'	=> $serverInfo['src']['connectType'], 
							 'isSlaver'		=> false
				);
			}
		}
	}
	
	/**
	 * 获取源服务器的主服务器地址
	 * @param array $serverInfo
	 * array (
	 *       'move' => true /false
	 *       'src'=> array(
				'master'=>'192.168.3.150:1611',
				'slaver'=>'192.168.3.151:1611',
				'connectType'=>1,
				),
			 'dst'=> array(//如果不是迁移的情况，则不存在此服务器信息
				'master'=>'192.168.3.150:1611',
				'slaver'=>'192.168.3.151:1611',
				'connectType'=>1,
				),
	 * @return 返回ip地址、是否长连接和是否从服务器，如：array('addr'=>'192.168.3.150:1411', 'connectType'=>1, 'isSlaver'=>false);
	 */
	static public function getSrcMasterAddr($serverInfo)
	{
		if(empty($serverInfo))
		{
			return '';
		}
		return array('addr'			=> $serverInfo['src']['master'], 
					 'connectType'	=> $serverInfo['src']['connectType'], 
					 'isSlaver'		=> false
			);
	}
	
	/**
	 * 获取主服务器
	 * @param array $serverInfo
	 * array (
	 *       'move' => true /false
	 *       'src'=> array(
				'master'=>'192.168.3.150:1611',
				'slaver'=>'192.168.3.151:1611',
				'connectType'=>1,
				),
			 'dst'=> array(//如果不是迁移的情况，则不存在此服务器信息
				'master'=>'192.168.3.150:1611',
				'slaver'=>'192.168.3.151:1611',
				'connectType'=>1,
				),
	 * @return 返回ip地址、是否长连接和是否从服务器，如：array('addr'=>'192.168.3.150:1411', 'connectType'=>1, 'isSlaver'=>false);
	 */
	static public function getMasterAddr($serverInfo)
	{
		if(empty($serverInfo))
		{
			return '';
		}
		if($serverInfo['move'])
		{
			return array('addr'			=> $serverInfo['dst']['master'], 
						 'connectType'	=> $serverInfo['dst']['connectType'], 
						 'isSlaver'		=> false
			);
		}
		else
		{
			return array('addr'			=> $serverInfo['src']['master'], 
						 'connectType'	=> $serverInfo['src']['connectType'], 
						 'isSlaver'		=> false
			);
		}
	}
	
	static public function setModifyFlag($isModify)
	{
		self::$s_modifyFlag = $isModify;
	}

	/**
	 * 从给定的服务器组中按负载算法返回其中一台服务器地址
	 * @param array $servers
	 */
	static private function selectSlaverAddr($servers)
	{
		if(empty($servers))
		{
			return '';
		}
		if(count($servers) == 1)
		{
			return array_pop($servers);
		}
		//使用随机选取方式
		$index = mt_rand(0, count($servers) - 1);
		return $servers[$index];
	}
}
?>