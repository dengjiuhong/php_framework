<?php
/**
 *    数据访问接口
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
namespace framework\datalevel\dal;
require dirname(__FILE__) . '/Conditions.php';
require dirname(__FILE__) . '/common/DbConfiguration.php';
require dirname(__FILE__) . '/common/ServerSelectStrategy.php';
require dirname(__FILE__) . '/common/MysqlCommandParser.php';
require dirname(__FILE__) . '/common/TcKeyFormat.php';
use framework\datalevel\dal\common\DbConfiguration as DbConfiguration;
use framework\datalevel\dal\common\MysqlCommandParser as MysqlCommandParser;
use framework\datalevel\dal\common\ServerSelectStrategy as ServerSelectStrategy;
use framework\datalevel\dal\common\TcKeyFormat as TcKeyFormat;
use framework\datalevel\base\Factory as DaFactory;
use framework\datalevel\base\Tc as MyTc;
use framework\datalevel\base\Mysql as Mysql;
use framework\utils\Result as Result;
class Dal
{
	private static $s_dal;

	private $_dbConfig = null;
	private function __construct()
	{
		$this->_dbConfig = DbConfiguration::getInstance();
	}
	/**
	 * 从多个键值或多个table中查询数据
	 * @param array $shardIds
	 * 需要查询的且用于划分数据的id键值，本函数会根据这些键值导向到具体数据库，然后执行后面参数指定的命令
	 * 此参数构成的条件一般不会被自动加到conditions中，它只作为定位数据库，如果需要作为conditions中的一个条件，则需要自行添加到conditions
	 * 如array(
	 * 		12345432,//键值
	 * 		10000000,//键值
	 * 		.......
	 * )
	 * @param array tables 表名,可以多个表,但不允许部分表存在TC，部分表存在MySQL的情况。即使存在，也会忽略
	 * 如：array('feeds_feed_list', 'feeds_my_feed_list')
	 * 另外，如果检查到所查询的表是存储在TC中，而又存在多个表名，则会忽略其他表，只取第一个。
	 * @param array columns 字段名称，如果查询全部，则为设置为NULL，如果是多表查询，则需要在字段前加表名
	 * 如：array('feedId', 'feedType') 或  array('feeds_feed.feedId', 'feeds_my_feed_list.mergeTag)
	 * 【注】此参数暂时对TC保存的数据失效，暂不支持
	 * @param array conditions 查询条件，如果没有条件则设置为NULL。可以使用工具类Conditions组装条件
	 * 【注】此参数暂时对TC保存的数据失效，暂不支持
	 * @param int limit 查询的记录数，0为查询所有
	 * 【注】此参数暂时对TC保存的数据失效，暂不支持
	 * @param int offset 查询的记录偏移位置。
	 * 【注】此参数暂时对TC保存的数据失效，暂不支持
	 * @param array orderby 排序，可以多组。
	 * 如array('updateTime'=>'desc', 'feedId'=>'asc');
	 * 【注】此参数暂时对TC保存的数据失效，暂不支持
	 * @return 数据集(array),内容视查询的表和字段而定。
	 * 使用utils\Result格式返回。
	 * array(
	 * 		'status'=>
	 * 		'data'=>array{
	 * 				0/key1=>array(field1,field2,......),
	 * 				1/key2=>array(field1,field2,......),
	 * 		)
	 * 如果没有找到数据，则'data'为空数组。
	 * array(
	 * 		'status'=>ok
	 * 		'data'  =>array();
	 * )
	 * 如果失败则使用Result::error返回失败信息和失败代号
	 */
	public function multiSelect($shardIds, $tables, $columns = null, $conditions = null, $limit = 0, $offset = 0, $orderby = null)
	{
		if(!is_array($shardIds) || empty($shardIds))
		{
			return Result::error("shardIds必须是数组且不能为空", "dal_shardIds_invalid_001", 'warn');
		}
		if(!is_array($tables) || empty($tables))
		{
			return Result::error("表名必须是数组且不能为空", 'dal_multiselect_tables_empty_002', 'warn');
		}
		if($limit < 0 || $offset < 0)
		{
			return Result::error("limit和offset不能为负数", "dal_params_invalid_002", 'warn');
		}
		//取第一个表来判断数据库类别。不支持部分数据在MySQL，部分在TC的数据查询
		$dbType = $this->_dbConfig->getDbType($tables[0]);
		if($dbType != DbConfiguration::DB_MYSQL && $dbType != DbConfiguration::DB_TC)
		{
			return Result::error("请检查数据表名是否正确,执行失败!" . $tables[0], 'dal_table_name_error_001');
		}
		//$shardKey = $this->_dbConfig->getShardKey($tables[0]);
		//对Ids按服务器进行分组
		$classifiedIds = $this->_dbConfig->classifyIdsByServer($dbType, $shardIds);
		if($dbType == DbConfiguration::DB_MYSQL)
		{
			return $this->multiSelectFromMysql($classifiedIds, $tables, $columns, $conditions, $limit, $offset, $orderby);
		}
		else
		{
			return $this->multiSelectFromTc($classifiedIds, $tables);
		}
	}

	/**
	 * 获取一个键值和从一个table中获取数据。可参阅multiSelect的说明
	 * @param string/int $shardId 单个值
	 * @param string $table 一个table
	 * @param array $columns
	 * @param array $conditions
	 * @param int $limit
	 * @param int $offset
	 * @param array $orderby
	 * @return 数据集(array),内容视查询的表和字段而定。
	 * 使用utils\Result格式返回。
	 * array(
	 * 		'status'=>
	 * 		'data'=>array{
	 * 				0=>array(field1,field2,......),
	 * 				1=>array(field1,field2,......),
	 * 		)
	 * )
	 *  如果没有找到数据，则'data'为空数组。
	 * array(
	 * 		'status'=>ok
	 * 		'data'  =>array();
	 * )
	 * 如果失败则使用Result::error返回失败信息和失败代号
	 */
	public function select($shardId, $table, $columns = null, $conditions = null, $limit = 0, $offset = 0, $orderby = null)
	{
		if(empty($shardId))
		{
			//debug_print_backtrace();
			return Result::error("shardId不能为空" . $shardId, "dal_shardId_invalid_001", 'warn');
		}
		if(empty($table))
		{
			return Result::error("表名不能为空", 'dal_select_tables_empty_002', 'warn');
		}
		if($limit < 0 || $offset < 0)
		{
			return Result::error("limit和offset不能为负数", "dal_params_invalid_002", 'warn');
		}
		//取第一个表来判断数据库类别。不支持部分数据在MySQL，部分在TC的数据查询
		$dbType = $this->_dbConfig->getDbType($table);
		if($dbType == DbConfiguration::DB_MYSQL)
		{
			return $this->selectFromMysql($shardId, $table, $columns, $conditions, $limit, $offset, $orderby);
		}
		else if($dbType == DbConfiguration::DB_TC)
		{
			return $this->selectFromTc($shardId, $table);
		}
		else
		{
			throw new \utils\DalException("请检查数据表名是否正确,执行失败!" . $table, 5001);
		}
	}


	/**
	 * 新增数据
	 * @param string $shardId 单个键值
	 * 需要查询的且用于划分数据的键值，程序会根据这些键值导向到具体数据库，然后执行后面参数指定的命令
	 * @param array table 表名
	 * @param col_values 更新的字段和值。
	 * 如array('mergeType' => 0,
	 * 		   'itemTotal' => 10',
	 * 		   'content'   => 'abc',
	 * )
	 * @return
	 * * array(
	 * 		status=>
	 * 		data=>true(false),
	 * 		)
	 * )
	 * 如果失败则使用Result::error返回失败信息和失败代号
	 */
	public function insert($shardId, $table, $col_values)
	{
		if(empty($shardId))
		{
			return Result::error("shardId不能为空", "dal_shardId_empty_001", 'warn');
		}
		if(empty($table))
		{
			return Result::error("表名不能为空", 'dal_insert_tables_empty_002', 'warn');
		}
		if(empty($col_values))
		{
			return Result::error("数据不能为空", 'dal_insert_colvalues_empty_003', 'warn');
		}

		//设置修改数据标记
		ServerSelectStrategy::setModifyFlag(true);

		$dbType = $this->_dbConfig->getDbType($table);
		if($dbType == DbConfiguration::DB_MYSQL)
		{
			return $this->insertToMysql($shardId, $table, $col_values);
		}
		else if($dbType == DbConfiguration::DB_TC)
		{
			return $this->insertToTc($shardId, $table, $col_values);
		}
		else
		{
			throw new \utils\DalException("请检查数据表名是否正确,执行失败!" . $table, 5001);
		}
	}

	/**
	 * 更新数据
	 * @param array $shardId 单个id
	 * 需要查询的且用于划分数据的键值，程序会根据这些键值导向到具体数据库，然后执行后面参数指定的命令
	 * @param array table 表名
	 * @param array col_values 更新的字段和值，字段顺序必须跟配置的字段顺序一致；可以是部分字段值
	 * 注：如果字段名称后面出现'+'号，则代表累计值,字段必须是数值；另外需要设置hasAccumulated为true
	 * 如array('mergeType' => 0,
	 * 		   'itemTotal' => 10',//如果是'itemTotal+'=>10，则代表是itemTotal=itemTotal+10
	 * 		   'content'   => 'abc',
	 * )
	 * @param array conditions 参考select的说明
	 * @param bool hasAccumulated 是否存在累加。加此参数主要是为了避免每次都检查每个字段是否存在累加，从而造成性能损失。
	 * @return 更新记录数或false
	 * array(
	 * 		status=>
	 * 		data => 是否成功(true/false)
	 * )
	 * 如果失败则使用Result::error返回失败信息和失败代号
	 */
	public function update($shardId, $table, $col_values, $conditions, $hasAccumulated = false)
	{
		if(empty($shardId))
		{
			return Result::error("shardId不能为空", "dal_shardId_empty_001", 'warn');
		}
		if(empty($table))
		{
			return Result::error("表名不能为空", 'dal_update_tables_empty_002', 'warn');
		}
		if(empty($col_values))
		{
			return Result::error("数据不能为空", 'dal_update_colvalues_empty_003', 'warn');
		}
		//设置修改数据标记
		ServerSelectStrategy::setModifyFlag(true);

		$dbType = $this->_dbConfig->getDbType($table);
		if($dbType == DbConfiguration::DB_MYSQL)
		{
			return $this->updateToMysql($shardId, $table, $col_values, $conditions, $hasAccumulated);
		}
		else if($dbType == DbConfiguration::DB_TC)
		{
			return $this->updateToTc($shardId, $table, $col_values, $hasAccumulated);
		}
		else
		{
			throw new \utils\DalException("请检查数据表名是否正确,执行失败!" . $table, 5001);
		}
	}

	/**
	 * 删除数据
	 * @param array $shardId 单个id
	 * 需要查询的且用于划分数据的键值，程序会根据这些键值导向到具体数据库，然后执行后面参数指定的命令
	 * @param array tables 表名
	 * @param array conditions 参考select的说明
	 * @return 删除的记录数或false
	 * array(
	 * 		status=>
	 * 		data => 是否成功(true/false)
	 * )
	 * 如果失败则使用Result::error返回失败信息和失败代号
	 */
	public function delete($shardId, $table, $conditions)
	{
		if(empty($shardId))
		{
			return Result::error("shardId不能为空", "dal_shardId_empty_001", 'warn');
		}
		if(empty($table))
		{
			return Result::error("表名不能为空", 'dal_update_tables_empty_002', 'warn');
		}
		//设置修改数据标记
		ServerSelectStrategy::setModifyFlag(true);

		//取第一个表来判断数据库类别。不支持部分数据在MySQL，部分在TC的数据查询
		$dbType = $this->_dbConfig->getDbType($table);
		if($dbType == DbConfiguration::DB_MYSQL)
		{
			return $this->deleteFromMysql($shardId, $table, $conditions);
		}
		else if($dbType == DbConfiguration::DB_TC)
		{
			return $this->deleteFromTc($shardId, $table);
		}
		else
		{
			throw new \utils\DalException("请检查数据表名是否正确,执行失败!" . $table, 5001);
		}
	}

	/**
	 * 从TC中进行多数据获取
	 * @param array $classifiedIds 已经按服务器分组的Id
	 * @param array $tables 数据表名
	 * @return
	 * array(
	 * 		status=>
	 * 		data=>array{
	 * 				key1=>array(field1,field2,......),
	 * 				key2=>array(field1,field2,......),
	 * 		)
	 * )
	 */
	private function multiSelectFromTc($classifiedIds, $tables)
	{
		$arrResult = array();
		foreach($classifiedIds as $serverName => $ids)
		{
			//获取服务器信息
			$arrServerInfo = $this->_dbConfig->getServerByServerName(DbConfiguration::DB_TC, $serverName);
			//选择服务器array(addr, connectType);
			$server = ServerSelectStrategy::getServerAddr($arrServerInfo, true);

			try
			{
				//获取指定tc实例
				$tc = DaFactory::getInstance()->getTc($server['addr'], $server['connectType']);
			}
			catch(\utils\DalException $e)
			{
				//如果从服务器连接失败，则选取主服务器
				if($server['isSlaver'] && $e->getCode() == MyTc::CONNECT_FAILED_CODE)
				{
					$server = ServerSelectStrategy::getMasterAddr($arrServerInfo);
					$tc = DaFactory::getInstance()->getTc($server['addr'], $server['connectType']);
				}
				else
				{
					throw $e;
				}
			}
			//格式化TC的KEY
			foreach ($ids as $k => $v)
			{
				$ids[$k] = TcKeyFormat::formatKey($tables[0], $v);
			}
			$arrTmpRes = $tc->multiGet($ids);

			//*****************************************************
			//当读迁移目标机器数据不存在或没有数据时，从原数据服务器读
			//*****************************************************
			if($arrTmpRes === false && $arrServerInfo['move'])
			{
				$server = ServerSelectStrategy::getSrcMasterAddr($arrServerInfo);
				$tc = DaFactory::getInstance()->getTc($server['addr'], $server['connectType']);
				$arrTmpRes = $tc->multiGet($ids);
			}
			if($arrTmpRes === false)
			{
				//当数据不存在或失败时，不设置$arrResult;
				continue;
			}
			else
			{
				$fields = $this->_dbConfig->getFields($tables[0]);
				//加到返回结果集中
				foreach ($arrTmpRes as $key => $strOrg)
				{
					$arrOrg = json_decode($strOrg, true);
					if($arrOrg === false)
					{
						$arrResult[TcKeyFormat::revertKeyByTables($tables, $key)] = false;
						continue;
					}
					
					////////////////////////////////////////////////////////////
					//[单数值字段]:处理单字段非数组的情况, add by liangrn 2010-11-17
					if(!is_array($arrOrg))
					{
						$arrOrg = array($arrOrg);
					}
					////////////////////////////////////////////////////////////
					
					//处理字段数量变化的情况，也就是数据版本的简单兼容情况
					$dif = count($fields) - count($arrOrg);
					if($dif < 0)//少字段的情况
					{
						return Result::error("获取的字段数多余配置的字段数，请检查数据兼容问题。", 'dal_multiselect_field_count_less_004');
					}
					if($dif > 0)
					{
						//增加新字段
						$this->addFieldsWithDefaultValue($arrOrg, $dif);
					}

					//因为TC中不保存数组的key名称，所以需要恢复。（不知道会对性能有多大影响）
					$arrResult[TcKeyFormat::revertKeyByTables($tables, $key)] = array_combine($fields, $arrOrg);
					unset($arrOrg);//主动清空数据
				}
			}
			unset($arrTmpRes);//主动清空数组
		}
		return Result::ok($arrResult);
	}

	/**
	 * 从多个表获取记录
	 * @param array $classifiedIds
	 * @param array $tables
	 * @param array $columns
	 * @param array $conditions
	 * @param int $limit
	 * @param int $offset
	 * @param array $orderby
	 * @return
	 * array(
	 * 		status=>
	 * 		data=>array{
	 * 				0=>array(field1,field2,......),
	 * 				1=>array(field1,field2,......),
	 * 		)
	 * )
	 */
	private function multiSelectFromMysql($classifiedIds, $tables, $columns, $conditions, $limit, $offset, $orderby)
	{
		$arrResult = array();
		foreach($classifiedIds as $serverName => $ids)
		{
			//获取服务器信息
			$arrServerInfo = $this->_dbConfig->getServerByServerName(DbConfiguration::DB_MYSQL, $serverName);
			//选择服务器array(addr, connectType);
			$server = ServerSelectStrategy::getServerAddr($arrServerInfo, true);
			$sql = MysqlCommandParser::getSelectCommand($tables, $columns, $conditions, $limit, $offset, $orderby);
			if(empty($sql))
			{
				return Result::error("参数不正确，生成执行命令错误", 'dal_multiselect_command_error_003');
			}
			//获取指定mysql实例
			$mysql = DaFactory::getInstance()->getMysql($server['addr']);
			$arrTmpRes = array();
			try
			{
				$arrTmpRes = $mysql->select($sql);
			}
			catch(\utils\DalException $e)
			{
				//如果从服务器访问失败，则选取主服务器
				if($server['isSlaver'] && $e->getCode() == Mysql::PROXY_ACCESS_FAILED_CODE)
				{
					$server = ServerSelectStrategy::getMasterAddr($arrServerInfo);
					$mysql = DaFactory::getInstance()->getMysql($server['addr']);
					$arrTmpRes = $mysql->select($sql);
				}
				else
				{
					throw $e;
				}
			}
			if($arrTmpRes['status'] == 'error')
			{
				return $arrTmpRes;
			}

			//*****************************************************
			//当读迁移目标机器数据不存在或没有数据时，从原数据服务器读
			//*****************************************************
			if(empty($arrTmpRes['data']) && $arrServerInfo['move'])
			{
				$server = ServerSelectStrategy::getSrcMasterAddr($arrServerInfo);
				$mysql = DaFactory::getInstance()->getMysql($server['addr']);
				$arrTmpRes = $mysql->select($sql);
				if($arrTmpRes['status'] == 'error')
				{
					return $arrTmpRes;
				}
			}

			//加到返回结果集中
			foreach ($arrTmpRes['data'] as $item)
			{
				$arrResult[] = $item;
			}
			unset($arrTmpRes);//主动清空数组
		}
		return Result::ok($arrResult);
	}

	/**
	 * 从MySQL查询记录
	 * @param string/int $shardId
	 * @param string $table
	 * @param array $columns
	 * @param array $conditions
	 * @param int $limit
	 * @param int $offset
	 * @param array $orderby
	 * @return
	 * array(
	 * 	 'status'=>,
	 *   'data'  => array(
	 *   				0=>记录1，
	 *   				1=>记录2，
	 *                  ......
	 *              )
	 *   )
	 */
	private function selectFromMysql($shardId, $table, $columns, $conditions, $limit, $offset, $orderby)
	{
		$arrServerInfo = $this->_dbConfig->getServerById(DbConfiguration::DB_MYSQL, $shardId);
		//选择服务器
		$server = ServerSelectStrategy::getServerAddr($arrServerInfo, true);
		$sql = MysqlCommandParser::getSelectCommand(array($table), $columns, $conditions, $limit, $offset, $orderby);
		if(empty($sql))
		{
			return Result::error("参数不正确，生成执行命令错误", 'dal_select_command_error_003');
		}
		//获取指定mysql实例
		$mysql = DaFactory::getInstance()->getMysql($server['addr']);
		$arrResult = array();
		try
		{
			$arrResult = $mysql->select($sql);
		}
		catch(\utils\DalException $e)
		{
			//如果从服务器访问失败，则选取主服务器
			if($server['isSlaver'] && $e->getCode() == Mysql::PROXY_ACCESS_FAILED_CODE)
			{
				$server = ServerSelectStrategy::getMasterAddr($arrServerInfo);
				$mysql = DaFactory::getInstance()->getMysql($server['addr']);
				$arrResult = $mysql->select($sql);
			}
			else
			{
				throw $e;
			}
		}
		if($arrResult['status'] == 'error')
		{
			return $arrResult;
		}

		//*****************************************************
		//当读迁移目标机器数据不存在或没有数据时，从原数据服务器读
		//*****************************************************
		if(empty($arrResult['data']) && $arrServerInfo['move'])
		{
			$server = ServerSelectStrategy::getSrcMasterAddr($arrServerInfo);
			$mysql = DaFactory::getInstance()->getMysql($server['addr']);
			$arrResult = $mysql->select($sql);
		}

		return $arrResult;
	}

	/**
	 * 从TC获取单条数据
	 * @param string/int $shardId
	 * @param string $table
	 * @return
	 * array(
	 * 	 'status'=>,
	 *   'data'  => array(
	 *   				0=>数组
	 *              )
	 *   )
	 *
	 */
	private function selectFromTc($shardId, $table)
	{
		$arrServerInfo = $this->_dbConfig->getServerById(DbConfiguration::DB_TC, $shardId);
		//选择服务器
		$server = ServerSelectStrategy::getServerAddr($arrServerInfo, true);

		try
		{
			//获取指定tc
			$tc = DaFactory::getInstance()->getTc($server['addr'], $server['connectType']);
		}
		catch(\utils\DalException $e)
		{
			//如果从服务器连接失败，则选取主服务器
			if($server['isSlaver'] && $e->getCode() == MyTc::CONNECT_FAILED_CODE)
			{
				$server = ServerSelectStrategy::getMasterAddr($arrServerInfo);
				$tc = DaFactory::getInstance()->getTc($server['addr'], $server['connectType']);
			}
			else
			{
				throw $e;
			}
		}
		//格式化TC的KEY
		$key = TcKeyFormat::formatKey($table, $shardId);
		$strOrg = $tc->get($key);
		$arrResult = array();
		//*****************************************************
		//当读迁移目标机器数据不存在或没有数据时，从原数据服务器读
		//*****************************************************
		if($strOrg === false && $arrServerInfo['move'])
		{
			$server = ServerSelectStrategy::getSrcMasterAddr($arrServerInfo);
			$tc = DaFactory::getInstance()->getTc($server['addr'], $server['connectType']);
			$strOrg = $tc->get($key);
		}
		if($strOrg !== false)
		{
			$arrOrg = json_decode($strOrg, true);
			if($arrOrg === false)
			{
				return Result::ok($arrOrg);
			}
			
			////////////////////////////////////////////////////////////
			//[单数值字段]:处理单字段非数组的情况, add by liangrn 2010-11-17
			if(!is_array($arrOrg))
			{
				$arrOrg = array($arrOrg);
			}
			////////////////////////////////////////////////////////////
			
			$fields = $this->_dbConfig->getFields($table);
			$dif = count($fields) - count($arrOrg);
			if($dif < 0)//少字段的情况
			{
				return Result::error("获取的字段数多于配置的字段数，请检查数据兼容问题。", 'dal_select_field_count_less_004');
			}
			//增加新字段
			$this->addFieldsWithDefaultValue($arrOrg, $dif);

			//因为TC中不保存数组的key名称，所以需要恢复。（不知道会对性能有多大影响）
			$arrResult[] = array_combine($fields, $arrOrg);
			unset($arrOrg);//主动清空数据
		}
		return Result::ok($arrResult);
	}

	private function insertToMysql($shardId, $table, $col_values)
	{
		//获取服务器信息
		$arrServerInfo = $this->_dbConfig->getServerById(DbConfiguration::DB_MYSQL, $shardId);
		//选择服务器。如果是迁移的情况，获取的服务器是目标服务器地址
		$server = ServerSelectStrategy::getServerAddr($arrServerInfo, false);
		$sql = MysqlCommandParser::getInsertCommand($table, $col_values);
		if(empty($sql))
		{
			return Result::error("参数不正确，生成执行命令错误", 'dal_insert_command_error_003');
		}
		//获取指定mysql实例
		$mysql = DaFactory::getInstance()->getMysql($server['addr']);
		$res = $mysql->insert($sql);
		if(Result::check($res))
		{//返回成功标记
			unset($res);
			return Result::ok(true);
		}
		return $res;
	}

	private function insertToTc($shardId, $table, $col_values)
	{
		//获取服务器信息
		$arrServerInfo = $this->_dbConfig->getServerById(DbConfiguration::DB_TC, $shardId);
		//选择服务器。如果是迁移的情况，获取的服务器是目标服务器地址
		$server = ServerSelectStrategy::getServerAddr($arrServerInfo, false);
		$fields = $this->_dbConfig->getFields($table);
		//字段数必须相等
		if(count($fields) != count($col_values))
		{
			return Result::error("Tc添加数据失败, 字段数不相等。", 'dal_insert_tc_failed_004');
		}
		$key = TcKeyFormat::formatKey($table, $shardId);
		//按字段顺序重新排序,且去掉键名————为了减少数据量
		$values = array();
		foreach($fields as $field)
		{
			//判断字段是否存在
			if(!array_key_exists($field, $col_values))
			{
				return Result::error("Tc添加数据{$key}失败, 没有设置字段：{$field}。col_values:" . json_encode($col_values), 'dal_insert_tc_failed_005');
			}
			$values[] = $col_values[$field];
		}
		unset($col_values);

		//获取指定tc
		$tc = DaFactory::getInstance()->getTc($server['addr'], $server['connectType']);

		////////////////////////////////////////////////////////////
		//[单数值字段]:如果只有一个字段，则保存成非数组形式,
		//主要为了满足数值型的单字段累加问题（使用increment)。
		//add by liangrn 2010-11-17
		if(count($values) == 1 && !is_array($values[0]))
		{
			$values = $values[0];			
		}
		////////////////////////////////////////////////////////////
		
		$flag = $tc->add($key, json_encode($values));
		unset($values);
		if($flag)
		{
			return Result::ok($flag);
		}
		else
		{
			return Result::error("Tc添加数据失败({$tc->getResultCode()}), {$tc->getResultMessage()}. [server]{$server['addr']}[key]{$key}", 'dal_insert_tc_failed_006');
		}
	}

	private function updateToMysql($shardId, $table, $col_values, $conditions, $hasAccumulated)
	{
		//获取服务器信息
		$arrServerInfo = $this->_dbConfig->getServerById(DbConfiguration::DB_MYSQL, $shardId);
		//选择服务器，如果是迁移的情况获取的服务器是目标服务器地址
		$server = ServerSelectStrategy::getServerAddr($arrServerInfo, false);
		$sql = MysqlCommandParser::getUpdateCommand($table, $col_values, $conditions, $hasAccumulated);
		if(empty($sql))
		{
			return Result::error("参数不正确，生成执行命令错误", 'dal_select_command_error_003');
		}
		//获取指定mysql
		$mysql = DaFactory::getInstance()->getMysql($server['addr']);
		$arrTmpRes = $mysql->update($sql);
		if($arrTmpRes['status'] == 'error')
		{
			return $arrTmpRes;
		}
		//*****************************************************
		//如果目标机器数据没有更新到数据时，从更新源数据服务器
		//*****************************************************
		if(intval($arrTmpRes['data']) == 0 && $arrServerInfo['move'])
		{
			$server = ServerSelectStrategy::getSrcMasterAddr($arrServerInfo);
			$mysql = DaFactory::getInstance()->getMysql($server['addr']);
			$arrTmpRes = $mysql->update($sql);
		}
		if(Result::check($arrTmpRes))
		{//返回成功标记
			unset($arrTmpRes);
			return Result::ok(true);
		}
		return $arrTmpRes;
	}

	private function updateToTc($shardId, $table, $col_values, $hasAccumulated)
	{
		//获取服务器信息
		$arrServerInfo = $this->_dbConfig->getServerById(DbConfiguration::DB_TC, $shardId);
		//选择服务器，如果是迁移的情况获取的服务器是目标服务器地址
		$server = ServerSelectStrategy::getServerAddr($arrServerInfo, false);
		$fields = $this->_dbConfig->getFields($table);
		$key = TcKeyFormat::formatKey($table, $shardId);
		//获取指定tc
		$tc = DaFactory::getInstance()->getTc($server['addr'], $server['connectType']);

		$dif = count($fields) - count($col_values);
		$flag = false;
		$values = array();
		
		////////////////////////////////////////////////////////////
		//[单数值字段]:处理单字段的更新, 保存成非数组形式。
		//前提是该元素不是数组。add by liangrn 2011-01-18
		$first = array();
		//获取第一个元素
		foreach($col_values as $v)
		{
			$first = $v;
			break;
		}
		if(count($fields) == 1 && !is_array($first))
		{
			if(count($col_values) != 1)
			{
				return Result::error("Tc获取数据失败, 更新的字段数超过表配置的字段数。", "dal_update_tc_failed_003", 'warn');
			}
			$values = $first;
			if($hasAccumulated)
			{
				$flag = $tc->increment($key, $values);
			}
			else 
			{
				$flag = $tc->replace($key, json_encode($values));
			}
		}
		///////////////////////////////////////////////////////////
		else 
		{
			if(!$hasAccumulated && $dif == 0)//更新全部字段信息,且不存在累加值的情况
			{
				//按字段顺序重新排序
				foreach($fields as $field)
				{
					//判断字段是否存在
					if(!array_key_exists($field, $col_values))
					{
						return Result::error("Tc更新数据{$key}失败:, 没有设置字段：{$field}。col_values:" . json_encode($col_values), 'dal_update_tc_failed_005');
					}
					//去除数组的key名称
					$values[] = $col_values[$field];
				}
				unset($col_values);
				$flag = $tc->replace($key, json_encode($values));
			}
			else//部分字段更新的情况
			{
				//获取原来的值
				$value = $tc->get($key);
				if($value === false)
				{//没有指定数据
					return Result::ok($value);//return Result::error("Tc获取数据失败", 'dal_update_tc_get_failed_004');
				}
				
				$arrOrg = json_decode($value, true);
				if(!is_array($arrOrg) )
				{
					$arrOrg = array($arrOrg);
				}
				
				$dif = count($fields) - count($arrOrg);
				if($dif < 0)//少字段的情况
				{
					return Result::error("获取的字段数多于 配置的字段数，请检查数据兼容问题。", 'dal_update_field_count_less_004');
				}
				//增加新字段
				$this->addFieldsWithDefaultValue($arrOrg, $dif);
	
				//恢复键名(key)
				$values = array_combine($fields, $arrOrg);
				unset($arrOrg);//主动清空数据
	
				//更新指定键的数据
				foreach($col_values as $k => $item)
				{
					//只有设置了$hasAccumulated为true时才检查
					if($hasAccumulated && strlen($k) > 1 && $k[strlen($k) - 1] == '+')
					{//累加的情况
						$k = substr($k, 0, strlen($k) - 1);//去除+
						if(array_key_exists($k, $values))//只更新存在的建
						{
							$values[$k] += intval($item);
						}
					}
					else
					{
						if(array_key_exists($k, $values))//只更新存在的建
						{
							$values[$k] = $item;
						}
					}
				}
				unset($col_values);
				//去除数组的key名称
				$values = array_combine(array_flip($fields), $values);
				$flag = $tc->replace($key, json_encode($values));
			}
		}

		//*****************************************************
		//如果目标机器数据没有更新到数据时，更新源数据服务器
		//*****************************************************
		if($flag === false && $arrServerInfo['move'])
		{
			$server = ServerSelectStrategy::getSrcMasterAddr($arrServerInfo);
			$tc = DaFactory::getInstance()->getTc($server['addr'], $server['connectType']);
			////////////////////////////////////////////////////////////
			//[单数值字段]:处理数值单字段的累加更新, add by liangrn 2010-11-17
			if($hasAccumulated && count($fields) == 1)
			{
				$flag = $tc->increment($key, $values);
			}///////////////////////////////////////////////////////////
			else 
			{
				$flag = $tc->replace($key, json_encode($values));
			}
		}

		unset($values);
		if($flag)
		{
			return Result::ok($flag);
		}
		else
		{
			return Result::error("Tc更新数据失败({$tc->getResultCode()}), {$tc->getResultMessage()}. [server]{$server['addr']}[key]{$key}", 'dal_update_tc_failed_006');
		}
	}


	private function deleteFromMysql($shardId, $table, $conditions)
	{
		//获取服务器信息
		$arrServerInfo = $this->_dbConfig->getServerById(DbConfiguration::DB_MYSQL, $shardId);
		//选择服务器，如果是迁移的情况获取的服务器是目标服务器地址
		$server = ServerSelectStrategy::getServerAddr($arrServerInfo, false);
		$sql = MysqlCommandParser::getDeleteCommand($table, $conditions);
		if(empty($sql))
		{
			return Result::error("参数不正确，生成执行命令错误", 'dal_select_command_error_003');
		}

		//获取指定mysql
		$mysql = DaFactory::getInstance()->getMysql($server['addr']);
		$arrTmpRes = $mysql->delete($sql);
		if($arrTmpRes['status'] == 'error')
		{
			return $arrTmpRes;
		}

		//*****************************************************
		//如果目标机器数据没有删除到数据时，删除源数据服务器的数据
		//*****************************************************
		if(intval($arrTmpRes['data']) == 0 && $arrServerInfo['move'])
		{
			$server = ServerSelectStrategy::getSrcMasterAddr($arrServerInfo);
			$mysql = DaFactory::getInstance()->getMysql($server['addr']);
			$arrTmpRes = $mysql->delete($sql);
		}
		if(Result::check($arrTmpRes))
		{//返回成功标记
			if($arrTmpRes['data'] == 0)
			{//没有数据
				return Result::ok(false);
			}
			return Result::ok(true);
		}
		return $arrTmpRes;
	}

	private function deleteFromTc($shardId, $table)
	{
		//获取服务器信息
		$arrServerInfo = $this->_dbConfig->getServerById(DbConfiguration::DB_TC, $shardId);
		//选择服务器，如果是迁移的情况获取的服务器是目标服务器地址
		$server = ServerSelectStrategy::getServerAddr($arrServerInfo, false);
		$key = TcKeyFormat::formatKey($table, $shardId);
		//获取指定tc
		$tc = DaFactory::getInstance()->getTc($server['addr'], $server['connectType']);
		$flag = $tc->delete($key);
		//*****************************************************
		//如果目标机器数据没有删除到数据时，删除源数据服务器的数据
		//*****************************************************
		if($flag === false && $arrServerInfo['move'])
		{
			$server = ServerSelectStrategy::getSrcMasterAddr($arrServerInfo);
			$tc = DaFactory::getInstance()->getTc($server['addr'], $server['connectType']);
			$flag = $tc->delete($key);
		}

		if($flag)
		{
			return Result::ok($flag);
		}
		else
		{
			return Result::error("Tc删除数据失败({$tc->getResultCode()}), {$tc->getResultMessage()}. [server]{$server['addr']}[key]{$key}", 'dal_delete_tc_failed_005', 'info');
		}
	}

	/**
	 * 使用默认值增加新字段
	 * @param array $arrOrg
	 * @param int $dif
	 */
	private function addFieldsWithDefaultValue(&$arrOrg, $dif)
	{
		if($dif > 0)//多字段的情况
		{
			//使用空串补齐字段
			for($i=0; $i<$dif; $i++)
			{
				$arrOrg[] = '';
			}
		}
	}

	public static function getInstance()
	{
		if(self::$s_dal == null)
		{
			self::$s_dal = new self();
		}
		return self::$s_dal;
	}

}
?>