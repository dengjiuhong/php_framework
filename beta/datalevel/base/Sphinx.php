<?php
declare(encoding='UTF-8');
namespace framework\datalevel\base;
use framework\utils\Result as Result;
use framework\base\Logger as Logger;
use framework\base\Config as Config;
/**
 *   Sphinx全文查询器
 * 提供“精确查找”、“基于分词的任意匹配查找“接口，默认根据相关度排序；
 * 
 * @category   base
 * @package    datalevel
 * @author panwy <panwy@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 * @example
 *  //初始化
 *  $conf = array(
 *       'host' => '127.0.0.1',
 *       'port' => '9312',
 *       'indexName' => 'scholl'
 *   );
 *   $searcher = new Sphinx($conf);
 *   $searcher
 *   //精确查找
 *   $searcher->search_exactly('上海');
 *   //“基于分词的任意匹配查找“
 *   $searcher->search_any('上海');
 *   //使用完毕后需关闭长链接
 *   $searcher->close();
 *   //分页
 *   $searcher->search_exactly('上海', 0, 10);
 *   //设置过滤
 *   //设置分组
 *   //设置排序
 */
class Sphinx
{
    //当前使用中的sphinx查询客户端
    private $_client;
    //已使用过的sphinx查询客户端
    private $_clients = array();
    
    // 当前的group名
    private $_currGroup = 'default';

    //sphinx默认的max_matches为1000，因为该值不必设为很大，每次最多查1000已经足够用户使用了
    private $_limitMax = 1000;
    
    //最大的查询时间，单位为毫秒
    private $_maxQueryTime = 3000;
    //最大的连接超时时间，单位为秒
    private $_connectTimeout = 3;

