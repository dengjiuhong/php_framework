<?php
/**
 * 数据库配置管理
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
use framework\base\DalException as DalException;
use framework\base\Config as Config;
class DbConfiguration
{
    static private $s_config = null;

    private $_servers = array();
    private $_tables = array();
    private $_dataDistri = array();

    const DB_MYSQL = 'mysql';
    const DB_TC = 'tc';

    private function __construct()
    {
        $this->loadConfig();
    }

    /**
     * 获取数据库类型
     * @param string tableName 数据表名
     * @return string 数据库类型
     */
    public function getDbType($tableName)
    {
        if (isset($this->_tables[$tableName]['db']) ) return $this->_tables[$tableName]['db'];
        throw new DalException("unknow db tableName " . $tableName);
    }

    /**
     * 获取字段信息
     * @param string tableName 数据表名
     * @return array 字段信息
     */
    public function getFields($tableName)
    {
        return explode(',', $this->_tables[$tableName]['fields']);
    }

    /**
     * 获取拆分关键KEY
     * @param string tableName 数据表名
     * @return string 拆分的KEY
     */
    public function getShardKey($tableName)
    {
        return $this->_tables[$tableName]['shardKey'];
    }

    /**
     * 根据id值获取服务器信息
     * @param string id 数据拆分KEY的Id值
     * @param string dbType 数据库类型
     * @return 服务器信息, 如果存在迁移情况，则返回两个服务器
     * 如   array (
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
     )
     */
    public function getServerById($dbType, $id)
    {
        //根据id获取数据分布对应的KEY
        $dataDistriKey = $this->getDataDistriKey($dbType, $id);
        if(empty($dataDistriKey))
        {
            return array();
        }
        //通过数据分布配置获取服务器信息。
        $arrServerName = explode('->', $this->_dataDistri[$dbType][$dataDistriKey]);
        if(empty($arrServerName))
        {
            return array();
        }
        if(count($arrServerName) == 1)
        {
            return array(
				'move' => false,
				'src'  => $this->_servers[$arrServerName[0]],
				'dst'  => null,
            );
        }
        else
        {
            return array(
				'move' => true,
				'src'  => $this->_servers[$arrServerName[0]],
				'dst'  => $this->_servers[$arrServerName[1]],
            );
        }
        return array();
    }

    /**
     * 获取服务器服务器信息
     * @param string dbType 数据库类型
     * @param string serverName 服务器名称
     * @return 服务器信息, 如果存在迁移情况，则返回两个服务器
     * 如   array (
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
     )
     */
    public function getServerByServerName($dbType, $serverName)
    {
        if(empty($serverName))
        {
            return array();
        }
        //通过数据分布配置获取服务器信息。
        $arrServerName = explode('->', $serverName);
        if(count($arrServerName) == 1)
        {
            return array(
				'move' => false,
				'src'  => $this->_servers[$arrServerName[0]],
				'dst'  => null,
            );
        }
        else
        {
            return array(
				'move' => true,
				'src'  => $this->_servers[$arrServerName[0]],
				'dst'  => $this->_servers[$arrServerName[1]],
            );
        }
        return array();
    }

    /**
     * 获取所有服务器信息
     * @param string dbType 数据库类型，可选值为mysql, tc
     * @return
     * 如array(
     *      A001 => array(
                'master'=>'192.168.3.150:1611',
                'slaver'=>'192.168.3.151:1611',
                'connectType'=>1,
            ),
            'B001' => array(
                'master'=>'192.168.3.150:1611',
                'slaver'=>'192.168.3.151:1611',
                'connectType'=>1,
            ),
         )
     */
    public function getAllServers($dbType)
    {
        if (in_array($dbType, array('mysql', 'tc')))
        {
            // 根据分布表，取出对应类型的服务器
            if (!isset($this->_dataDistri[$dbType]) || empty($this->_dataDistri[$dbType]))
            {
                return array();
            }
            $servers = $this->_dataDistri[$dbType];
            $uniqueServers = array_keys(array_flip($servers));
            $serversInfo = array();
            foreach ($uniqueServers as $server){
                $serversInfo[$server] =  $this->_servers[$server];
            }
            // 对所有服务器进行排重，即为结果
            return $serversInfo;
        }
        else
        {
            return array();
        }
    }


    /**
     * 对KEY按服务器进行分类
     *
     * @param string dbType 数据库类型
     * @param array $keys
     * @return array 返回以相同服务器（名称）分类后的二维数组
     * 如：array(
     *     	'A001'=>array('id1', 'id2',......),//同一台服务器的id
     *     	'B001'=>array('id1', 'id2',......),//同一台服务器的id
     * )
     */
    public function classifyIdsByServer($dbType, $ids)
    {
        if($dbType != DbConfiguration::DB_MYSQL && $dbType != DbConfiguration::DB_TC)
        {
            return array();
        }
        $arrResult = array();
        foreach($ids as $id)
        {
            $dataDistrikey = $this->getDataDistriKey($dbType, $id);
            $serverName = $this->_dataDistri[$dbType][$dataDistrikey];

            if(empty($serverName))
            {
                $arrResult['unknown'][] = $id;
                continue;
            }
            //将Id放置到某台服务器组上
            $arrResult[$serverName][] = $id;
        }
        return $arrResult;
    }

    /**
     * 获取指定id在数据分布配置中的key
     * @param string $dbType
     * @param int $id
     */
    private function getDataDistriKey($dbType, $id)
    {
        if($dbType != DbConfiguration::DB_MYSQL && $dbType != DbConfiguration::DB_TC)
        {
            return '';
        }
        $key = '';
        if($dbType == DbConfiguration::DB_MYSQL)
        {
            //因为以万为单位所以除以10000
            $id = intval(intval($id) / 10000);
            $start = $id - $id % 10;
            $end = $start + 10;
            $key = $start . '-' . $end;

        }
        else if($dbType == DbConfiguration::DB_TC)
        {
            $sufNum = 100;//如果id少于3位，则默认保存到第一个范围段
            if(strlen(strval($id)) >= 3 && intval($id) >= 100)
            {
                $sufNum = intval(substr(strval($id), 0, 3));//取前3位随机数
            }
            if(intval($sufNum) < 100)//如果数值小于100，，则默认保存到第一个范围段
            {
                $sufNum = 100;
            }
            $start = $sufNum - $sufNum % 10;
            $end = $start + 9;
            $key = $start . '-' . $end;

        }
        return $key;
    }

    private function loadServersConfig()
    {
        //如果已经加载，则直接返回
        if(!empty($this->_servers))
        {
            return;
        }
        //为了避免多一次的拷贝以及所占的空间，这里不使用framework\base\Config
        $config = Config::getGlobal();
        $this->_servers = include $config['base']['configs']['basePath']  . '/Db_Servers.inc.php';
    }

    private function loadTablesConfig()
    {
        //如果已经加载，则直接返回
        if(!empty($this->_tables))
        {
            return;
        }
        $config = Config::getGlobal();
        //为了避免多一次的拷贝以及所占的空间，这里不使用framework\base\Config
        $this->_tables = include $config['base']['configs']['basePath'] . '/Db_Tables.inc.php';

    }

    private function loadDataDistriConfig()
    {
        //如果已经加载，则直接返回
        if(!empty($this->_dataDistri))
        {
            return;
        }
        $config = Config::getGlobal();
        //为了避免多一次的拷贝的性能和所占的空间，这里不使用framework\base\Config
        $this->_dataDistri = include $config['base']['configs']['basePath'] . '/Db_DataDistribution.inc.php';
    }

    private function loadConfig()
    {
        //从配置文件中读取配置
        $this->loadDataDistriConfig();
		$this->loadServersConfig();
		$this->loadTablesConfig();

	}

	static public function getInstance()
	{
		if(self::$s_config == null)
		{
			self::$s_config = new self();
		}
		return self::$s_config;
	}
}
?>
