<?php
/**
 * mysql执行语句解析器
 * 对insert/update/where中的字符串信息使用了addslashes进行转义，防止MySQL注入
 * 但目前未实现使用stripslashes对查询结果进行还原。
 * 另由于目前使用MySQL方案是不保存内容数据的，为了性能暂不实现， 以后再考虑
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
class MysqlCommandParser
{

	/**
	 * MySQL查阅操作的参数解析
	 * @param tableName
	 * @param columns 需要查询的字段名称
	 * @param conditions
	 * @param limit
	 * @param offset
	 * @param orderBy
	 * @return 返回MYSQL查询命令
	 */
	public static function getSelectCommand($tables, $columns, $conditions, $limit, $offset, $orderby)
	{
		$sql = 'select ';
		if(empty($columns))
		{
			$sql .= ' *  ';
		}
		else
		{
			$sql .= implode(',', $columns);
		}
		if(empty($tables))
		{
			return '';
		}
		$sql .= ' from ';
		$sql .= implode(',', $tables);
		$sql .= self::getWhere($conditions);
		$sql .= ' ';
		if(!empty($orderby))
		{
			$sql .= ' order by ';
			$strOrderby = '';
			foreach($orderby as $key => $sort)
			{
				if($strOrderby !== '')
				{
					$strOrderby .= ' , ';
				}
				$strOrderby .= $key . ' ' . $sort . ' ';
			}
			$sql .= $strOrderby;
		}
		if($limit == 0)
		{
			return $sql;
		}
		return $sql .= ' limit ' . $limit . ' offset ' . $offset;
	}

	/**
	 *
	 * @param tables
	 * @param col_values
	 */
	public static function getInsertCommand($table, $col_values)
	{
		if(empty($table) || empty($col_values))
		{
			return '';
		}
		$sql = 'insert into ' . $table . ' ';
		$strColumns = '';
		$strValues = '';
		foreach($col_values as $column => $value)
		{
			if($strColumns !== '')
			{
				$strColumns .= ' , ';
			}
			$strColumns .= $column;

			if($strValues !== '')
			{
				$strValues .= ' , ';
			}
			if(is_string($value))
			{
				$strValues .= '"' . addslashes($value) . '"';
			}
			else
			{
				$strValues .= $value;
			}
		}
		$sql .= ' (' . $strColumns . ') ';
		$sql .= ' values ';
		$sql .= ' (' . $strValues . ') ';
		return $sql;
		//$sql .= self::getWhere($conditions);

	}

	/**
	 *
	 * @param tables
	 * @param conditions
	 */
	public static function getDeleteCommand($table, $conditions)
	{
		if(empty($table) || empty($conditions))
		{
			return '';
		}
		$sql = 'delete from ' . $table . ' ';
		$sql .= self::getWhere($conditions);
		return $sql;
	}

	/**
	 *
	 * @param tables
	 * @param col_values
	 * @param conditions
	 */
	public static function getUpdateCommand($table, $col_values, $conditions, $hasAccumulated)
	{
		if(empty($table) || empty($col_values))
		{
			return '';
		}
		$sql = 'update ' . $table . ' set ';
		$strColumnValues = '';
		foreach($col_values as $column => $value)
		{
			if($strColumnValues !== '')
			{
				$strColumnValues .= ' , ';
			}
			
			if($hasAccumulated && strlen($column) > 1 && $column[strlen($column) - 1] == '+')
			{
				//更新值的情况
				$column = substr($column, 0, strlen($column) - 1);
				$strColumnValues .= $column . '=';
				$strColumnValues .= $column . '+' . $value;
			}
			else
			{
				$strColumnValues .= $column . '=';
				if(is_string($value))
				{
					$strColumnValues .= '"' . addslashes($value) . '"';
				}
				else
				{
					$strColumnValues .= $value;
				}
			}
		}
		$sql .= $strColumnValues;
		$sql .= self::getWhere($conditions);
		return $sql;
	}

	private static function getWhere($conditions)
	{
		$condStr = self::getConditionStr($conditions);
		if(!empty($condStr))
		{
			return ' where ' . $condStr;
		}
		return ' ';
	}
	
	private static function getConditionStr($conditions)
	{
		$sql = '';
		if(!empty($conditions))
		{
			foreach($conditions as $key => $condition)
			{
				if($condition[2] == 'nest')//嵌套的情况
				{
					$nestStr = self::getConditionStr($condition[3]);	
					if(!empty($nestStr))
					{
						$sql .= $condition[0]. ' (' .  $nestStr . ')';
					}
				}
				else if($condition[2] == 'in')//in的情况
				{
					if(empty($condition[3]))
					{
						continue;
					}
					///////////////////////////////////////////////////////////////////
					//注意：这里没有做sting的判断，假设了$condition[3]中的元素都为数值类型
					if(count($condition[3]) == 1)//只有一个元素的情况
					{
						$sql .= $condition[0]. ' '  . $condition[1] . '=' . $condition[3][0] . ' ';
					}
					else
					{
						$sql .= $condition[0]. ' '  . $condition[1] . ' in(' . implode(',', $condition[3]) . ') ';
					}
					
				}
				else//其他操作
				{
					if(is_string($condition[3]))
					{
						$sql .= $condition[0]. ' ' . $condition[1] . $condition[2] . "'" . addslashes($condition[3]) . "' ";
					}
					else
					{
						$sql .= ' ' . implode(' ', $condition) . ' ';
					}
				}
			}
			return $sql;
		}
		return '';
	}
}
?>