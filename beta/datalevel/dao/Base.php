<?php
/**
 * 数据访问对象基础类
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
use framework\datalevel\dal\Conditions as Conditions;
use framework\datalevel\dal\Dal as Dal;
use framework\utils\Result as Result;

abstract class Base
{
	//存放列表的表名（主要是MySQL的表名）, 需要在子类设置，如coms_comment_list, moods_mood_list
	protected $_listTable       = null;
	//存放内容的表名（主要是TT的表名）, 需要在子类设置, 如coms_comment, moods_mood
	protected $_contentTable    = null;

	//需要查询的列表字段，如果为null，则默认查询全部, 如array(field1, field2, field3, ...);
	protected $_qryListFields    = null;
	//需要查询的内容字段, 如果为null, 则默认查询全部, 如array(field1, field2, field3, ...);
	protected $_qryContentFields = null;
	//列表的表名中对应内容的键值名称，如moodId或commentId
	protected $_contentIdField   = null;
	//列表的表名中对应分库用户Id的键值名称，如userId或itemAuthorId
	protected $_userIdField      = null;
	
	protected $_dal              = null;
	
	protected function __construct()
	{
		$this->_dal = Dal::getInstance();
	}
	/**
	 * 删除指定id数据
	 * @param userId
	 * @param id
	 */
	public function delete($userId, $id)
	{
		return $this->deleteOneRecordById($this->_listTable, $this->_contentTable, $userId, $id);
	}

	/**
	 * 增加
	 * @param userId
	 * @param data
	 */
	/*public function add($userId, $data)
	{
		//暂无必要提供缺省实现
	}*/

	/**
	 *
	 * @param userId
	 * @param id
	 * @param data
	 */
	/*public function update($userId, $id, $data)
	{
		//暂无必要提供缺省实现
	}*/

	/**
	 *
	 * @param userId
	 * @param id
	 */
	public function getById($userId, $id)
	{
		return $this->getOneRecordById($this->_listTable, $this->_contentTable, $userId, $id);
	}
	
	/**
	 * 根据指定Id的记录。如果只查询列表中小部分的字段，建议设置qryPartFields.
	 * @param string $listTable 列表表名，不能为空
	 * @param string $contentTable 内容表名，如果为null，则默认不查询内容信息。
	 * @param userId
	 * @param id
	 * @param array $conditions  由Conditions类组装成的条件，如果没有条件，则本函数以$userId和$id条件查询。
	 * @param array qryPartFields 指定需要查询的列表字段，如果为空，则按$this->_qryListFields查询。
	 * @return  Result格式
	 * array(
	 * 		status=>,
	 * 		data=>array(
	 * 				0=>array(field1=>, field2=>,...),
	 * 				1=>array(field1=>, field2=>,...),
	 * 			)
	 * )
	 */
	protected function getOneRecordById($listTable, $contentTable, $userId, $id, $conditions = null, $qryPartFields=null)
	{
		if(empty($listTable) || empty($userId) || empty($id))
		{
			return Result::error('参数不能为空', 'basedao_getOneRecordById_params_empty_001', 'info');
		}
		if(empty($conditions))
		{
			$conditions = array();
			Conditions::first_c($conditions, $this->_userIdField, '=', $userId);
			Conditions::and_c($conditions, $this->_contentIdField, '=', $id);
		}
		
		$arrList = null;
		if($qryPartFields === null)
		{
			$arrList = $this->_dal->select($userId, $listTable, $this->_qryListFields, $conditions);
		}
		else
		{
			$arrList = $this->_dal->select($userId, $listTable, $qryPartFields, $conditions);
		}
		if(!Result::check($arrList))
		{//有错的情况
			return $arrList;
		}
		if(empty($arrList['data']))
		{
			//没有找到指定记录。
			return Result::ok(array());
		}
		if(empty($contentTable))
		{//不需要查询内容，则返回列表的信息
			return Result::ok($arrList['data'][0]);
		}
		//查询内容信息
		$arrContents = $arrComment = $this->_dal->select($id, $contentTable);
		if(!Result::check($arrContents))
		{
			return $arrContents;
		}
		if(empty($arrContents['data']))
		{
			//没有找到指定记录。
			return Result::ok(array());
		}
		if($this->_qryContentFields === null)
		{//合并全部内容字段信息
			$arrList['data'][0] = array_merge($arrList['data'][0], $arrContents['data'][0]);
		}
		else 
		{
			foreach($this->_qryContentFields as $cField)
			{//合并指定内容字段信息
				$arrList['data'][0][$cField] = $arrContents['data'][0][$cField];
			}
		}
		unset($arrContents);
		return Result::ok($arrList['data'][0]);
	}
	
	/**
	 * 根据查询满足条件的记录。如果只查询列表中小部分的字段，建议设置qryPartFields.
	 * @param string $listTable 列表表名，不能为空
	 * @param string $contentTable 内容表名，如果为null，则默认不查询内容信息。
	 * @param array $conditions
	 * @param int $pageNo 页码
	 * @param int $pageSize 如果为0，则表示不分页
	 * @param array $orderby 排序条件，如array(field1=>'desc', field2=>'asc',...);
	 * @param array qryPartFields 指定查询列表中的字段，如果为空，则按$this->_qryListFields查询。
	 * @return  Result格式
	 * array(
	 * 		status=>,
	 * 		data=>array(
	 * 				0=>array(field1=>, field2=>,...),
	 * 				1=>array(field1=>, field2=>,...),
	 * 			)
	 * )
	 */
	protected function getByConditions($listTable, $contentTable, $userId, $conditions, $pageNo, $pageSize, $orderby=null, $qryPartFields=null)
	{
		if(empty($listTable) || empty($userId))
		{
			return Result::error('参数不能为空', 'basedao_getByConditions_params_empty_001', 'info');
		}
		$arrList = null;
		if($qryPartFields === null)
		{
			$arrList = $this->_dal->select($userId, $listTable, $this->_qryListFields, $conditions, $pageSize, ($pageNo - 1) * $pageSize, $orderby);
		}
		else
		{
			$arrList = $this->_dal->select($userId, $listTable, $qryPartFields, $conditions, $pageSize, ($pageNo - 1) * $pageSize, $orderby);
		}
		if(!Result::check($arrList))
		{
			return $arrList;
		}
		if(empty($arrList['data']))
		{
			//没有找到指定记录。
			return Result::ok(array());
		}
		
		if(empty($contentTable))
		{//不需要查询内容，则返回列表信息
			return $arrList;
		}
		$ids = array();
		foreach($arrList['data'] as $data)
		{
			$ids[] = $data[$this->_contentIdField];
		}
		//查询内容信息
		$arrContents = $this->_dal->multiSelect($ids, array($contentTable));
		if(!Result::check($arrContents))
		{
			return $arrContents;
		}
		if(empty($arrContents['data']))
		{
			//没有找到指定记录。
			return Result::ok(array());
		}

		$partLost = false;
		foreach($arrList['data'] as $k=>$data)
		{
			if(isset($arrContents['data'][$data[$this->_contentIdField]]) 
				&& is_array($arrContents['data'][$data[$this->_contentIdField]]))
			{
				if($this->_qryContentFields === null)
				{//合并全部内容字段信息
					$arrList['data'][$k] = array_merge($arrList['data'][$k], $arrContents['data'][$data[$this->_contentIdField]]);
				}
				else 
				{
					foreach($this->_qryContentFields as $cField)
					{//后面追加内容字段信息
						$arrList['data'][$k][$cField] = $arrContents['data'][$data[$this->_contentIdField]][$cField];
					}
				}
			}
			else
			{//不存在内容的数据，从列表中删除
				$partLost = true;
				unset($arrList['data'][$k]);//
			}
		}
		unset($arrContents);
		
		if($partLost)
		{	//返回的列表的数组KEY，不一定是连续数值，对于没有内容的记录，会缺少对应的KEY。
			//所以需要重新排号。
			$tmpList = array();
			foreach($arrList['data'] as $data)
			{
				$tmpList[] = $data;
			}
			unset($arrList['data']);
			$arrList['data'] = $tmpList;
		}
		
		return $arrList;
	}
	
	/**
	 * 返回符合条件的记录数。
	 * @param string $listTable 列表表名 
	 * @param int $userId 拆分库的用户Id 
	 * @param array $conditions 条件，由Conditions类组装成的数组
	 * @return Result格式
	 * array(
	 * 		status=>
	 * 		data=>数量，没有符合条件的则为0
	 * )
	 */
	protected function getCountByConditions($listTable, $userId, $conditions)
	{
		if(empty($listTable) || empty($userId))
		{
			return Result::error('参数不能为空', 'basedao_getCountByConditions_params_empty_001', 'warn');
		}
		$arrList = $this->_dal->select($userId, $listTable, array('count(1) as num'), $conditions);
		if(!Result::check($arrList))
		{
			return $arrList;
		}
		if(empty($arrList['data']))
		{
			//没有找到指定记录。
			return Result::ok(0);
		}
		//返回记录数
		return Result::ok($arrList['data'][0]['num']);
	}
	
	/**
	 * 删除指定Id的记录
	 * @param string $listTable 列表表名，不能为空
	 * @param string $contentTable 内容表名，不能为空。
	 * @param int $userId
	 * @param int $id
	 * @param array $conditions 由Conditions类组装成的条件，如果没有条件，则本函数以$userId和$id条件查询。
	 * @return Result格式
	 * array(
	 * 		status=>
	 * 		data=>是否删除成功(true/false)
	 * )
	 */
	protected function deleteOneRecordById($listTable, $contentTable, $userId, $id, $conditions = null)
	{
		if(empty($listTable) || empty($contentTable) || empty($userId) || empty($id))
		{
			return Result::error('参数不能为空', 'basedao_deleteOneRecordById_empty_001', 'info');
		}
		if(empty($conditions))
		{
			$conditions = array();
			Conditions::first_c($conditions, $this->_userIdField, '=', $userId);
			Conditions::and_c($conditions, $this->_contentIdField, '=', $id);
		}
		//先删除列表，再删除内容
		$res = $this->_dal->delete($userId, $listTable, $conditions);
		if(!Result::check($res) || !$res['data'])
		{//删除失败或没有符合条件的记录
			return $res;
		}
		//删除内容
		return $this->_dal->delete($id, $contentTable, null);
	}
	
	/**
	 * 删除符合条件的记录
	 * 
	 * @param string $listTable 列表表名，不能为空
	 * @param string $contentTable 内容表名，不能为空
	 * @param int $userId
	 * @param array $conditions 条件,由Conditions类组装成的数组
	 * @return Result格式
	 * array(
	 * 		status=>
	 * 		data=>是否删除成功(true/false)
	 * )
	 */
	protected function deleteByConditions($listTable, $contentTable, $userId, $conditions)
	{
		if(empty($listTable) || empty($contentTable) || empty($userId))
		{
			return Result::error('参数不能为空', 'basedao_deleteByConditions_params_empty_001', 'info');
		}
		$delIds = array(); //array(id1, id2, id3.....)
		$delIdsWithUser = array();// array(userId=>array(id1, id2,...), userId2=>array(id1, id2, id3...)...)
		
		//先查询需要删除的id
		$rids = $this->_dal->select($userId, $listTable, array($this->_contentIdField), $conditions);
		if(!Result::check($rids))
		{//查询失败或没有符合条件的记录
			return $rids;
		}
		
		if(empty($rids['data']))
		{
			return Result::ok(false);
		}
		//删除列表数据
		$r2 = $this->_dal->delete($userId, $listTable, $conditions);
		if(!Result::check($r2))
		{//删除失败
			return $r2;
		}
		
		foreach($rids['data'] as $id)
		{
			//逐一删除内容
			$this->_dal->delete($id[$this->_contentIdField], $contentTable, null);
		}
		return Result::ok(true);
		
	}

	/**
	 * 获取指定Id内容信息
	 * @param string $contentTable
	 * @param int $userId 用户Id(预留)
	 * @param int $id
	 * @return Result格式
	 * array(
	 * 		status=>
	 * 		data=>array(field1,field2, ...)
	 * )
	 * 
	 */
	protected function getContentsById($contentTable, $userId, $id)
	{
		if(empty($contentTable) || empty($id))
		{
			return Result::error('参数不能为空', 'basedao_getContentById_params_empty_001', 'info');
		}

		$arrContents = $this->_dal->select($id, $contentTable);
		if(!Result::check($arrContents))
		{
			return $arrContents;
		}
		if(empty($arrContents['data']))
		{
			//没有找到指定记录。
			return Result::ok(false);
		}
		//返回内容(所有字段)
		return Result::ok($arrContents['data'][0]);
	}
	
	/**
	 * 添加一条记录
	 * @param string $listTable 列表表名，不能为空
	 * @param string $contentTable 内容表名，可以为空，如果为NULL，则不插入内容。
	 * @param int $userId
	 * @param array $listData 列表字段值
	 * @param array $contentData 内容字段值，如果内容表名为空，则本内容也可以为空。
	 */
	protected function addOneRecord($listTable, $contentTable, $userId, $listData, $contentData)
	{
		if(empty($listTable) || empty($listData) || empty($userId) || (!empty($contentTable) && empty($contentData)))
		{
			return Result::error('参数不能为空', 'basedao_addOneRecord_params_empty_001', 'info');
		}

		if(!empty($contentTable))
		{
			$id = $listData[$this->_contentIdField];
			//先添加内容，
			$res = $this->_dal->insert($id, $contentTable, $contentData);
			if(!Result::check($res))
			{
				return $res;
			}
		}
		//再添加列表
		return $this->_dal->insert($userId, $listTable, $listData);
	}
	
	/**
	 * 更新一条记录
	 * @param string $listTable 列表表名，不能为空
	 * @param string $contentTable 内容表名，可以为空，如果为NULL，则不更新内容。
	 * @param int $userId
	 * @param int $id
	 * @param array $listData 列表字段值
	 * @param array $contentData 内容字段值，如果内容表名为空，则本内容也可以为空。
	 * @param array $conditions 由Conditions类组装成的条件. 如果没有指定条件，则本函数以$userId和$id条件查询。 
	 */
	protected function updateOneRecord($listTable, $contentTable, $userId, $id, $listData, $contentData, $conditions = null)
	{
		if(empty($listTable) || empty($listData) || empty($id) || (!empty($contentTable) && empty($contentData)))
		{
			return Result::error('参数不能为空', 'basedao_updateOneRecord_params_empty_001', 'info');
		}

		if(empty($conditions))
		{
			$conditions = array();
			Conditions::first_c($conditions, $this->_userIdField, '=', $userId);
			Conditions::and_c($conditions, $this->_contentIdField, '=', $id);
		}
		
		if(!empty($contentTable))
		{
			//先更新内容，
			$res = $this->_dal->update($id, $contentTable, $contentData, null);
			if(!Result::check($res))
			{
				return $res;
			}
		}
		//再更新列表
		return $this->_dal->update($userId, $listTable, $listData, $conditions);
	}
	/**
	 * 
	 *   根据页码和每页大小得到偏移起始值offset和偏移大小limit
	 * @param int $page 页码
	 * @param int $pageSize 每页大小
	 * @return 成功返回array($offset[0, ..], $limit[1, ..])， 否则返回默认值array(0, 10)
	 */
	protected function getOffsetLimit($page, $pageSize)
	{
	    if ($page <= 0 || $pageSize <= 0)
	    {
	        return array(0, 10);
	    }
	    $offset = $pageSize * ($page - 1);
	    return array($offset, $pageSize);
	}
}
