<?php
declare(encoding='UTF-8');
namespace services\modules\test;
use \framework\datalevel\base\Factory as DatalevelFactory;
use \framework\datalevel\dao\Factory as DaoFactory;
use \framework\utils\IdGenerator as IdGenerator;
class Test implements \services\interfaces\ITest
{
    public function sayHello()
    {
        // 调用Memcache
        $mc  = DatalevelFactory::getInstance()->getMc();
        $mc->set('hello', 'v');
        $res = $mc->get('hello');
        $res = $mc->delete('hello');
        // 调用DAO
        $dao = DaoFactory::getInstance()->getDao('\datalevel\dao\TestDao');
        
        // 生成唯一id
        $itemId   = IdGenerator::getBigIntId();
        $uid      = 1000;
        
        // 插入数据
        $res      = $dao->add($uid, $itemId, 'hello man 1' . $itemId);
        //var_dump($res);
        
        // 
        // 获取klist类数据
        $page     = 1;
        $pageSize = 100;
        $res = $dao->getListById($uid, $page, $pageSize);
       // var_dump($res);
        
        // 获取kvalue类数据
        // get hole data by id
        $res = $dao->getById($uid, $itemId);
        //var_dump($res);
        
        // get content by id
        $res = $dao->getContentById($uid, $itemId);
        //var_dump($res);
        
        // 删除数据
        $res = $dao->del($uid, $itemId);
        //var_dump($res);
        
        // 调用消息队列
        
    }
}