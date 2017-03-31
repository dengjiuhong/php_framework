<?php
/**
 *    key格式化
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
class TcKeyFormat
{

	/**
	 * 格式化TC的key
	 * @param tableName
	 * @param id
	 */
	public static function formatKey($tableName, $id)
	{
		//为了兼容旧数据，需要补全3位，因为需要根据3为数值进行数据分布计算和方便TC的list查询KEY
		if(strlen(strval($id)) < 3)
		{
			$id = substr("000" . $id, -3);
		}
		return $tableName . '_' . $id;
	}

	/**
	 * 还原原来的KEY（删除前缀）
	 * @param string $tableName
	 * @param string $key
	 */
	public static function revertKey($tableName, $key)
	{
		//删除前缀
		$key = str_replace($tableName . '_', '', $key);
		//如果长度小于等于3，则表明是旧数据，需要进行兼容
		if(strlen($key) <= 3)
		{
			$i=0;
			//还原原来的KEY,去掉formatKey补位的0
			for($i=0; $i<3;$i++)
			{
				if($key[$i] == '0')
				{
					continue;
				}
				break;
			}
			if($i >= 3)
			{
				return '';
			}
			$key = substr($key, $i);
		}
		return $key;
	}

	/**
	 * 根据多个Table的名称还原原来键值
	 * @param array $tableNames
	 * @param string $key
	 */
	public static function revertKeyByTables($tableNames, $key)
	{
		$count = 0;
		foreach($tableNames as $tableName)
		{
			/*//使用表名进行尝试替换前缀。
			$resKey = str_replace($tableName . '_', '', $key, $count);
			//只要替换成功，则返回
			if($count > 0)
			{
				return $resKey;
			}*/
			$resKey = self::revertKey($tableName, $key);
			if($resKey != $key)
			{//只要替换成功，则返回
				return $resKey;
			}
		}
		//如果没有可替换的前缀，则原样返回。
		return $key;
	}
}