    public static $SORT_RELEVANCE = 1;
    public static $SORT_ATTR_DESC = 2;
    public static $SORT_ATTR_ASC = 3;
    public static $SORT_TIME_SEGMENTS = 4;
    public static $SORT_EXTENDED = 5;
    public static $SORT_EXPR = 6;
    /**
     *
     *   查询器构造函数
     * @param array $group 组名，根据Sphinx_Servers.inc.php获取组服务器地址
     */
    public function __construct($group = 'default')
    {
        //设置服务器
        $this->setCurrServer($group);
    }
    /**
     *
     *   基于分词的任意匹配查询
     * @param string $word 待查询词
     * @param string $index 索引名
     * @param int &$total 引用值，返回记录总数
     * @param int $offset 偏移
     * @param int $limit 需返回的条目数
     * @return \utils\Result格式
     * array(
     *      status=>
     *      data=> 无结果返回空array(), 否则返回array('文档ID1'=>array(), '文档ID2'=>array(), ...)
     * )
     */
    public function searchMatchAny($word, $index, &$total, $offset=-1, $limit=-1)
    {
        $result = $this->_searchMatchAny($word, $index, $total, $offset, $limit);
        // 如果sphinx返回false，表明sphinx连接异常，则重新
        if ($result === false)
        {
            $this->reconnect();
            $result = $this->_searchMatchAny($word, $index, $total, $offset, $limit);
            if ($result === false)
            {
                return Result::error('full text search error - return false', 'Sphinx::search');
            }
        }
        return $result;
    }
    /**
     *
     *   基于分词的任意匹配查询
     * @param string $word 待查询词
     * @param string $index 索引名
     * @param int &$total 引用值，返回记录总数
     * @param int $offset 偏移
     * @param int $limit 需返回的条目数
     * @return \utils\Result格式
     * array(
     *      status=>
     *      data=> 无结果返回空array(), 否则返回array('文档ID1'=>array(), '文档ID2'=>array(), ...)
     * )
     */
    private function _searchMatchAny($word, $index, &$total, $offset=-1, $limit=-1)
    {
        //设置匹配模式
        $this->_client->setMatchMode(SPH_MATCH_ANY);
        //返回查询结果
        $result = $this->search($word, $index, $total, $offset, $limit);
        return $result;
    }
    /**
     *
     *   完全匹配查询
     * @param string $word 待查询词
     * @param string $index 索引名
     * @param int &$total 引用值，返回记录总数
     * @param int $offset 偏移
     * @param int $limit 需返回的条目数
     * @return \utils\Result格式
     * array(
     *      status=>
     *      data=> 无结果返回空array(), 否则返回array('文档ID1'=>array(), '文档ID2'=>array(), ...)
     * )
     */
    public function searchMatchAll($word, $index, &$total, $offset=-1, $limit=-1)
    {
        $result = $this->_searchMatchAll($word, $index, $total, $offset, $limit);
        // 如果sphinx返回false，表明sphinx连接异常，则重新
        if ($result === false)
        {
            $this->reconnect();
            $result = $this->_searchMatchAll($word, $index, $total, $offset, $limit);
            if ($result === false)
            {
                return Result::error('full text search error - return false', 'Sphinx::search');
            }
        }
        return $result;
    }
    /**
     *
     *   完全匹配查询
     * @param string $word 待查询词
     * @param string $index 索引名
     * @param int &$total 引用值，返回记录总数
     * @param int $offset 偏移
     * @param int $limit 需返回的条目数
     * @return \utils\Result格式
     * array(
     *      status=>
     *      data=> 无结果返回空array(), 否则返回array('文档ID1'=>array(), '文档ID2'=>array(), ...)
     * )
     */
    private function _searchMatchAll($word, $index, &$total, $offset=-1, $limit=-1)
    {
        //设置匹配模式
        $this->_client->setMatchMode(SPH_MATCH_ALL);
        //返回查询结果
        return $this->search($word, $index, $total, $offset, $limit);
    }
    /**
     *
     *   重置过滤器
     */
    public function resetFilters()
    {
        $this->_client->ResetFilters();
    }
    /**
     *
     *   重置分组
     */
    public function resetGroupBy()
    {
        $this->_client->ResetGroupBy();
    }
    /**
     *
     *   重置排序模式到相关性排序
     */
    public function resetSortMode()
    {
        $this->_client->SetSortMode(SPH_SORT_RELEVANCE);
    }
    /**
     *
     *   设置过滤条件
     * @param string $attribute 属性名
     * @param array $values 值的数组
     * @param bool $exclude 包含或不包含
     * @return \utils\Result格式
     * array(
     *      status=>
     *      data=> boolean 是否成功
     * )
     */
    public function setFilter($attribute , $values, $exclude = false)
    {
        $ret = $this->_client->setFilter($attribute, $values, $exclude);
        if ($ret)
        {
            return Result::ok(true);
        }
        else
        {
            return Result::error('set Filter fail', 'Sphinx::setFilter', 'warn');
        }
    }
    /**
     *
     *   设置分组属性
     * @param string $attribute 熟悉名
     * @param int $func 生成group-by key的函数
     * @param string $groupsort 设置分组排序方法
     * @return \utils\Result格式
     * array(
     *      status=>
     *      data=> boolean 是否成功
     * )
     */
    public function setGroupBy($attribute, $func, $groupsort = "@group desc")
    {
        $ret = $this->_client->SetGroupBy($attribute, $func, $groupsort);
        if ($ret)
        {
            return Result::ok(true);
        }
        else
        {
            return Result::error('set GroupBy fail', 'Sphinx::setGroupBy', 'warn');
        }
    }
    /**
     *
     *   设置排序模式
     * @param int $mode 模式
     * @param string $sortby 可选的，排序的描述
     * @return \utils\Result格式
     * array(
     *      status=>
     *      data=> boolean 是否成功
     * )
     */
    public function setSortMode($mode, $sortby='')
    {
        $ret = false;
        if (self::$SORT_ATTR_ASC == $mode)
        {
            $ret = $this->_client->SetSortMode(SPH_SORT_RELEVANCE);
        }
        else if (self::$SORT_ATTR_ASC == $mode)
        {
            $ret = $this->_client->SetSortMode(SPH_SORT_ATTR_ASC, $sortby);
        }
        else if (self::$SORT_ATTR_DESC == $mode)
        {
            $ret = $this->_client->SetSortMode(SPH_SORT_ATTR_DESC, $sortby);
        }
        else if (self::$SORT_TIME_SEGMENTS == $mode)
        {
            $ret = $this->_client->SetSortMode(SPH_SORT_TIME_SEGMENTS);
        }
        else if (self::$SORT_EXTENDED == $mode)
        {
            $ret = $this->_client->SetSortMode(SPH_SORT_EXTENDED, $sortby);
        }
        else if (self::$SORT_EXPR == $mode)
        {
            $ret = $this->_client->SetSortMode(SPH_SORT_EXPR, $sortby);
        }
        if ($ret)
        {
            return Result::ok(true);
        }
        else
        {
            return Result::error('set SortMode fail', 'Sphinx::setSortMode', 'warn');
        }
    }
    /**
     * 
     *   立即更新指定文档的指定属性值
     * @param string $index 为待更新的（一个或多个）索引名
     * @param array $attrs 
     * @param array $values
     * @return 成功则返回实际被更新的文档数目（0或更多），失败则返回-1
     * @example
     * $cl->UpdateAttributes ( "products", array ( "price", "amount_in_stock" ),
                array ( 1001=>array(123,5), 1002=>array(37,11), 1003=>(25,129) ) );
     */
    public function updateAttributes ( $index, $attrs, $values )
    {
        $ret = $this->_client->UpdateAttributes ( $index, $attrs, $values );
        if ($ret != -1)
        {
            return Result::ok($ret);
        }
        else
        {
            return Result::error('updateAttributes fail', 'Sphinx::updateAttributes', 'warn');
        }
    }


