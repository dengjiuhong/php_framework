<?php

namespace datalevel\dao;
use \framework\datalevel\dao\Base as Base;
use \framework\datalevel\dal\Conditions as Conditions;
use \framework\utils\Result as Result;
class TestDao extends Base
{
    /**
     * 
     * 初始化dao
     */
    public function __construct()
    {
        parent::__construct();
                //说说列表的表名（父类的变量）
        $this->_listTable = 'test_list';
        
        //存放内容的表名（父类的变量）
        $this->_contentTable = 'test_content';
        //设置需要查询的列表字段（父类的变量），这里设置为null, 查询所有字段
        $this->_qryListFields = null;//userId,commentId,commentType,itemType,itemId,itemAuthorId,parentId,parentAuthorId,state,createTime,rootId,rootCreateTime,rootUpdateTime
        //设置需要查询的内容字段（父类的变量）
        $this->_qryContentFields = array('feild1','feild2');
        //设置内容Id字段名（父类的变量）
        $this->_contentIdField = 'itemId';
        //设置分库用户Id字段名（父类的变量）
        $this->_userIdField = 'userId';
        
    }
    public function test()
    {
        // $res = $this->_dal->select($moodId, $this->_contentTable);
        //$upRes = $this->_dal->update($moodId, $this->_contentTable, $res['data'][0], null);
        // $this->_dal->delete($moodId, $this->_userIdTable, null);
        // 		$contents = $this->_dal->multiSelect($moodIds, array($this->_contentTable), null);
		
        
    }
    public function del($userId, $itemId)
    {
        $res = $this->delete($userId, $itemId);
        return $res;
    }
    public function add($userId, $itemId, $content)
    {
        // 列表类数据
        $arrMoodList['itemId'] = $itemId;
        $arrMoodList['userId'] = $userId;
        $arrMoodList['createTime'] = time();
        
        // content 类数据
        $newMood['feild1'] = $content;
        $newMood['feild2'] = 'test1';
        // 新增一条数据
        $res = $this->addOneRecord($this->_listTable, $this->_contentTable, $userId, $arrMoodList, $newMood);
        if(!Result::check($res))
        {
            return $res;
        }
        return $res;
    }
    public function getById($userId, $itemId)
    {
                $arr = parent::getById($userId, $itemId);       
        return $arr;
    }
    public function getListById($userId, $pageNo, $pageSize)
    {
        // 构建条件
        $conditions = array();
        Conditions::first_c($conditions, 'userId' , '=', $userId);
        // 查询
        return $this->getByConditions(
            $this->_listTable, 
            $this->_contentTable, 
            $userId, 
            $conditions, 
            $pageNo,
            $pageSize, 
            array('createTime'=>'desc')
        );
    }
    /**
     * 获取说说内容
     * @param int userId
     * @param bigint moodId
     * @return \utils\Result格式
     * array(
     *         status=>
     *         data=>说说内容(content),如果没有找到则为false
     * )
     */
    public function getContentById($userId, $itemId)
    {
        if(empty($itemId))
        {
            return Result::error('参数不能为空', 'test_params_empty_001', 'info');
        }
        $arrMood = $this->getContentsById($this->_contentTable, $userId, $itemId);
        if(!Result::check($arrMood))
        {
            return $arrMood;
        }
        if(empty($arrMood['data']))
        {
            //没有找到指定记录。
            return Result::ok(false);
        }
        //返回内容
        return Result::ok($arrMood['data']['feild1']);
    }
    protected $_contentIdField = '';
    protected  $_listTable     = '';
    protected  $_contentTable  = '';
}
