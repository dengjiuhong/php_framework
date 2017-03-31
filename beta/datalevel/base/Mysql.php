<?php
declare(encoding='UTF-8');
namespace framework\datalevel\base;
use framework\base\Config as Config;
use framework\base\DalException as DalException;
use framework\utils\Result as Result;
/**
 *   mysql数据访问接口
 *
 * @category   base
 * @package    datalevel
 * @author liangrn <liangrn@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class Mysql
{
	const PROXY_ACCESS_FAILED_CODE = 1001;
	
	private $_serverName = '';

	public function __construct($serverName)
	{
		//固定使用/resources/configs/ucdbi.ini
		$config = Config::getGlobal();
		$res    = \ucdbi_setconfig($config['base']['configs']['basePath'] . '/ucdbi.ini');
		if(!$res)
		{
			throw new DalException("set ucdbi config failed!", 1000);
		}

		$this->_serverName = $serverName;
	}

	/**
	 * 切换服务器
	 * 为了避免以后上百以上服务器的时候，会创建多个mysql对象，
 	 * 所以采用了切换服务器访问的方式
	 * @param string $serverName
	 */
	public function changeServer($serverName)
	{
		if(empty($serverName))
		{
			throw new DalException("服务器名称不能为空", 1002);
		}
		$this->_serverName = $serverName;
	}

	/**
	 * 查询
	 * @param sql
	 * @return 返回数据集(array)
	 */
	public function select($sql)
	{
		if(empty($this->_serverName))
		{
			throw new DalException("服务器名称不能为空!", 1102);
		}
		if(empty($sql))
		{
			throw new DalException("参数不能为空!", 1103);
		}
		$strResult = @\ucdbi_executesql($sql, $this->_serverName);
		if($strResult == null || $strResult == false)
		{
			$error = error_get_last();
			if(!empty($error) && strpos($error['file'], 'Mysql.php') !== false)
			{
				throw new DalException($error['message'], self::PROXY_ACCESS_FAILED_CODE);
			}
			throw new DalException("Mysql Proxy访问失败!" . error_get_last(), self::PROXY_ACCESS_FAILED_CODE);
		}
		$arrResult = json_decode($strResult, true);
		if($arrResult['Result'] == 'FAIL')
		{
			throw new DalException("Sql语句执行出错:". $arrResult['Message']."; 请检查语句:" . $sql, 1104);
		}
		//删除执行结果的信息
		unset($arrResult['Result']);
		if($arrResult['Count'] == 0)
		{
			return Result::ok(array());
		}
		unset($arrResult['Count']);
		unset($arrResult['AffectedRows']);
		
		//////////////////////////////////////
		//因为proxy返回的数组下标为：0,1,10,11,12,13...,19,2,20,21,22,...,29,3,30...
		//所以需要重新排列成：0,1,2,3,4,5,6,7,8,9,10.....
		//////////////////////////////////////
		$finalResult = array();
		$count = count($arrResult);
		for($i = 0; $i < $count; $i++)
		{
			$finalResult[] = $arrResult[$i];//重新排序（号）
		}
		//返回数据集
		return Result::ok($finalResult);
	}

	/**
	 * 执行增加记录操作
	 * @param sql
	 * @return 返回影响的记录数
	 */
	public function insert($sql)
	{
		if(empty($this->_serverName))
		{
			throw new DalException("服务器名称不能为空!", 1202);
		}
		if(empty($sql))
		{
			throw new DalException("参数不能为空!", 1203);
		}
		
		$strResult = @\ucdbi_executesql($sql, $this->_serverName);
		if($strResult == null || $strResult == false)
		{
			$error = error_get_last();
			if(!empty($error) && strpos($error['file'], 'Mysql.php') !== false)
			{
				throw new DalException($error['message'], self::PROXY_ACCESS_FAILED_CODE);
			}
			throw new DalException("Mysql Proxy访问失败!", self::PROXY_ACCESS_FAILED_CODE);
		}
		$arrResult = json_decode($strResult, true);
		if($arrResult['Result'] == 'FAIL')
		{
			throw new DalException("Sql语句执行出错:". $arrResult['Message']."; 请检查语句:" . $sql, 1204);
		}

		return Result::ok($arrResult['AffectedRows']);
	}

	/**
	 * 执行更新记录操作
	 * @param sql
	 * @return 返回影响的记录数
	 */
	public function update($sql)
	{
		if(empty($this->_serverName))
		{
			throw new DalException("服务器名称不能为空!", 1302);
		}
		if(empty($sql))
		{
			throw new DalException("参数不能为空!", 1303);
		}
		$strResult = @\ucdbi_executesql($sql, $this->_serverName);
		if($strResult == null || $strResult == false)
		{
			$error = error_get_last();
			if(!empty($error) && strpos($error['file'], 'Mysql.php') !== false)
			{
				throw new DalException($error['message'], self::PROXY_ACCESS_FAILED_CODE);
			}
			throw new DalException("Mysql Proxy访问失败!", self::PROXY_ACCESS_FAILED_CODE);
		}
		$arrResult = json_decode($strResult, true);
		if($arrResult['Result'] == 'FAIL')
		{
			throw new DalException("Sql语句执行出错:". $arrResult['Message']."; 请检查语句:" . $sql, 1304);
		}
		//因为即使语句没有错并执行成功，如果没有引起字段值的变化，MySQL返回的AffectedRows为0
		//$res = intval($arrResult['AffectedRows']) > 0 ? intval($arrResult['AffectedRows']) : true;
		return Result::ok($arrResult['AffectedRows']);
	}

	/**
	 * 执行删除记录操作
	 * @param sql
	 * @return 返回影响的记录数
	 */
	public function delete($sql)
	{
		if(empty($this->_serverName))
		{
			throw new DalException("服务器名称不能为空!", 1402);
		}
		if(empty($sql))
		{
			throw new DalException("参数不能为空!", 1403);
		}
		$strResult = @\ucdbi_executesql($sql, $this->_serverName);
		if($strResult == null || $strResult == false)
		{
			$error = error_get_last();
			if(!empty($error) && strpos($error['file'], 'Mysql.php') !== false)
			{
				throw new DalException($error['message'], self::PROXY_ACCESS_FAILED_CODE);
			}
			throw new DalException("Mysql Proxy访问失败!", self::PROXY_ACCESS_FAILED_CODE);
		}
		$arrResult = json_decode($strResult, true);
		if($arrResult['Result'] == 'FAIL')
		{
			throw new DalException("Sql语句执行出错:". $arrResult['Message']."; 请检查语句:" . $sql, 1404);
		}

		return Result::ok($arrResult['AffectedRows']);
	}

	/**
	 * 执行SQL命令，此方式在已有操作都不满足的时候才使用
	 * @param sql
	 * @return mysql proxy的返回结果
	 */
	public function execute($sql)
	{
		if(empty($this->_serverName))
		{
			throw new DalException("服务器名称不能为空!", 1502);
		}
		if(empty($sql))
		{
			throw new DalException("参数不能为空!", 1503);
		}
		$strResult = @\ucdbi_executesql($sql, $this->_serverName);
		if($strResult == null || $strResult == false)
		{
			$error = error_get_last();
			if(!empty($error) && strpos($error['file'], 'Mysql.php') !== false)
			{
				throw new DalException($error['message'], self::PROXY_ACCESS_FAILED_CODE);
			}
			throw new DalException("Mysql Proxy访问失败!", self::PROXY_ACCESS_FAILED_CODE);
		}
		$arrResult = json_decode($strResult, true);
		if($arrResult['Result'] == 'FAIL')
		{
			throw new DalException("Sql语句执行出错:". $arrResult['Message']."; 请检查语句:" . $sql, 1504);
		}
		return Result::ok($arrResult);
	}
}