    /**
     *
     *   关闭长连接(available only if compiled with libsphinxclient >= 0.9.9)
     * @return \utils\Result格式
     * array(
     *      status=>
     *      data=> boolean 是否成功
     * )
     */
    public function close()
    {
        $ret = $this->_client->Close();
        if ($ret)
        {
            return Result::ok(true);
        }
        else
        {
            return Result::error('closePersistentConn fail', 'Sphinx::closePersistentConn', 'warn');
        }
    }
    /**
     *
     *   设置当前sphinx服务器
     * @param string $group 组名，获取sphinx组服务器配置
     * @param bool $replace 是否替换已存在的连接，默认为不替换
     */
    public function setCurrServer($group = 'default', $replace=false)
    {
        //如果已经有该server，则直接使用
        if (isset($this->_clients[$group]) && !$replace)
        {
            //切换回已保存的sphinx客户端
            $this->_client = $this->_clients[$group];
            return;
        }
        //获取应用的sphinx server配置
        $serverConf = SphinxServerConfig::getServer($group);
        if (empty($serverConf))
        {
            return Result::error('server configure not found', 'SphinxSearcher:setCurrServer');
        }
        //切分主机地址和端口号
        $serverConf = explode(':', $serverConf);
        $arr_serverConf = array();
        //设置主机地址
        $arr_serverConf['host'] = $serverConf[0];
        //如果有定义端口
        if (isset($serverConf[1]))
        {
            $arr_serverConf['port'] = $serverConf[1];
        }
        else
        {
            //否则，使用默认端口
            $arr_serverConf['port'] = 9312;
        }

        //连接sphinx、设置当前的sphinx客户端
        $this->_client = $this->getSphinxClient($arr_serverConf);
        //保存sphinx客户端
        $this->_clients[$group] = $this->_client;
        // 设置当前组名
        $this->_currGroup = $group;
    }
    private function reconnect()
    {
        // 重新替换sphinx链接，并重新查询一次
        $this->setCurrServer($this->_currGroup, true);
    }
    /**
     *
     *   根据服务器配置信息获取sphinx查询客户端
     * @param array $serverConf 服务器配置
     * @return 返回client
     */
    private function getSphinxClient($serverConf)
    {
        $client = new \SphinxClient;
        $client->setServer($serverConf['host'], $serverConf['port']);
        $client->SetSortMode(SPH_SORT_RELEVANCE);
        $client->setConnectTimeout($this->_connectTimeout);
        $client->SetMaxQueryTime($this->_maxQueryTime);
        $client->Open();
        return $client;
    }

    /**
     *
     *   重置结果限制条件
     * @return \utils\Result格式
     * array(
     *      status=>
     *      data=> boolean 是否成功
     * )
     */
    private function resetLimit()
    {
        $ret = $this->_client->SetLimits(0, $this->_limitMax);
        if ($ret)
        {
            return Result::ok(true);
        }
        else
        {
            return Result::error('resetLimit fail', 'Sphinx::resetLimit', 'warn');
        }
    }
    /**
     *
     *   根据结果的格式，判断成功与否，成功则取出查询结果返回
     * @param array $result sphinx的查询结果
     * @param int &$total 引用值，返回记录总数
     * @return \utils\Result格式
     * array(
     *      status=>
     *      data=> 无结果返回空array(), 否则返回array('文档ID1'=>array(), '文档ID2'=>array(), ...)
     * )
     */
    private function parseSearchResult($result, &$total)
    {
        $total = 0;
        if ($result == false)
        {
            $this->log($result);
            return false;
        }
        if ($result['error'] != '')
        {
            $this->log($result);
            return Result::error('full text search error - '. $result['error'], 'Sphinx::search');
        }
        else if ($result['warning'] != '')
        {
            $this->log($result);
            //只记录warning，逻辑继续
            Result::error('full text search warning - '. $result['warning'], 'Sphinx::search', 'warn');
        }
        if (isset($result['matches']))
        {
            $total = $result['total'];
            return Result::ok($result['matches']);
        }
        else
        {
            return Result::ok(array());
        }
    }
    /**
     *
     *   查询
     * @param string $word 待查询词
     * @param string $index 索引名
     * @param int &$total 引用值，返回记录总数
     * @param int $offset 偏移
     * @param int $limit 需返回的条目数
     * @return \utils\Result格式
     * array(
     *      status=>
     *      data=> 无结果返回空array(), 否则返回array('文档ID1'=>array(), '文档ID2'=>array(), ...)
     * )
     */
    private function search($word, $index, &$total, $offset=-1, $limit=-1){
        if ($offset != -1 && $limit != -1)
        {
            //如果传入了完整的offset、limit参数
            $this->_client->setLimits($offset, $limit);
        }
        else
        {
            //否则，需要重置limit
            $this->resetLimit();
        }
        $result = $this->_client->query($word, $index);
        return $this->parseSearchResult($result, $total);
    }
	/**
     *    记录log
     * @param string $msg
     * @param string $level
     */
    private function log($msg, $level = 'debug')
    {
        Logger::writeLog($level, "datalevel.sphinx", $msg);
    }
    
}
class SphinxServerConfig
{
    public static function getServer($group)
    {
        if (self::$s_configs == null)
        {
            self::loadConf();
        }
        if(!isset(self::$s_configs[$group]))
        {
            return array();
        }
        return self::$s_configs[$group];
    }
    public static function loadConf()
    {
        $config = Config::getGlobal();
        self::$s_configs = require $config['base']['configs']['basePath'] . '/Sphinx_Servers.inc.php';
    }
    
    private static $s_configs = null;
}
