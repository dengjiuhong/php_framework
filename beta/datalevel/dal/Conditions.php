<?php
/**
 *    条件组装
 * 注意：暂时不支持嵌套条件或二维条件，以后在增加方法考虑
 * 比如不支持：(a>b and b>c) or c = d
 * 	            支持：a > b and b > c or c = d
 *
 * 条件格式,如:
 * array(
 *  	'first' => array('feedId', 'in' , array(123,234,4564)),
 * 		'and'   => array('feedType', '=', 1),
 *  )
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
class Conditions
{
	static private $s_ops = array(
							'<'=>true,
							'<='=>true,
							'='=>true,
							'>'=>true,
							'>='=>true,
							'<>'=>true,
							'in'=>true,
							);
							
	const NEST_OP = 'nest';
	
	/**
	 * 设置第一个条件
	 * @param array $cond 条件 （引用变量）
	 * @param string $var 字段名
	 * @param string $op 比较操作，支持'<', '<=', '=', '>=', '>', '<>', 'in'
	 * @param string/array $value 匹配值，如果是in操作，则为数组，否则都为string
	 * @return true/false
	 */
	static public function first_c(&$cond, $var, $op, $value)
	{
		if(!self::checkParams($cond, $var, $op, $value))
		{
			return false;
		}
		//设置第一个条件
		$cond[] = array(' ', $var, $op, $value);
	}

	/**
	 * 设置'and'逻辑条件
	 * @param array $cond 条件 （引用变量）
	 * @param string $var 字段名
	 * @param string $op 比较操作，支持'<', '<=', '=', '>=', '>', 'in'
	 * @param string/array $value 匹配值，如果是in操作，则为数组，否则都为string/int等基础类型
	 * @return true/false
	 */
	static public function and_c(&$cond, $var, $op, $value)
	{
		if(!self::checkParams($cond, $var, $op, $value))
		{
			return false;
		}
		//设置and条件
		$cond[] = array('and', $var, $op, $value);
	}

	/**
	 * 设置'or'逻辑条件
	 * @param array $cond 条件 （引用变量）
	 * @param string $var 字段名
	 * @param string $op 比较操作，支持'<', '<=', '=', '>=', '>', 'in'
	 * @param string/array $value 匹配值，如果是in操作，则为数组，否则都为string
	 * @return true/false
	 */
	static public function or_c(&$cond, $var, $op, $value)
	{
		if(!self::checkParams($cond, $var, $op, $value))
		{
			return false;
		}
		//设置or条件
		$cond[] = array('or', $var, $op, $value);
	}
	
	static public function nest_or(&$cond, $nestCond)
	{
		if(empty($nestCond))
		{
			return false;
		}
		$cond[] = array('or', '', self::NEST_OP, $nestCond);
	}
	
	static public function nest_and(&$cond, $nestCond)
	{
		if(empty($nestCond))
		{
			return false;
		}
		$cond[] = array('and', '', self::NEST_OP, $nestCond);
	}

	static private function checkParams(&$cond, $var, $op, $value)
	{
		if(!is_array($cond))
		{
			return false;
		}
		if(empty($var) || empty($op))
		{
			return false;
		}
		if(!self::isValidOp($op) || !self::isValidValue($op, $value))
		{
			return false;
		}
		return true;
	}
	/**
	 * 检查是否支持指定的操作
	 * @param string $op
	 */
	static private function isValidOp($op)
	{
		if(isset(self::$s_ops[$op]))
		{
			return self::$s_ops[$op];
		}
		return false;
	}

	/**
	 * 检查是否支持指定的操作
	 * @param string $op
	 * @param $value
	 */
	static private function isValidValue($op, $value)
	{
		if(!isset(self::$s_ops[$op]))
		{
			return false;
		}
		if($op == 'in' && !is_array($value))
		{
			return false;
		}
		return true;
	}
}
